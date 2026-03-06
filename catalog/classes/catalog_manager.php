<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_catalog;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');

/**
 * Gestor del catálogo de cursos.
 *
 * Centraliza toda la lógica de:
 * - Query recursiva de cursos por categorías
 * - Filtrado por precio, nivel, tags, texto
 * - Obtención de datos extra (instructores, precio, custom fields)
 * - Paginación
 * - Datos para los filtros laterales
 * - Detalle completo de un curso (página tipo Udemy)
 */
class catalog_manager {

    // =====================================================================
    // DETALLE DEL CURSO — Página individual tipo Udemy
    // =====================================================================

    /**
     * Obtiene TODOS los datos de un curso para la página de detalle.
     *
     * @param int $courseid
     * @return array|null Datos completos o null si no existe
     */
    public function get_course_detail(int $courseid): ?array {
        global $DB, $USER;

        $record = $DB->get_record('course', ['id' => $courseid, 'visible' => 1]);
        if (!$record || $record->id == SITEID) {
            return null;
        }

        $course = new \core_course_list_element(get_course($courseid));
        $context = \context_course::instance($courseid);

        // --- Datos básicos ---
        $data = [
            'id'       => $courseid,
            'fullname' => $record->fullname,
            'summary'  => format_text($record->summary, FORMAT_HTML),
            'courseurl' => (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            'enrolurl' => (new \moodle_url('/enrol/index.php', ['id' => $courseid]))->out(false),
        ];

        // --- Breadcrumbs ---
        $data['breadcrumbs'] = $this->get_breadcrumbs($record);

        // --- Imagen ---
        $imageurl = $this->get_course_image($course);
        $data['imageurl'] = $imageurl;
        $data['hasimage'] = !empty($imageurl);
        $data['initials'] = strtoupper(substr($record->fullname, 0, 2));

        // --- Instructores (detallado) ---
        $data['instructors'] = $this->get_instructors_detail($courseid);
        $data['hasinstructors'] = !empty($data['instructors']);
        $data['instructornames'] = implode(', ', array_column($data['instructors'], 'fullname'));

        // --- Estadísticas ---
        $data['enrolledcount'] = count_enrolled_users($context);
        $data['activitycount'] = $this->get_activity_count($courseid);
        $data['hasactivities'] = $data['activitycount'] > 0;

        // --- Fechas ---
        $data['lastupdate'] = userdate($record->timemodified, get_string('strftimedate', 'langconfig'));
        $data['timecreated'] = userdate($record->timecreated, get_string('strftimedate', 'langconfig'));

        // --- Idioma del curso ---
        $data['courselang'] = $this->get_course_language($record->lang);
        $data['hascourselang'] = !empty($data['courselang']);

        // --- Precio ---
        $price = $this->get_price($courseid);
        $data['is_free'] = $price['is_free'];
        $data['price'] = $price['is_free'] ? '' : $price['cost'] . ' ' . $price['currency'];
        $data['pricelabel'] = $price['is_free']
            ? get_string('free', 'local_catalog')
            : $price['cost'] . ' ' . $price['currency'];

        // --- Custom fields ---
        $customfields = $this->get_all_custom_fields($courseid);

        // Nivel y duración
        $data['level'] = $customfields['course_level'] ?? '';
        $data['haslevel'] = !empty($data['level']);
        $data['duration'] = $customfields['course_duration'] ?? '';
        $data['hasduration'] = !empty($data['duration']);

        // Video preview
        $data['previewvideo'] = $customfields['course_preview_video'] ?? '';
        $data['haspreviewvideo'] = !empty($data['previewvideo']);

        // Objetivos de aprendizaje (textarea → lista)
        $data['objectives'] = $this->textarea_to_list($customfields['course_objectives'] ?? '');
        $data['hasobjectives'] = !empty($data['objectives']);

        // Requisitos previos (textarea → lista)
        $data['requirements'] = $this->textarea_to_list($customfields['course_requirements'] ?? '');
        $data['hasrequirements'] = !empty($data['requirements']);

        // Descripción extendida
        $data['longdescription'] = $customfields['course_long_description'] ?? '';
        $data['haslongdescription'] = !empty($data['longdescription']);

        // --- Tags ---
        $data['tags'] = $this->get_tags($courseid);
        $data['hastags'] = !empty($data['tags']);

        // --- Temario (secciones + actividades) ---
        $data['sections'] = $this->get_course_sections($courseid);
        $data['hassections'] = !empty($data['sections']);
        $data['sectioncount'] = count($data['sections']);
        $totalduration = 0;
        $totallessons = 0;
        foreach ($data['sections'] as $section) {
            $totallessons += count($section['activities']);
        }
        $data['totallessons'] = $totallessons;

        // --- Cursos relacionados (misma categoría) ---
        $data['relatedcourses'] = $this->get_related_courses($courseid, $record->category);
        $data['hasrelatedcourses'] = !empty($data['relatedcourses']);

        // --- Más cursos del instructor ---
        $data['instructorcourses'] = $this->get_instructor_courses($courseid, $data['instructors']);
        $data['hasinstructorcourses'] = !empty($data['instructorcourses']);

        // --- Valoraciones (placeholder Fase 2) ---
        $data['rating'] = '4.0';
        $data['ratingcount'] = $data['enrolledcount'];
        $data['stars'] = '★★★★☆';

        // --- Estado de matriculacion del usuario actual ---
        $data["isenrolled"] = is_enrolled($context, $USER);
        $data["isloggedin"] = isloggedin() && !isguestuser();
        $data["learnurl"] = (new \moodle_url("/local/catalog/learn.php", ["id" => $courseid]))->out(false);
        $data["loginurl"] = (new \moodle_url("/login/index.php"))->out(false);
        return $data;
    }


    /**
     * Genera los breadcrumbs del curso.
     */
    private function get_breadcrumbs(\stdClass $course): array {
        $crumbs = [];
        $crumbs[] = [
            'text' => get_string('catalog', 'local_catalog'),
            'url'  => (new \moodle_url('/local/catalog/index.php'))->out(false),
        ];

        try {
            $category = \core_course_category::get($course->category);
            // Obtener padres
            $parents = $category->get_parents();
            foreach ($parents as $parentid) {
                $parent = \core_course_category::get($parentid);
                $crumbs[] = [
                    'text' => $parent->name,
                    'url'  => (new \moodle_url('/local/catalog/index.php', ['cat' => $parent->id]))->out(false),
                ];
            }
            // Categoría actual
            $crumbs[] = [
                'text' => $category->name,
                'url'  => (new \moodle_url('/local/catalog/index.php', ['cat' => $category->id]))->out(false),
            ];
        } catch (\Exception $e) {
            // Silently skip
        }

        // Curso actual (sin link)
        $crumbs[] = [
            'text'    => $course->fullname,
            'url'     => '',
            'current' => true,
        ];

        return $crumbs;
    }

    /**
     * Obtiene instructores con datos detallados del perfil.
     */
    private function get_instructors_detail(int $courseid): array {
        global $DB, $OUTPUT;

        $context = \context_course::instance($courseid);
        $instructors = [];

        foreach ([3, 4] as $roleid) {
            $users = get_role_users($roleid, $context, false,
                'u.id, u.firstname, u.lastname, u.email, u.description, u.picture, u.imagealt');

            foreach ($users as $user) {
                // Contar cursos del instructor
                $sql = "SELECT COUNT(DISTINCT ra.contextid)
                          FROM {role_assignments} ra
                          JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                         WHERE ra.userid = :userid AND ra.roleid IN (3, 4)";
                $coursecount = $DB->count_records_sql($sql, ['userid' => $user->id]);

                // Contar estudiantes totales del instructor
                $sql = "SELECT COUNT(DISTINCT ue.userid)
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                          JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
                          JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :teacherid
                         WHERE ra.roleid IN (3, 4)";
                $studentcount = $DB->count_records_sql($sql, ['teacherid' => $user->id]);

                // Avatar
                $userpicture = new \user_picture($user);
                $userpicture->size = 100;

                $instructors[] = [
                    'id'           => $user->id,
                    'fullname'     => fullname($user),
                    'bio'          => format_text($user->description ?? '', FORMAT_HTML),
                    'hasbio'       => !empty($user->description),
                    'avatarurl'    => $userpicture->get_url($GLOBALS['PAGE'], $GLOBALS['OUTPUT'])->out(false),
                    'profileurl'   => (new \moodle_url('/user/profile.php', ['id' => $user->id]))->out(false),
                    'coursecount'   => $coursecount,
                    'studentcount'  => $studentcount,
                    'rating'        => '4.5', // Placeholder fase 2
                    'reviewcount'   => 0,     // Placeholder fase 2
                ];
            }
        }

        return $instructors;
    }


    /**
     * Obtiene el nombre legible del idioma del curso.
     */
    private function get_course_language(string $langcode): string {
        if (empty($langcode)) {
            return '';
        }

        $translations = get_string_manager()->get_list_of_translations();
        return $translations[$langcode] ?? $langcode;
    }


    /**
     * Obtiene TODOS los campos personalizados del curso.
     */
    private function get_all_custom_fields(int $courseid): array {
        $result = [];

        try {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $fields = $handler->get_instance_data($courseid);

            foreach ($fields as $field) {
                $shortname = $field->get_field()->get('shortname');
                $value = $field->export_value();
                if (!empty($value)) {
                    $result[$shortname] = $value;
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return $result;
    }

    /**
     * Convierte un textarea (un item por línea) en array para Mustache.
     */
    private function textarea_to_list(string $text): array {
        if (empty($text)) {
            return [];
        }

        // Limpiar HTML y separar por líneas
        $text = strip_tags($text);
        $lines = explode("\n", $text);
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $items[] = ['text' => $line];
            }
        }

        return $items;
    }

    /**
     * Obtiene las secciones y actividades del curso (temario).
     *
     * Genera la estructura tipo Udemy:
     * Sección 1: "Preparando el entorno" (4 lecciones, 24 min)
     *   - 1. Instalando VS Code (Página, 2 min)
     *   - 2. Extensiones (Página, 15 min)
     */
    private function get_course_sections(int $courseid): array {
        $sections = [];

        try {
            $modinfo = get_fast_modinfo($courseid);
            $courseformat = course_get_format($courseid);

            foreach ($modinfo->get_section_info_all() as $sectionnum => $sectioninfo) {
                // Saltar sección 0 (general) si está vacía
                if ($sectionnum == 0 && empty($modinfo->sections[0])) {
                    continue;
                }

                $sectionname = $courseformat->get_section_name($sectioninfo);
                if (empty($sectionname)) {
                    $sectionname = get_string('section') . ' ' . $sectionnum;
                }

                $activities = [];
                if (isset($modinfo->sections[$sectionnum])) {
                    foreach ($modinfo->sections[$sectionnum] as $cmid) {
                        $cm = $modinfo->cms[$cmid];
                        if (!$cm->uservisible && !$cm->visible) {
                            continue;
                        }

                        $activitytype = get_string('modulename', $cm->modname);

                        // Icono según tipo
                        $icon = '📄';
                        switch ($cm->modname) {
                            case 'page':    $icon = '📝'; break;
                            case 'url':     $icon = '🔗'; break;
                            case 'resource': $icon = '📎'; break;
                            case 'quiz':    $icon = '📋'; break;
                            case 'forum':   $icon = '💬'; break;
                            case 'assign':  $icon = '📤'; break;
                            case 'lesson':  $icon = '📖'; break;
                            case 'h5p':     $icon = '🎮'; break;
                            case 'label':   continue 2; // Skip labels
                        }

                        $activities[] = [
                            'name' => $cm->name,
                            'icon' => $icon,
                            'type' => $activitytype,
                        ];
                    }
                }

                if (!empty($activities) || $sectionnum == 0) {
                    $sections[] = [
                        'name'          => $sectionname,
                        'num'           => $sectionnum,
                        'activitycount' => count($activities),
                        'activities'    => $activities,
                        'hasactivities' => !empty($activities),
                        'isopen'        => ($sectionnum <= 1), // Primera sección abierta
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return $sections;
    }

    /**
     * Obtiene cursos relacionados (misma categoría, excluyendo el actual).
     */
    private function get_related_courses(int $courseid, int $categoryid, int $limit = 4): array {
        global $DB;

        $sql = "SELECT id, fullname, summary
                  FROM {course}
                 WHERE category = :catid AND id != :courseid AND id != :siteid AND visible = 1
              ORDER BY timecreated DESC
                 LIMIT $limit";

        $records = $DB->get_records_sql($sql, [
            'catid'    => $categoryid,
            'courseid' => $courseid,
            'siteid'   => SITEID,
        ]);

        $courses = [];
        foreach ($records as $record) {
            $course = new \core_course_list_element(get_course($record->id));
            $imageurl = $this->get_course_image($course);
            $price = $this->get_price($record->id);

            $summary = strip_tags($record->summary);
            if (strlen($summary) > 80) {
                $summary = substr($summary, 0, 80) . '...';
            }

            $courses[] = [
                'id'        => $record->id,
                'fullname'  => $record->fullname,
                'summary'   => $summary,
                'imageurl'  => $imageurl,
                'hasimage'  => !empty($imageurl),
                'initials'  => strtoupper(substr($record->fullname, 0, 2)),
                'url'       => (new \moodle_url('/local/catalog/course.php', ['id' => $record->id]))->out(false),
                'is_free'   => $price['is_free'],
                'pricelabel' => $price['is_free'] ? get_string('free', 'local_catalog') : $price['cost'] . ' ' . $price['currency'],
            ];
        }

        return $courses;
    }

    /**
     * Obtiene otros cursos del mismo instructor.
     */
    private function get_instructor_courses(int $courseid, array $instructors, int $limit = 4): array {
        global $DB;

        if (empty($instructors)) {
            return [];
        }

        $teacherid = $instructors[0]['id'];

        $sql = "SELECT DISTINCT c.id, c.fullname, c.summary
                  FROM {course} c
                  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                  JOIN {role_assignments} ra ON ra.contextid = ctx.id
                 WHERE ra.userid = :teacherid
                   AND ra.roleid IN (3, 4)
                   AND c.id != :courseid
                   AND c.id != :siteid
                   AND c.visible = 1
              ORDER BY c.timecreated DESC
                 LIMIT $limit";

        $records = $DB->get_records_sql($sql, [
            'teacherid' => $teacherid,
            'courseid'  => $courseid,
            'siteid'    => SITEID,
        ]);

        $courses = [];
        foreach ($records as $record) {
            $course = new \core_course_list_element(get_course($record->id));
            $imageurl = $this->get_course_image($course);
            $price = $this->get_price($record->id);

            $courses[] = [
                'id'        => $record->id,
                'fullname'  => $record->fullname,
                'imageurl'  => $imageurl,
                'hasimage'  => !empty($imageurl),
                'initials'  => strtoupper(substr($record->fullname, 0, 2)),
                'url'       => (new \moodle_url('/local/catalog/course.php', ['id' => $record->id]))->out(false),
                'is_free'   => $price['is_free'],
                'pricelabel' => $price['is_free'] ? get_string('free', 'local_catalog') : $price['cost'] . ' ' . $price['currency'],
            ];
        }

        return $courses;
    }

    // =====================================================================
    // MÉTODOS COMPARTIDOS (usados por catálogo y detalle)
    // =====================================================================

    /**
     * Obtiene la URL de la imagen del curso.
     */
    public function get_course_image(\core_course_list_element $course): string {
        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }
        }
        return '';
    }

    /**
     * Obtiene información de precio del curso.
     */
    public function get_price(int $courseid): array {
        $result = ['is_free' => true, 'cost' => '0', 'currency' => 'EUR'];

        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'fee' && !empty($instance->cost)) {
                $result['is_free']  = false;
                $result['cost']     = number_format((float) $instance->cost, 2, ',', '.');
                $result['currency'] = $instance->currency ?? 'EUR';
                break;
            }
        }

        return $result;
    }

    /**
     * Obtiene los tags del curso.
     */
    public function get_tags(int $courseid): array {
        $tags = \core_tag_tag::get_item_tags('core', 'course', $courseid);
        $result = [];

        foreach ($tags as $tag) {
            $result[] = [
                'name' => $tag->rawname,
                'url'  => (new \moodle_url('/local/catalog/index.php', [
                    'tag' => $tag->rawname,
                ]))->out(false),
            ];
        }

        return $result;
    }

    /**
     * Cuenta las actividades visibles del curso.
     */
    public function get_activity_count(int $courseid): int {
        try {
            $modinfo = get_fast_modinfo($courseid);
            $count = 0;
            foreach ($modinfo->get_cms() as $cm) {
                if ($cm->uservisible) {
                    $count++;
                }
            }
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }


    // =====================================================================
    // CATÁLOGO — Listado de cursos (código existente)
    // =====================================================================

    /**
     * Obtiene cursos filtrados con paginación.
     */
    public function get_courses(array $filters, int $page = 0, int $perpage = 12): array {
        global $DB;

        $where = ['c.id != :siteid', 'c.visible = 1'];
        $params = ['siteid' => SITEID];

        // Filtro por categoría (recursivo)
        $categoryids = $this->get_recursive_category_ids($filters['category']);
        if (!empty($categoryids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
            $where[] = "c.category $insql";
            $params = array_merge($params, $inparams);
        }

        // Filtro por búsqueda de texto
        if (!empty($filters['search'])) {
            $searchterm = '%' . $DB->sql_like_escape($filters['search']) . '%';
            $where[] = '(' . $DB->sql_like('c.fullname', ':search1', false) .
                       ' OR ' . $DB->sql_like('c.summary', ':search2', false) . ')';
            $params['search1'] = $searchterm;
            $params['search2'] = $searchterm;
        }

        // Filtro por tag
        $jointag = '';
        if (!empty($filters['tag'])) {
            $jointag = "JOIN {tag_instance} ti ON ti.itemid = c.id
                            AND ti.itemtype = 'course'
                            AND ti.component = 'core'
                        JOIN {tag} t ON t.id = ti.tagid";
            $where[] = $DB->sql_like('t.rawname', ':tagname', false);
            $params['tagname'] = $DB->sql_like_escape($filters['tag']);
        }

        $wheresql = implode(' AND ', $where);

        $countsql = "SELECT COUNT(DISTINCT c.id) FROM {course} c $jointag WHERE $wheresql";
        $total = $DB->count_records_sql($countsql, $params);

        $sql = "SELECT DISTINCT c.id, c.fullname, c.summary, c.category, c.timecreated
                  FROM {course} c $jointag
                 WHERE $wheresql
              ORDER BY c.timecreated DESC";

        $courserecords = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        $courses = [];
        foreach ($courserecords as $record) {
            $coursedata = $this->enrich_course_card($record);

            if (!empty($filters['price'])) {
                if ($filters['price'] === 'free' && !$coursedata['is_free']) { $total--; continue; }
                if ($filters['price'] === 'paid' && $coursedata['is_free']) { $total--; continue; }
            }
            if (!empty($filters['level'])) {
                $courselevel = strtolower($coursedata['level_raw'] ?? '');
                if (!empty($courselevel) && $courselevel !== $filters['level']) { $total--; continue; }
            }

            $courses[] = $coursedata;
        }

        $pagination = $this->build_pagination($page, $perpage, $total, $filters);

        return ['courses' => $courses, 'total' => $total, 'pagination' => $pagination];
    }

    /**
     * Enriquece un curso para la tarjeta del catálogo.
     */
    private function enrich_course_card(\stdClass $record): array {
        $course = new \core_course_list_element(get_course($record->id));
        $imageurl = $this->get_course_image($course);
        $instructors = $this->get_instructors_simple($record->id);
        $customfields = $this->get_custom_fields_simple($record->id);
        $price = $this->get_price($record->id);
        $tags = $this->get_tags($record->id);
        $activitycount = $this->get_activity_count($record->id);

        $summary = strip_tags($record->summary);
        if (strlen($summary) > 150) {
            $summary = substr($summary, 0, 150) . '...';
        }

        return [
            'id'              => $record->id,
            'fullname'        => $record->fullname,
            'summary'         => $summary,
            'imageurl'        => $imageurl,
            'hasimage'        => !empty($imageurl),
            'initials'        => strtoupper(substr($record->fullname, 0, 2)),
            'courseurl'        => (new \moodle_url('/local/catalog/course.php', ['id' => $record->id]))->out(false),
            'enrolurl'        => (new \moodle_url('/enrol/index.php', ['id' => $record->id]))->out(false),
            'instructornames'  => implode(', ', $instructors),
            'hasinstructors'   => !empty($instructors),
            'level'           => $customfields['course_level'] ?? '',
            'level_raw'       => strtolower($customfields['course_level'] ?? ''),
            'haslevel'        => !empty($customfields['course_level']),
            'duration'        => $customfields['course_duration'] ?? '',
            'hasduration'     => !empty($customfields['course_duration']),
            'activitycount'   => $activitycount,
            'hasactivities'   => $activitycount > 0,
            'is_free'         => $price['is_free'],
            'price'           => $price['is_free'] ? '' : $price['cost'] . ' ' . $price['currency'],
            'pricelabel'      => $price['is_free'] ? get_string('free', 'local_catalog') : $price['cost'] . ' ' . $price['currency'],
            'tags'            => $tags,
            'hastags'         => !empty($tags),
        ];
    }

    private function get_instructors_simple(int $courseid): array {
        $context = \context_course::instance($courseid);
        $names = [];
        foreach ([3, 4] as $roleid) {
            $users = get_role_users($roleid, $context, false, 'u.id, u.firstname, u.lastname');
            foreach ($users as $user) {
                $names[] = fullname($user);
            }
        }
        return $names;
    }

    private function get_custom_fields_simple(int $courseid): array {
        $result = ['course_level' => '', 'course_duration' => ''];
        try {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $fields = $handler->get_instance_data($courseid);
            foreach ($fields as $field) {
                $shortname = $field->get_field()->get('shortname');
                if (array_key_exists($shortname, $result)) {
                    $value = $field->export_value();
                    if (!empty($value)) {
                        $result[$shortname] = $value;
                    }
                }
            }
        } catch (\Exception $e) {}
        return $result;
    }

    private function get_recursive_category_ids(int $categoryid): array {
        if ($categoryid <= 0) return [];
        try {
            $category = \core_course_category::get($categoryid);
            return array_merge([$categoryid], $category->get_all_children_ids());
        } catch (\Exception $e) {
            return [$categoryid];
        }
    }

    public function get_filter_data(array $filters): array {
        global $DB;

        $categories = [];
        foreach (\core_course_category::get_all() as $cat) {
            if ($cat->id == 0) continue;
            $count = $cat->get_courses_count();
            if ($count > 0) {
                $categories[] = [
                    'id' => $cat->id, 'name' => $cat->name, 'count' => $count,
                    'active' => ($cat->id == $filters['category']),
                    'url' => (new \moodle_url('/local/catalog/index.php',
                        array_merge($this->clean_filters($filters), ['cat' => $cat->id])))->out(false),
                ];
            }
        }

        $levels = [];
        foreach (['Principiante', 'Intermedio', 'Avanzado', 'Todos los niveles'] as $name) {
            $levels[] = [
                'value' => strtolower($name), 'name' => $name,
                'active' => (strtolower($name) === $filters['level']),
                'url' => (new \moodle_url('/local/catalog/index.php',
                    array_merge($this->clean_filters($filters), ['level' => strtolower($name)])))->out(false),
            ];
        }

        $prices = [
            ['value' => 'free', 'name' => get_string('free', 'local_catalog'),
             'active' => ($filters['price'] === 'free'),
             'url' => (new \moodle_url('/local/catalog/index.php',
                 array_merge($this->clean_filters($filters), ['price' => 'free'])))->out(false)],
            ['value' => 'paid', 'name' => get_string('paid', 'local_catalog'),
             'active' => ($filters['price'] === 'paid'),
             'url' => (new \moodle_url('/local/catalog/index.php',
                 array_merge($this->clean_filters($filters), ['price' => 'paid'])))->out(false)],
        ];

        $tags = [];
        $sql = "SELECT t.id, t.rawname, COUNT(ti.id) as count
                  FROM {tag} t JOIN {tag_instance} ti ON ti.tagid = t.id
                 WHERE ti.itemtype = 'course' AND ti.component = 'core'
              GROUP BY t.id, t.rawname ORDER BY count DESC LIMIT 20";
        foreach ($DB->get_records_sql($sql) as $rec) {
            $tags[] = [
                'name' => $rec->rawname, 'count' => $rec->count,
                'active' => ($rec->rawname === $filters['tag']),
                'url' => (new \moodle_url('/local/catalog/index.php',
                    array_merge($this->clean_filters($filters), ['tag' => $rec->rawname])))->out(false),
            ];
        }

        return ['categories' => $categories, 'levels' => $levels, 'prices' => $prices, 'tags' => $tags];
    }

    private function clean_filters(array $filters): array {
        $clean = [];
        $map = ['category' => 'cat', 'search' => 'q', 'price' => 'price', 'level' => 'level', 'tag' => 'tag'];
        foreach ($map as $key => $param) {
            if (!empty($filters[$key])) $clean[$param] = $filters[$key];
        }
        return $clean;
    }

    private function build_pagination(int $page, int $perpage, int $total, array $filters): array {
        $totalpages = ceil($total / $perpage);
        if ($totalpages <= 1) return [];

        $clean = $this->clean_filters($filters);
        $pages = [];
        for ($i = 0; $i < $totalpages; $i++) {
            $pages[] = [
                'page' => $i, 'display' => $i + 1, 'active' => ($i === $page),
                'url' => (new \moodle_url('/local/catalog/index.php', array_merge($clean, ['page' => $i])))->out(false),
            ];
        }

        return [
            'pages' => $pages,
            'hasprev' => $page > 0,
            'hasnext' => $page < ($totalpages - 1),
            'prevurl' => (new \moodle_url('/local/catalog/index.php', array_merge($clean, ['page' => $page - 1])))->out(false),
            'nexturl' => (new \moodle_url('/local/catalog/index.php', array_merge($clean, ['page' => $page + 1])))->out(false),
        ];
    }
}
