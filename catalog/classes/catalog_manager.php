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

        $slug = catalog_manager::slugify($record->shortname ?: $record->fullname);

        $course = new \core_course_list_element(get_course($courseid));

        $context = \context_course::instance($courseid);

        // --- Datos básicos ---
        $data = [
            'id'       => $courseid,
            'fullname' => $record->fullname,
            'summary'  => format_text($record->summary, FORMAT_HTML),
            //'courseurl' => (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            //'enrolurl' => (new \moodle_url('/enrol/index.php', ['id' => $courseid]))->out(false),
            'courseurl' => '/cursos/' . $slug,
            'enrolurl'  => (new \moodle_url('/enrol/index.php', ['id' => $record->id]))->out(false),
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
        //$data['sections'] = $this->get_course_sections($courseid);
        $sectionsdata = $this->get_course_sections($courseid);
        $data = array_merge($data, $sectionsdata);  // añade todas las claves al $data principal
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
        // $data["learnurl"] = (new \moodle_url("/local/catalog/learn.php", ["id" => $courseid]))->out(false);
        $data['learnurl']  = '/cursos/' . $slug . '/ver';
        // ── URLs de navegación ────────────────────────────────────────────────
        $data['homeurl']        = (new \moodle_url('/'))->out(false);
        $data['catalogurl']     = (new \moodle_url('/cursos'))->out(false);
        $data['searchaction']   = (new \moodle_url('/cursos'))->out(false);
        $data['mycoursesurl']   = (new \moodle_url('/mis-cursos'))->out(false);
        $data["loginurl"]       = (new \moodle_url("/login"))->out(false);
        $data['loginactionurl'] = (new \moodle_url('/local/catalog/login.php'))->out(false); // para el form action
        $data['logouturl']      = (new \moodle_url('/login/logout.php',['sesskey' => sesskey()]))->out(false);
        $data['registerurl']    = (new \moodle_url('/registro'))->out(false);
        $data['instructorsurl'] = (new \moodle_url('/profesores'))->out(false);
        $data['planurl']        = (new \moodle_url('/plan-personal'))->out(false);
        $data['compareurl']     = (new \moodle_url('/comparar-planes'))->out(false);
        $data['demourl']        = (new \moodle_url('/solicitar-demo'))->out(false);
        $data['teachurl']       = (new \moodle_url('/ensena-aqui'))->out(false);
        $data['privacyurl']     = '#'; // Sustituir por URL real de privacidad

        return $data;
    }


    /**
     * Obtiene los cursos en los que el usuario está matriculado,
     * con estado de progreso.
     */
    public function get_my_courses(int $userid, string $filter = 'all', int $page = 0, int $perpage = 12): array {
        global $DB;

        // Cursos del usuario
        $sql = "SELECT DISTINCT c.id, c.fullname, c.summary, c.shortname, c.enablecompletion
              FROM {course} c
              JOIN {enrol} e ON e.courseid = c.id
              JOIN {user_enrolments} ue ON ue.enrolid = e.id
             WHERE ue.userid = :userid
               AND c.id != :siteid
               AND c.visible = 1
               AND ue.status = 0
          ORDER BY ue.timecreated DESC";

        $allrecords = $DB->get_records_sql($sql, ['userid' => $userid, 'siteid' => SITEID]);

        $courses    = [];
        $completed  = 0;
        $inprogress = 0;

        foreach ($allrecords as $record) {
            $slug        = self::slugify($record->shortname ?: $record->fullname);
            $courseobj   = new \core_course_list_element(get_course($record->id));
            $imageurl    = $this->get_course_image($courseobj);

            // Calcular progreso
            $progress = 0;
            $iscompleted = false;
            if ($record->enablecompletion) {
                $completion  = new \completion_info($record);
                if ($completion->is_enabled()) {
                    $params   = ['userid' => $userid, 'courseid' => $record->id];
                    $total    = $DB->count_records('course_modules',
                        ['course' => $record->id, 'completion' => 1]);
                    $done     = $DB->count_records_select(
                        'course_modules_completion',
                        'userid = :userid AND completionstate IN (1,2) AND coursemoduleid IN
                                 (SELECT id FROM {course_modules} WHERE course = :courseid AND completion = 1)',
                        $params);
                    $progress    = ($total > 0) ? round(($done / $total) * 100) : 0;
                    $iscompleted = ($progress >= 100);
                }
            }

            if ($iscompleted) { $completed++; }
            else              { $inprogress++; }

            // Aplicar filtro
            if ($filter === 'completed'  && !$iscompleted) { continue; }
            if ($filter === 'inprogress' && $iscompleted)  { continue; }

            $summary = strip_tags($record->summary);
            if (strlen($summary) > 100) {
                $summary = substr($summary, 0, 100) . '...';
            }

            $courses[] = [
                'id'          => $record->id,
                'fullname'    => $record->fullname,
                'summary'     => $summary,
                'imageurl'    => $imageurl,
                'hasimage'    => !empty($imageurl),
                'initials'    => strtoupper(substr($record->fullname, 0, 2)),
                'courseurl'   => '/cursos/' . $slug,              // ✅ URL amigable
                'learnurl'    => '/cursos/' . $slug . '/ver',     // ✅ URL amigable
                'progress'    => $progress,
                'iscompleted' => $iscompleted,
                'statuslabel' => $iscompleted
                    ? get_string('completed', 'local_catalog')
                    : get_string('inprogress', 'local_catalog'),
            ];
        }

        $total = count($courses);

        // Paginación
        $paginatecourses = array_slice($courses, $page * $perpage, $perpage);
        $totalpages      = $perpage > 0 ? ceil($total / $perpage) : 1;
        $pages           = [];

        for ($i = 0; $i < $totalpages; $i++) {
            $pages[] = [
                'page'    => $i,
                'display' => $i + 1,
                'active'  => ($i === $page),
                'url'     => (new \moodle_url('/mis-cursos', array_filter([
                    'filter' => $filter !== 'all' ? $filter : null,
                    'page'   => $i ?: null,
                ])))->out(false),
            ];
        }
        $pagination = $totalpages > 1 ? [
            'pages'   => $pages,
            'hasprev' => $page > 0,
            'hasnext' => $page < ($totalpages - 1),
            'prevurl' => (new \moodle_url('/mis-cursos', array_filter(['filter' => $filter !== 'all' ? $filter : null, 'page' => $page - 1 ?: null])))->out(false),
            'nexturl' => (new \moodle_url('/mis-cursos', array_filter(['filter' => $filter !== 'all' ? $filter : null, 'page' => $page + 1])))->out(false),
        ] : [];

        return [
            'courses'    => $paginatecourses,
            'total'      => $total,
            'completed'  => $completed,
            'inprogress' => $inprogress,
            'pagination' => $pagination,
        ];
    }


    /**
     * Genera los breadcrumbs del curso.
     */
    private function get_breadcrumbs(\stdClass $course): array {
        $crumbs = [];
        $crumbs[] = [
            'text' => get_string('catalog', 'local_catalog'),
            'url'  => (new \moodle_url('/cursos'))->out(false),          // ✅ URL amigable
        ];

        try {
            $category = \core_course_category::get($course->category);
            $parents  = $category->get_parents();
            foreach ($parents as $parentid) {
                $parent = \core_course_category::get($parentid);
                $crumbs[] = [
                    'text' => $parent->name,
                    'url'  => (new \moodle_url('/cursos', ['cat' => $parent->id]))->out(false), // ✅
                ];
            }
            $crumbs[] = [
                'text' => $category->name,
                'url'  => (new \moodle_url('/cursos', ['cat' => $category->id]))->out(false),   // ✅
            ];
        } catch (\Exception $e) {
            // Silently skip
        }

        $crumbs[] = [
            'text'    => $course->fullname,
            'url'     => '',
            'current' => true,
        ];

        return $crumbs;
    }

    /**
     * Obtiene el listado de todos los instructores para la página /profesores.
     */
    public function get_instructors_list(string $search = '', int $page = 0, int $perpage = 12): array {
        global $DB, $OUTPUT, $PAGE;

        // Base query: usuarios con rol teacher en algún curso
        $wheresearch = '';
        $params = [];

        if (!empty($search)) {
            $wheresearch = "AND (" . $DB->sql_like('u.firstname', ':s1', false) .
                " OR "  . $DB->sql_like('u.lastname',  ':s2', false) . ")";
            $term = '%' . $DB->sql_like_escape($search) . '%';
            $params['s1'] = $term;
            $params['s2'] = $term;
        }

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.description, u.picture, u.imagealt
              FROM {user} u
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
             WHERE ra.roleid IN (3, 4)
               AND u.deleted = 0 AND u.suspended = 0
               $wheresearch
          ORDER BY u.lastname, u.firstname";

        $total = count($DB->get_records_sql($sql, $params));
        $users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        $instructors = [];
        foreach ($users as $user) {
            $slug = self::slugify_user($user);

            // Contar cursos y estudiantes
            $coursecount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT ctx.instanceid) FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
              WHERE ra.userid = :uid AND ra.roleid IN (3, 4)",
                ['uid' => $user->id]
            );

            $userpicture       = new \user_picture($user);
            $userpicture->size = 100;

            $instructors[] = [
                'id'          => $user->id,
                'fullname'    => fullname($user),
                'slug'        => $slug,
                'bio'         => strip_tags($user->description ?? ''),
                'hasbio'      => !empty($user->description),
                'avatarurl'   => $userpicture->get_url($PAGE, $OUTPUT)->out(false),
                'profileurl'  => '/profesores/' . $slug,         // ✅ URL amigable
                'coursecount' => $coursecount,
                'rating'      => '4.5', // Placeholder fase 2
            ];
        }

        // Paginación básica
        $totalpages = $perpage > 0 ? ceil($total / $perpage) : 1;
        $pages = [];
        for ($i = 0; $i < $totalpages; $i++) {
            $pages[] = [
                'page'    => $i,
                'display' => $i + 1,
                'active'  => ($i === $page),
                'url'     => (new \moodle_url('/profesores', array_filter([
                    'q'    => $search ?: null,
                    'page' => $i ?: null,
                ])))->out(false),
            ];
        }
        $pagination = $totalpages > 1 ? [
            'pages'   => $pages,
            'hasprev' => $page > 0,
            'hasnext' => $page < ($totalpages - 1),
            'prevurl' => (new \moodle_url('/profesores', array_filter(['q' => $search ?: null, 'page' => $page - 1 ?: null])))->out(false),
            'nexturl' => (new \moodle_url('/profesores', array_filter(['q' => $search ?: null, 'page' => $page + 1])))->out(false),
        ] : [];

        return ['instructors' => $instructors, 'total' => $total, 'pagination' => $pagination];
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
                    //'profileurl'   => (new \moodle_url('/user/profile.php', ['id' => $user->id]))->out(false),
                    'profileurl'   => '/profesores/' . self::slugify_user($user),
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
     * Busca el userid de un instructor por su slug (generado de su nombre completo).
     */
    public function get_instructor_by_slug(string $slug): ?int {
        global $DB;

        // Obtener todos los usuarios con rol teacher/editingteacher en algún curso
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
              FROM {user} u
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
             WHERE ra.roleid IN (3, 4) AND u.deleted = 0 AND u.suspended = 0";

        $users = $DB->get_records_sql($sql);

        foreach ($users as $user) {
            if (self::slugify_user($user) === $slug) {
                return (int) $user->id;
            }
        }

        return null;
    }

    /**
     * Obtiene todos los datos del perfil de un instructor para su página pública.
     */
    public function get_instructor_profile(int $userid): ?array {

        global $DB, $OUTPUT, $PAGE;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0]);
        if (!$user) {
            return null;
        }

        // Verificar que es instructor en algún curso
        $sql = "SELECT COUNT(DISTINCT ctx.instanceid) AS coursecount
              FROM {role_assignments} ra
              JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
             WHERE ra.userid = :userid AND ra.roleid IN (3, 4)";
        $coursecount = $DB->count_records_sql($sql, ['userid' => $userid]);

        if ($coursecount === 0) {
            return null; // No es instructor
        }

        // Total de estudiantes
        $sql = "SELECT COUNT(DISTINCT ue.userid) AS students
              FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
              JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :teacherid
             WHERE ra.roleid IN (3, 4)";
        $studentcount = $DB->count_records_sql($sql, ['teacherid' => $userid]);

        // Avatar
        $userpicture       = new \user_picture($user);
        $userpicture->size = 200;

        // Slug del instructor
        $slug = self::slugify_user($user);

        // Cursos del instructor (para el grid)
        $sql = "SELECT DISTINCT c.id, c.fullname, c.summary, c.shortname
              FROM {course} c
              JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
              JOIN {role_assignments} ra ON ra.contextid = ctx.id
             WHERE ra.userid = :teacherid AND ra.roleid IN (3, 4)
               AND c.id != :siteid AND c.visible = 1
          ORDER BY c.timecreated DESC";
        $courserecords = $DB->get_records_sql($sql, ['teacherid' => $userid, 'siteid' => SITEID]);

        $courses = [];
        foreach ($courserecords as $record) {
            $courseslug = self::slugify($record->shortname ?: $record->fullname);
            $course     = new \core_course_list_element(get_course($record->id));
            $imageurl   = $this->get_course_image($course);
            $price      = $this->get_price($record->id);
            $summary    = strip_tags($record->summary);
            if (strlen($summary) > 100) {
                $summary = substr($summary, 0, 100) . '...';
            }

            $courses[] = [
                'id'         => $record->id,
                'fullname'   => $record->fullname,
                'summary'    => $summary,
                'imageurl'   => $imageurl,
                'hasimage'   => !empty($imageurl),
                'initials'   => strtoupper(substr($record->fullname, 0, 2)),
                'courseurl'  => '/cursos/' . $courseslug,       // ✅ URL amigable
                'is_free'    => $price['is_free'],
                'pricelabel' => $price['is_free']
                    ? get_string('free', 'local_catalog')
                    : $price['cost'] . ' ' . $price['currency'],
            ];
        }

        return [
            'id'           => $userid,
            'fullname'     => fullname($user),
            'slug'         => $slug,
            'bio'          => format_text($user->description ?? '', FORMAT_HTML),
            'hasbio'       => !empty($user->description),
            'avatarurl'    => $userpicture->get_url($PAGE, $OUTPUT)->out(false),
            'profileurl'   => '/profesores/' . $slug,           // ✅ URL amigable
            'coursecount'  => $coursecount,
            'studentcount' => $studentcount,
            'rating'       => '4.5', // Placeholder fase 2
            'reviewcount'  => 0,     // Placeholder fase 2
            'courses'      => $courses,
            'hascourses'   => !empty($courses),
            'allurl'       => (new \moodle_url('/profesores'))->out(false),
            // ── URLs de navegación ────────────────────────────────────────────────
            'homeurl'        => (new \moodle_url('/'))->out(false),
            'catalogurl'     => (new \moodle_url('/cursos'))->out(false),
            'searchaction'   => (new \moodle_url('/cursos'))->out(false),
            'mycoursesurl'   => (new \moodle_url('/mis-cursos'))->out(false),
            'loginurl'       => (new \moodle_url('/login/index.php'))->out(false),
            'logouturl'      => (new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
            'registerurl'    => (new \moodle_url('/registro'))->out(false),
            'instructorsurl' => (new \moodle_url('/profesores'))->out(false),
            'planurl'        => (new \moodle_url('/plan-personal'))->out(false),
            'compareurl'     => (new \moodle_url('/comparar-planes'))->out(false),
            'demourl'        => (new \moodle_url('/solicitar-demo'))->out(false),
            'teachurl'       => (new \moodle_url('/ensena-aqui'))->out(false),
            'termsurl'       => (new \moodle_url('/terminos'))->out(false),
            'privacyurl'     => (new \moodle_url('/privacidad'))->out(false),
        ];
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
    private function get_course_sections_anterior(int $courseid): array {
        $sections = [];

        try {
            $modinfo = get_fast_modinfo($courseid);
            $this->print_sql($modinfo);

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
                        if (!$cm->uservisible && !$cm->visible ) {
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

    private function get_course_sections(int $courseid): array {

        $sections_full    = [];  // todo (para el player sidebar)
        $sections_videos  = [];  // solo actividades con isvideo=true
        $sections_files   = [];  // solo actividades con isfile=true

        try {
            $modinfo      = get_fast_modinfo($courseid);
            $courseformat = course_get_format($courseid);

            foreach ($modinfo->get_section_info_all() as $sectionnum => $sectioninfo) {

                if ($sectionnum == 0 && empty($modinfo->sections[0])) {
                    continue;
                }

                $sectionname = $courseformat->get_section_name($sectioninfo);
                if (empty($sectionname)) {
                    $sectionname = get_string('section') . ' ' . $sectionnum;
                }

                $all_activities   = [];
                $video_activities = [];
                $file_activities  = [];

                if (isset($modinfo->sections[$sectionnum])) {
                    foreach ($modinfo->sections[$sectionnum] as $cmid) {
                        $cm = $modinfo->cms[$cmid];

                        if ((!$cm->uservisible && !$cm->visible) || $cm->modname === 'label') {
                            continue;
                        }

                        $detail = $this->get_activity_detail($cm);

                        $all_activities[] = $detail;

                        if (!empty($detail['isvideo'])) {
                            $video_activities[] = $detail;
                        }

                        if (!empty($detail['isfile'])) {
                            $file_activities[] = $detail;
                        }
                    }
                }

                // ── Sección completa (player sidebar) ────────────────────
                if (!empty($all_activities)) {
                    $sections_full[] = [
                        'name'          => $sectionname,
                        'num'           => $sectionnum,
                        'activitycount' => count($all_activities),
                        'activities'    => $all_activities,
                        'hasactivities' => true,
                        'isopen'        => ($sectionnum <= 1),
                        'progress_text' => '0 / ' . count($all_activities),
                    ];
                }

                // ── Solo videos (detalle del curso + player sidebar) ──────
                if (!empty($video_activities)) {
                    $sections_videos[] = [
                        'name'           => $sectionname,
                        'num'            => $sectionnum,
                        'activitycount'  => count($video_activities),
                        'activities'     => $video_activities,
                        'hasactivities'  => true,
                        'isopen'         => ($sectionnum <= 1),
                        'progress_text'  => '0 / ' . count($video_activities),
                    ];
                }

                // ── Solo ficheros (detalle del curso) ─────────────────────
                if (!empty($file_activities)) {
                    $sections_files[] = [
                        'name'          => $sectionname,
                        'num'           => $sectionnum,
                        'activitycount' => count($file_activities),
                        'activities'    => $file_activities,
                        'hasactivities' => true,
                        'isopen'        => ($sectionnum <= 1),
                    ];
                }
            }

        } catch (\Exception $e) {
            // Silently fail
        }

        return [
            // Para el sidebar del player (todo el contenido)
            'sections'          => $sections_full,
            'hassections'       => !empty($sections_full),
            'sectioncount'      => count($sections_full),

            // Para la pestaña "Videos" del detalle del curso
            'sections_videos'   => $sections_videos,
            'hassections_videos'=> !empty($sections_videos),
            'videocount'        => array_sum(array_column($sections_videos, 'activitycount')),

            // Para la pestaña "Recursos" del detalle del curso
            'sections_files'    => $sections_files,
            'hassections_files' => !empty($sections_files),
            'filecount'         => array_sum(array_column($sections_files, 'activitycount')),
        ];
    }

    /**
     * Wrapper público de get_course_sections() para course_player.
     */
    public function get_course_sections_public(int $courseid): array {
        return $this->get_course_sections($courseid);
    }

    /**
     * Instructores en formato simplificado para el player.
     */
    public function get_instructors_for_player(int $courseid): array {
        return $this->get_instructors_detail($courseid);
    }

    private function get_course_sections_actual(int $courseid): array {

        $sections = [];

        try {
            $modinfo     = get_fast_modinfo($courseid);
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

                        // Saltar ocultos y labels
                        if ((!$cm->uservisible && !$cm->visible) || $cm->modname === 'label') {
                            continue;
                        }

                        // Detalle enriquecido según tipo
                        $detail = $this->get_activity_detail($cm);

                        $activities[] = $detail;
                    }
                }

                if (!empty($activities) || $sectionnum == 0) {
                    $sections[] = [
                        'name'          => $sectionname,
                        'num'           => $sectionnum,
                        'activitycount' => count($activities),
                        'activities'    => $activities,
                        'hasactivities' => !empty($activities),
                        'isopen'        => ($sectionnum <= 1),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return $sections;
    }

    /**
     * Enriquece una actividad/recurso con datos específicos de su tipo.
     *
     * Accede a la tabla propia del módulo ({page}, {resource}, {url},
     * {assign}, {quiz}, {h5pactivity}…) para obtener metadatos relevantes
     * que la vista de detalle necesita mostrar al alumno.
     *
     * @param  \cm_info  $cm  Objeto course-module de get_fast_modinfo()
     * @return array          Array plano listo para Mustache
     */
    private function get_activity_detail(\cm_info $cm): array {

        global $DB;

        // ── Base común a todos los tipos ─────────────────────────────────
        $base = [
            'id'       => $cm->id,
            'name'     => $cm->name,
            'modname'  => $cm->modname,
            'visible'  => (bool) $cm->visible,
            'viewurl'  => (new \moodle_url('/mod/' . $cm->modname . '/view.php',
                ['id' => $cm->id]))->out(false),

            // Tipo legible
            'typename' => get_string('modulename', $cm->modname),

            // Icono (emoji rápido + clase CSS por si prefieres iconos reales)
            'icon'       => $this->get_activity_icon($cm->modname),
            'iconclass'  => 'mod-icon mod-' . $cm->modname,

            // Flags de tipo (útiles para {{#isfile}} etc. en Mustache)
            'ispage'     => false,
            'isfile'     => false,
            'isurl'      => false,
            'isquiz'     => false,
            'isassign'   => false,
            'isforum'    => false,
            'islesson'   => false,
            'ish5p'      => false,
            'isvideo'    => false,   // true si la URL/embed es un video
            'isother'    => false,

            // Metadatos extra (vacíos por defecto)
            'fileurl'        => '',
            'filesize'       => '',
            'filetype'       => '',
            'filename'       => '',
            'videourl'       => '',
            'videoembedurl'  => '',
            'externalurl'    => '',
            'duedate'        => '',
            'hasduedate'     => false,
            'timelimit'      => '',
            'hastimelimit'   => false,
            'description'    => '',
            'hasdescription' => false,
            'previewurl'     => '',   // URL de imagen de previsualización
            'haspreview'     => false,
        ];

        // ── Detalle por tipo de módulo ───────────────────────────────────
        switch ($cm->modname) {

            // ── Página (contenido HTML, puede tener video embebido) ───────
            case 'page':
                $base['ispage'] = true;
                $record = $DB->get_record('page', ['id' => $cm->instance],
                    'id, intro, content', IGNORE_MISSING);
                if ($record) {
                    // Descripción corta
                    if (!empty($record->intro)) {
                        $intro = strip_tags($record->intro);
                        $base['description']    = mb_strimwidth($intro, 0, 160, '…');
                        $base['hasdescription'] = true;
                    }
                    // Detectar video embebido dentro del contenido HTML
                    $videourl = $this->extract_video_url($record->content);
                    if ($videourl) {
                        $base['isvideo']        = true;
                        $base['videourl']       = $videourl['url'];
                        $base['videoembedurl']  = $videourl['embed'];
                        $base['haspreview']     = !empty($videourl['thumb']);
                        $base['previewurl']     = $videourl['thumb'] ?? '';
                    }
                }
                break;

            // ── Fichero (recurso descargable) ─────────────────────────────
            case 'resource':
                $base['isfile'] = true;
                $record = $DB->get_record('resource', ['id' => $cm->instance],
                    'id, intro', IGNORE_MISSING);
                if ($record && !empty($record->intro)) {
                    $base['description']    = mb_strimwidth(strip_tags($record->intro), 0, 160, '…');
                    $base['hasdescription'] = !empty($record->intro);
                }
                // Obtener el fichero real de la tabla {files}
                $fileinfo = $this->get_module_file($cm);
                if ($fileinfo) {
                    $base['fileurl']  = $fileinfo['url'];
                    $base['filename'] = $fileinfo['filename'];
                    $base['filesize'] = $this->format_filesize($fileinfo['filesize']);
                    $base['filetype'] = $fileinfo['mimetype'];

                    // Si el fichero es un video (mp4, webm…)
                    if ($this->is_video_mimetype($fileinfo['mimetype'])) {
                        $base['isvideo']    = true;
                        $base['videourl']   = $fileinfo['url'];
                    }
                }
                break;

            // ── URL externa (puede ser YouTube, Vimeo, etc.) ──────────────
            case 'url':
                $base['isurl'] = true;
                $record = $DB->get_record('url', ['id' => $cm->instance],
                    'id, externalurl, intro', IGNORE_MISSING);
                if ($record) {
                    $base['externalurl']    = $record->externalurl;
                    $base['description']    = mb_strimwidth(strip_tags($record->intro ?? ''), 0, 160, '…');
                    $base['hasdescription'] = !empty($record->intro);

                    // Detectar si la URL es un video conocido
                    $videourl = $this->extract_video_url($record->externalurl);
                    if ($videourl) {
                        $base['isvideo']       = true;
                        $base['videourl']      = $record->externalurl;
                        $base['videoembedurl'] = $videourl['embed'];
                        $base['haspreview']    = !empty($videourl['thumb']);
                        $base['previewurl']    = $videourl['thumb'] ?? '';
                    }
                }
                break;

            // ── Cuestionario ─────────────────────────────────────────────
            case 'quiz':
                $base['isquiz'] = true;
                $record = $DB->get_record('quiz', ['id' => $cm->instance],
                    'id, intro, timeopen, timeclose, timelimit', IGNORE_MISSING);
                if ($record) {
                    $base['description']    = mb_strimwidth(strip_tags($record->intro ?? ''), 0, 160, '…');
                    $base['hasdescription'] = !empty($record->intro);
                    if (!empty($record->timeclose)) {
                        $base['duedate']    = userdate($record->timeclose,
                            get_string('strftimedatetimeshort', 'langconfig'));
                        $base['hasduedate'] = true;
                    }
                    if (!empty($record->timelimit)) {
                        $base['timelimit']    = format_time($record->timelimit);
                        $base['hastimelimit'] = true;
                    }
                }
                break;

            // ── Tarea ─────────────────────────────────────────────────────
            case 'assign':
                $base['isassign'] = true;
                $record = $DB->get_record('assign', ['id' => $cm->instance],
                    'id, intro, duedate', IGNORE_MISSING);
                if ($record) {
                    $base['description']    = mb_strimwidth(strip_tags($record->intro ?? ''), 0, 160, '…');
                    $base['hasdescription'] = !empty($record->intro);
                    if (!empty($record->duedate)) {
                        $base['duedate']    = userdate($record->duedate,
                            get_string('strftimedatetimeshort', 'langconfig'));
                        $base['hasduedate'] = true;
                    }
                }
                break;

            // ── Foro ──────────────────────────────────────────────────────
            case 'forum':
                $base['isforum'] = true;
                $record = $DB->get_record('forum', ['id' => $cm->instance],
                    'id, intro', IGNORE_MISSING);
                if ($record && !empty($record->intro)) {
                    $base['description']    = mb_strimwidth(strip_tags($record->intro), 0, 160, '…');
                    $base['hasdescription'] = true;
                }
                break;

            // ── Lección ───────────────────────────────────────────────────
            case 'lesson':
                $base['islesson'] = true;
                $record = $DB->get_record('lesson', ['id' => $cm->instance],
                    'id, intro', IGNORE_MISSING);
                if ($record && !empty($record->intro)) {
                    $base['description']    = mb_strimwidth(strip_tags($record->intro), 0, 160, '…');
                    $base['hasdescription'] = true;
                }
                break;

            // ── H5P ───────────────────────────────────────────────────────
            case 'h5pactivity':
            case 'h5p':
                $base['ish5p'] = true;
                $table  = ($cm->modname === 'h5pactivity') ? 'h5pactivity' : 'hvp';
                $record = $DB->get_record($table, ['id' => $cm->instance],
                    'id, intro', IGNORE_MISSING);
                if ($record && !empty($record->intro)) {
                    $base['description']    = mb_strimwidth(strip_tags($record->intro), 0, 160, '…');
                    $base['hasdescription'] = true;
                }
                break;

            default:
                $base['isother'] = true;
                break;
        }

        return $base;
    }

    /**
     * Devuelve el emoji/icono según el tipo de módulo.
     */
    private function get_activity_icon(string $modname): string {
        $icons = [
            'page'        => '📝',
            'url'         => '🔗',
            'resource'    => '📎',
            'quiz'        => '📋',
            'forum'       => '💬',
            'assign'      => '📤',
            'lesson'      => '📖',
            'h5pactivity' => '🎮',
            'h5p'         => '🎮',
            'folder'      => '📁',
            'book'        => '📚',
            'choice'      => '☑️',
            'survey'      => '📊',
            'workshop'    => '🔧',
        ];
        return $icons[$modname] ?? '📄';
    }

    /**
     * Obtiene el fichero principal de un módulo resource/folder.
     * Devuelve ['url', 'filename', 'filesize', 'mimetype'] o null.
     */
    private function get_module_file(\cm_info $cm): ?array {

        $fs      = get_file_storage();
        $context = \context_module::instance($cm->id);

        $files = $fs->get_area_files(
            $context->id,
            'mod_resource',       // component
            'content',            // filearea
            false,                // itemid
            'sortorder DESC, id ASC',
            false                 // no directorios
        );

        foreach ($files as $file) {
            if ($file->get_filename() === '.') {
                continue;  // Saltar entrada raíz
            }
            $url = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            return [
                'url'      => $url->out(false),
                'filename' => $file->get_filename(),
                'filesize' => $file->get_filesize(),
                'mimetype' => $file->get_mimetype(),
            ];
        }
        return null;
    }

    /**
     * Extrae la URL de video de un string HTML o URL directa.
     * Soporta: YouTube, Vimeo, y tags <video src=...>
     *
     * Devuelve ['url', 'embed', 'thumb'] o null.
     */
    private function extract_video_url(string $content): ?array {

        if (empty($content)) {
            return null;
        }

        // ── YouTube ───────────────────────────────────────────────────────
        // Formatos: watch?v=ID, youtu.be/ID, embed/ID, /shorts/ID
        if (preg_match(
            '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $content, $m
        )) {
            $id = $m[1];
            return [
                'url'   => 'https://www.youtube.com/watch?v=' . $id,
                'embed' => 'https://www.youtube.com/embed/' . $id . '?rel=0&modestbranding=1',
                'thumb' => 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg',
            ];
        }

        // ── Vimeo ─────────────────────────────────────────────────────────
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $content, $m)) {
            $id = $m[1];
            return [
                'url'   => 'https://vimeo.com/' . $id,
                'embed' => 'https://player.vimeo.com/video/' . $id . '?title=0&byline=0',
                'thumb' => '',  // Vimeo requiere API para la miniatura
            ];
        }

        // ── Tag <video src="..."> dentro del HTML de una page ─────────────
        if (preg_match('/<video[^>]+src=["\']([^"\']+)["\']/', $content, $m)) {
            return [
                'url'   => $m[1],
                'embed' => $m[1],
                'thumb' => '',
            ];
        }

        // ── Pluginfile de Moodle (video subido al módulo page) ────────────
        if (preg_match('/pluginfile\.php[^"\']+\.(mp4|webm|ogg)/i', $content, $m)) {
            $url = $m[0];
            return [
                'url'   => $url,
                'embed' => $url,
                'thumb' => '',
            ];
        }

        return null;
    }

    /**
     * Comprueba si un mimetype corresponde a un video.
     */
    private function is_video_mimetype(string $mimetype): bool {
        return str_starts_with($mimetype, 'video/');
    }

    /**
     * Formatea el tamaño de un fichero en KB/MB legibles.
     */
    private function format_filesize(int $bytes): string {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }
        return $bytes . ' B';
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

            $slug = catalog_manager::slugify($record->shortname ?: $record->fullname);

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
                // 'url'       => (new \moodle_url('/local/catalog/course.php', ['id' => $record->id]))->out(false),
                'url' =>    '/cursos/' . $slug,
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

            $slug = catalog_manager::slugify($record->shortname ?: $record->fullname);

            $course = new \core_course_list_element(get_course($record->id));
            $imageurl = $this->get_course_image($course);
            $price = $this->get_price($record->id);

            $courses[] = [
                'id'        => $record->id,
                'fullname'  => $record->fullname,
                'imageurl'  => $imageurl,
                'hasimage'  => !empty($imageurl),
                'initials'  => strtoupper(substr($record->fullname, 0, 2)),
                //'url'       => (new \moodle_url('/local/catalog/course.php', ['id' => $record->id]))->out(false),
                'url' => '/cursos/' . $slug,
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
                'url'  => (new \moodle_url('/cursos', ['tag' => $tag->rawname]))->out(false), // ✅
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

    public function debug_sql($sql,$params)
    {
        // ── DEBUG: SQL con parámetros sustituidos ──────────────────────────────
        $debugsql = $sql;
        // Ordenar por longitud descendente para evitar que ':cat0' sustituya parte de ':cat00'
        $debugparams = $params;
        uksort($debugparams, fn($a, $b) => strlen($b) - strlen($a));
        foreach ($debugparams as $key => $value) {
            $replace = is_string($value)
                ? "'" . addslashes($value) . "'"
                : (is_null($value) ? 'NULL' : (string)$value);
            $debugsql = str_replace(':' . $key, $replace, $debugsql);
        }
        echo "<pre style='background:#1e1e1e;color:#9cdcfe;padding:12px;font-size:12px;'>";
        echo "── PARAMS ──\n";
        print_r($params);
        echo "\n── SQL FINAL ──\n";
        echo htmlspecialchars($debugsql);
        echo "</pre>";
        // ── FIN DEBUG ─────────────────────────────────────────────────────────
    }

    public function print_sql($value)
    {
        echo "<pre>";
        print_r($value);
        echo "</pre>";
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


        // filtro por course_level
        $joincustomfield = '';
        if (!empty($filters['level'])) {

            // Leer el campo y su configdata para resolver el índice correcto
            $levelfield = $DB->get_record(
                'customfield_field',
                ['shortname' => 'course_level'],
                'id, type, configdata',
                IGNORE_MISSING
            );

            // Valor que finalmente compararemos contra mcd.value en la BD
            $levelval = null;

            if ($levelfield) {

                if ($levelfield->type === 'select') {

                    // Parsear las opciones del configdata
                    $config  = json_decode($levelfield->configdata, true);
                    $options = [];

                    if (!empty($config['options'])) {
                        $raw     = str_replace("\r\n", "\n", $config['options']);
                        $options = array_values(array_filter(
                            array_map('trim', explode("\n", $raw)),
                            fn($o) => $o !== ''
                        ));
                    }

                    // $filters['level'] puede llegar como índice ("0") o como
                    // texto ("Principiante") — normalizamos a índice siempre.
                    if (is_numeric($filters['level'])) {
                        // Ya viene como índice — verificar que existe en el array
                        $idx = (int)($filters['level']);
                        if (isset($options[$idx])) {
                            $levelval = (string) $idx;
                        }
                    } else {
                        // Viene como texto — buscar su posición en el array
                        $idx = array_search(trim($filters['level']), $options, true);
                        if ($idx !== false) {
                            $levelval = (string) $idx;
                        }
                    }

                } else {
                    // Campo tipo text → mcd.value guarda el texto directamente
                    $levelval = trim($filters['level']);
                }
            }

            // Solo añadir el JOIN si pudimos resolver un valor válido
            if ($levelval !== null && $levelval !== '') {
                $joincustomfield = "JOIN {customfield_field} mcf ON mcf.shortname = 'course_level'
                                    JOIN {customfield_data}  mcd ON mcd.instanceid = c.id
                                                                AND mcd.fieldid    = mcf.id";
                $where[]              = 'mcd.value = :levelval';
                $params['levelval']   = $levelval;
            }
        }

        // ── Filtro por precio ─────────────────────────────────────────────────
        $joinprice = '';

        if (!empty($filters['price'])) {

            if ($filters['price'] === 'paid') {
                // PAID: solo cursos que tienen un enrol fee activo con cost > 0
                $joinprice       = "JOIN {enrol} eprice ON eprice.courseid = c.id
                                                       AND eprice.enrol    = 'fee'
                                                       AND eprice.status   = 0
                                                       AND eprice.cost     > 0";

            } else if ($filters['price'] === 'free') {
                // FREE: cursos que NO tienen ningún enrol fee activo con cost > 0
                // Técnica anti-join: LEFT JOIN + WHERE IS NULL
                $joinprice       = "LEFT JOIN {enrol} eprice ON eprice.courseid = c.id
                                                            AND eprice.enrol    = 'fee'
                                                            AND eprice.status   = 0
                                                            AND eprice.cost     > 0";
                $where[]         = 'eprice.id IS NULL';
            }
        }

        // where general
        $wheresql = implode(' AND ', $where);

        // query contador de registros
        $countsql = "SELECT COUNT(DISTINCT c.id) 
                    FROM {course} c 
                            $jointag 
                            $joincustomfield
                    $joinprice
                    WHERE $wheresql";
        $total = $DB->count_records_sql($countsql, $params);

        // query resultado de registros
        $sql = "SELECT DISTINCT c.id, c.fullname, c.summary, c.category, c.timecreated
                  FROM {course} c 
                  $jointag 
                  $joincustomfield
                  $joinprice
                 WHERE $wheresql
              ORDER BY c.timecreated DESC";


        $courserecords = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        $courses = [];

        foreach ($courserecords as $record) {

            $coursedata = $this->enrich_course_card($record);

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

        /*
        echo "<pre>";
        //print_r($record);
        print_r($course);
        echo "</pre>";
        */


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

        $slug = catalog_manager::slugify($record->shortname ?: $record->fullname);

        return [
            'id'              => $record->id,
            'fullname'        => $record->fullname,
            'summary'         => $summary,
            'imageurl'        => $imageurl,
            'hasimage'        => !empty($imageurl),
            'initials'        => strtoupper(substr($record->fullname, 0, 2)),
            'courseurl'       => '/cursos/' . $slug,
            //'courseurl'     => (new \moodle_url('/local/catalog/course.php', ['id' => $record->id]))->out(false),
            'learnurl'        => '/cursos/' . $slug . '/ver',
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


// =============================================================================
// PATCH — get_filter_data() en catalog_manager.php
//
// Sustituye el método completo. Añade el campo 'param' a cada item
// de filtro para que el JS sepa a qué parámetro GET corresponde
// cada checkbox, sin necesidad de leer el texto del label.
//
// Cambios respecto al original:
//   categories → añade 'param' => 'cat'
//   levels     → añade 'param' => 'level'  (ya tenía 'value')
//   prices     → añade 'param' => 'price'  (ya tenía 'value')
//   tags       → añade 'param' => 'tag', 'value' => $rec->rawname
// =============================================================================

    public function get_filter_data(array $filters): array {

        global $DB;

        // ── Categorías ────────────────────────────────────────────────────────
        $categories = [];
        foreach (\core_course_category::get_all() as $cat) {
            if ($cat->id == 0) {
                continue;
            }
            $count = $cat->get_courses_count();
            if ($count > 0) {
                $categories[] = [
                    'id'     => $cat->id,
                    'name'   => $cat->name,
                    'count'  => $count,
                    'param'  => 'cat',                  // ← nuevo
                    'value'  => (string) $cat->id,      // ← nuevo
                    'active' => ($cat->id == $filters['category']),
                    'url'    => (new \moodle_url('/cursos',
                        array_merge($this->clean_filters($filters), ['cat' => $cat->id])))->out(false),
                ];
            }
        }

        // ── Niveles ───────────────────────────────────────────────────────────
        //
        // Consulta los valores DISTINTOS que existen en la BD para el campo
        // `course_level`, junto con cuántos cursos visibles tiene cada valor.
        //
        // Tablas:
        //   {customfield_field}  → shortname = 'course_level'  → nos da el fieldid+`+

        //   {customfield_data}   → value = el nivel guardado    → uno por curso
        //   {course}             → c.visible = 1                → excluimos ocultos
        //

        $levels = [];

        $levelfield = $DB->get_record(
            'customfield_field',
            ['shortname' => 'course_level'],
            'id, configdata, type',
            IGNORE_MISSING
        );


        if ($levelfield && !empty($levelfield->configdata)) {

            $config  = json_decode($levelfield->configdata, true);
            $options = [];

            // Las opciones están separadas por \r\n (o \n según el SO)
            if (!empty($config['options'])) {
                $raw = str_replace("\r\n", "\n", $config['options']);
                $options = array_filter(
                    array_map('trim', explode("\n", $raw)),
                    fn($o) => $o !== ''
                );
                $options = array_values($options); // reindexar → 0,1,2...
            }

            // Para cada opción, contar cuántos cursos la tienen asignada.
            //
            // Si el campo es tipo 'select', customfield_data.value guarda el
            // índice entero de la opción (0, 1, 2...).
            // Si es tipo 'text', value guarda el texto directamente.
            //
            // Hacemos un LEFT JOIN para que las opciones con 0 cursos
            // también aparezcan en el sidebar (mejor UX que no mostrarlas).

            /*
            echo "<pre>";
            print_r($options);
            echo "<pre>";
            */

            $isselect = ($levelfield->type === 'select');

            foreach ($options as $index => $label) {

                // El valor que viaja en la URL y se compara en get_courses():
                //   select → índice como string ("0", "1", "2"...)
                //   text   → el texto literal
                $urlvalue = $isselect ? (string) $index+1 : $label;

                // Contar cursos visibles con este nivel
                if ($isselect) {
                    // customfield_data.value = índice entero guardado como string
                    $count = (int) $DB->count_records_sql(
                        "SELECT COUNT(c.id)
                           FROM {customfield_data} cd
                           JOIN {course} c ON c.id = cd.instanceid
                          WHERE cd.fieldid = :fid
                            AND cd.value   = :val
                            AND c.visible  = 1
                            AND c.id != :sid",
                        [
                            'fid' => $levelfield->id,
                            'val' => (string) ($index+1),
                            'sid' => SITEID,
                        ]
                    );
                } else {
                    // customfield_data.value = texto literal
                    $count = (int) $DB->count_records_sql(
                        "SELECT COUNT(c.id)
                           FROM {customfield_data} cd
                           JOIN {course} c ON c.id = cd.instanceid
                          WHERE cd.fieldid = :fid
                            AND cd.value = :val
                            AND c.visible = 1
                            AND c.id != :sid",
                        [
                            'fid' => $levelfield->id,
                            'val' => $label,
                            'sid' => SITEID,
                        ]
                    );
                }

                $levels[] = [
                    'name'   => $label,
                    'count'  => $count,
                    'param'  => 'level',
                    'value'  => $urlvalue,
                    'active' => ($urlvalue === $filters['level']),
                    'url'    => (new \moodle_url('/cursos',
                        array_merge(
                            $this->clean_filters($filters),
                            ['level' => $urlvalue]
                        )))->out(false),
                ];
                /*
                echo "<pre>";
                print_r($levels);
                echo "<pre>";
                */
            }
        }

        // ── Precios ───────────────────────────────────────────────────────────


// =============================================================================
// PATCH — bloque PRICES dentro de get_filter_data()
// Sustituye el array hardcodeado por contadores reales desde {enrol}
// =============================================================================

        // ── Precios — contadores reales desde {enrol} ─────────────────────────
        //
        // Un curso es DE PAGO si tiene al menos un enrol de tipo 'fee'
        // con status=0 (activo) y cost > 0.
        //
        // Un curso es GRATIS si NO tiene ningún enrol así.
        //
        // Usamos COUNT(DISTINCT c.id) para no contar el mismo curso
        // varias veces si tuviera múltiples métodos de pago.

        $total_visible = (int)$DB->count_records_select(
            'course',
            'visible = 1 AND id != :sid',
            ['sid' => SITEID]
        );

        $sql_paid = "SELECT COUNT(DISTINCT e.courseid)
                       FROM {enrol} e
                       JOIN {course} c ON c.id = e.courseid
                      WHERE e.enrol  = 'fee'
                        AND e.status = 0
                        AND e.cost   > 0
                        AND c.visible = 1
                        AND c.id     != :siteid";

        $paid_count = (int)$DB->count_records_sql($sql_paid, ['siteid' => SITEID]);
        $free_count = $total_visible - $paid_count;

        $prices = [];

        // Solo mostrar la opción si hay cursos en esa categoría
        if ($free_count > 0) {
            $prices[] = [
                'name' => get_string('free', 'local_catalog'),
                'count' => $free_count,
                'param' => 'price',
                'value' => 'free',
                'active' => ($filters['price'] === 'free'),
                'url' => (new \moodle_url('/cursos',
                    array_merge($this->clean_filters($filters), ['price' => 'free'])))->out(false),
            ];
        }

        if ($paid_count > 0) {
            $prices[] = [
                'name' => get_string('paid', 'local_catalog'),
                'count' => $paid_count,
                'param' => 'price',
                'value' => 'paid',
                'active' => ($filters['price'] === 'paid'),
                'url' => (new \moodle_url('/cursos',
                    array_merge($this->clean_filters($filters), ['price' => 'paid'])))->out(false),
            ];
        }

        // Nota: si no hay cursos de pago (como ocurre ahora en desarrollo),
        // $prices solo contendrá la opción 'free' con todos los cursos.
        // Cuando actives enrol_fee y pongas precio a algún curso,
        // aparecerá automáticamente la opción 'paid'.

        /*
        $prices = [
            [
                'name'   => get_string('free', 'local_catalog'),
                'param'  => 'price',                    // ← nuevo
                'value'  => 'free',
                'active' => ($filters['price'] === 'free'),
                'url'    => (new \moodle_url('/cursos',
                    array_merge($this->clean_filters($filters), ['price' => 'free'])))->out(false),
            ],
            [
                'name'   => get_string('paid', 'local_catalog'),
                'param'  => 'price',                    // ← nuevo
                'value'  => 'paid',
                'active' => ($filters['price'] === 'paid'),
                'url'    => (new \moodle_url('/cursos',
                    array_merge($this->clean_filters($filters), ['price' => 'paid'])))->out(false),
            ],
        ];
        */


        // ── Tags ──────────────────────────────────────────────────────────────
        $tags = [];
        $sql = "SELECT t.id, t.rawname, COUNT(ti.id) as count
                  FROM {tag} t
                  JOIN {tag_instance} ti ON ti.tagid    = t.id
                 WHERE ti.itemtype  = 'course'
                   AND ti.component = 'core'
              GROUP BY t.id, t.rawname
              ORDER BY count DESC
                 LIMIT 20";

        foreach ($DB->get_records_sql($sql) as $rec) {
            $tags[] = [
                'name'   => $rec->rawname,
                'count'  => $rec->count,
                'param'  => 'tag',                      // ← nuevo
                'value'  => $rec->rawname,              // ← nuevo
                'active' => ($rec->rawname === $filters['tag']),
                'url'    => (new \moodle_url('/cursos',
                    array_merge($this->clean_filters($filters), ['tag' => $rec->rawname])))->out(false),
            ];
        }

        return [
            'categories' => $categories,
            'levels'     => $levels,
            'prices'     => $prices,
            'tags'       => $tags,
        ];
    }

    public function get_filter_data_old(array $filters): array {

        global $DB;

        $categories = [];
        foreach (\core_course_category::get_all() as $cat) {
            if ($cat->id == 0) continue;
            $count = $cat->get_courses_count();
            if ($count > 0) {
                $categories[] = [
                    'id' => $cat->id, 'name' => $cat->name, 'count' => $count,
                    'active' => ($cat->id == $filters['category']),
                    'url' => (new \moodle_url('/cursos',
                        array_merge($this->clean_filters($filters), ['cat' => $cat->id])))->out(false),
                ];
            }
        }

        $levels = [];
        foreach (['Principiante', 'Intermedio', 'Avanzado', 'Todos los niveles'] as $name) {
            $levels[] = [
                'value' => strtolower($name), 'name' => $name,
                'active' => (strtolower($name) === $filters['level']),
                'url' => (new \moodle_url('/cursos',
                    array_merge($this->clean_filters($filters), ['level' => strtolower($name)])))->out(false),
            ];
        }

        $prices = [
            ['value' => 'free', 'name' => get_string('free', 'local_catalog'),
             'active' => ($filters['price'] === 'free'),
             'url' => (new \moodle_url('/cursos',
                 array_merge($this->clean_filters($filters), ['price' => 'free'])))->out(false)],
            ['value' => 'paid', 'name' => get_string('paid', 'local_catalog'),
             'active' => ($filters['price'] === 'paid'),
             'url' => (new \moodle_url('/cursos',
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
                'url' => (new \moodle_url('/cursos',
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
                'url' => (new \moodle_url('/cursos', array_merge($clean, ['page' => $i])))->out(false),
            ];
        }

        return [
            'pages' => $pages,
            'hasprev' => $page > 0,
            'hasnext' => $page < ($totalpages - 1),
            'prevurl' => (new \moodle_url('/cursos', array_merge($clean, ['page' => $page - 1])))->out(false),
            'nexturl' => (new \moodle_url('/cursos', array_merge($clean, ['page' => $page + 1])))->out(false),
        ];
    }

    /**
     * Genera un slug URL-friendly a partir del nombre del curso.
     */
    public static function slugify(string $text): string {
        $text = strtolower($text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    /**
     * Genera un slug a partir del nombre completo de un usuario.
     * Juan García Martínez → juan-garcia-martinez
     */
    public static function slugify_user(\stdClass $user): string {
        return self::slugify(fullname($user));
    }

    /**
     * Busca un curso por su slug (shortname slugificado).
     */
    public function get_course_by_slug(string $slug): ?int {

        global $DB;

        // Buscar por shortname exacto primero
        $courses = $DB->get_records('course', ['visible' => 1], '', 'id, shortname, fullname');

        foreach ($courses as $course) {
            if ($course->id == SITEID) continue;
            if (self::slugify($course->shortname) === $slug ||
                self::slugify($course->fullname) === $slug) {
                return (int) $course->id;
            }
        }

        return null;
    }

    public function get_navbar_data(): array {

        global $USER, $OUTPUT, $PAGE, $CFG;

        $isloggedin = isloggedin() && !isguestuser();

        // Avatar URL
        $avatarurl = '';
        if ($isloggedin) {
            $userpic = new \user_picture($USER);
            $userpic->size = 64;
            $avatarurl = $userpic->get_url($PAGE, $OUTPUT)->out(false);
        }

        // Categorías de primer nivel para el menú Explorar
        $categories = [];
        foreach (\core_course_category::get_all() as $cat) {
            if ($cat->depth == 1 && $cat->get_courses_count() > 0) {
                $categories[] = [
                    'id'   => $cat->id,
                    'name' => $cat->name,
                    'url'  => (new \moodle_url('/cursos', ['cat' => $cat->id]))->out(false),
                ];
            }
            if (count($categories) >= 8) break; // Max 8 categorías en el menú
        }

        // Tags populares para el menú Explorar
        global $DB;
        $tagsql = "SELECT t.id, t.rawname, COUNT(ti.id) as cnt
                 FROM {tag} t JOIN {tag_instance} ti ON ti.tagid = t.id
                WHERE ti.itemtype = 'course' AND ti.component = 'core'
             GROUP BY t.id, t.rawname ORDER BY cnt DESC LIMIT 8";
        $explore_tags = [];
        foreach ($DB->get_records_sql($tagsql) as $tag) {
            $explore_tags[] = [
                'name' => $tag->rawname,
                'url'  => (new \moodle_url('/cursos', ['tag' => $tag->rawname]))->out(false),
            ];
        }

        // Items extra del menú Explorar (desde admin settings)
        $extra_raw   = get_config('local_catalog', 'navbar_explore_extra');
        $extra_items = [];
        if ($extra_raw) {
            $parsed = json_decode($extra_raw, true);
            if (is_array($parsed)) {
                $extra_items = $parsed;
            }
        }

        return [
            // Estado usuario
            'isloggedin'     => $isloggedin,
            'username'       => $isloggedin ? fullname($USER) : '',
            'firstname'      => $isloggedin ? $USER->firstname : '',
            'useremail'      => $isloggedin ? $USER->email : '',
            'useravatarurl'  => $avatarurl,

            // URLs de usuario
            'mycoursesurl'   => (new \moodle_url('/mis-cursos'))->out(false),
            'profileurl'     => $isloggedin ? (new \moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false) : '',
            'editprofileurl' => $isloggedin ? (new \moodle_url('/user/edit.php', ['id' => $USER->id]))->out(false) : '',
            'logouturl'      => (new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
            'loginurl'       => (new \moodle_url('/login/index.php'))->out(false),
            'registerurl'    => (new \moodle_url('/registro'))->out(false),

            // URLs de navegación
            'homeurl'        => (new \moodle_url('/'))->out(false),
            'catalogurl'     => (new \moodle_url('/cursos'))->out(false),
            'searchaction'   => (new \moodle_url('/cursos'))->out(false),
            'searchquery'    => optional_param('q', '', PARAM_TEXT),

            // Identidad visual (desde admin settings)
            'logourl'        => get_config('local_catalog', 'site_logo_url') ?: '',
            'sitename'       => get_config('local_catalog', 'site_name') ?: get_string('catalog', 'local_catalog'),

            // URLs navbar configurables
            'personalplanurl'=> get_config('local_catalog', 'navbar_personalplan_url') ?: '#',
            'businessurl'    => get_config('local_catalog', 'navbar_business_url') ?: '#',
            'teachurl'       => get_config('local_catalog', 'navbar_teach_url') ?: '#',

            // Menú Explorar
            'explore_categories' => $categories,
            'explore_tags'       => $explore_tags,
            'explore_extra'      => !empty($extra_items) ? ['items' => $extra_items] : false,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // get_home_data()
    // Datos para la página home (secciones dinámicas)
    // ─────────────────────────────────────────────────────────────────────────────
    /*
    public function get_home_data(): array {
        global $DB, $USER;

        $isloggedin = isloggedin() && !isguestuser();

        // --- Stats globales ---
        $total_courses     = $DB->count_records_select('course', 'id != :sid AND visible = 1', ['sid' => SITEID]);
        $total_students    = $DB->count_records_select('user', 'deleted = 0 AND suspended = 0 AND id != :guest', ['guest' => 1]);
        $total_instructors = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT userid) FROM {role_assignments} WHERE roleid IN (3,4)"
        );

        // --- Hero ---
        $hero_buttons_raw = get_config('local_catalog', 'home_hero_buttons');
        $hero_buttons_arr = $hero_buttons_raw ? json_decode($hero_buttons_raw, true) : [];

        // --- Features (propuestas de valor) ---
        $features_raw = get_config('local_catalog', 'home_features');
        $features_arr = $features_raw ? json_decode($features_raw, true) : [];

        // --- Empresas ---
        $companies_raw = get_config('local_catalog', 'home_companies');
        $companies_arr = $companies_raw ? json_decode($companies_raw, true) : [];

        // --- Testimonios ---
        $testimonials_raw = get_config('local_catalog', 'home_testimonials');
        $testimonials_arr = [];
        if ($testimonials_raw) {
            $parsed = json_decode($testimonials_raw, true);
            if (is_array($parsed)) {
                foreach ($parsed as $t) {
                    $rating = (int)($t['rating'] ?? 5);
                    $t['stars']   = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                    $t['initials'] = strtoupper(substr($t['name'] ?? 'U', 0, 2));
                    $t['hasavatar'] = !empty($t['avatar']);
                    $testimonials_arr[] = $t;
                }
            }
        }

        // --- Cursos en tendencia (los más matriculados, últimos 30 días) ---
        $trending_sql = "
        SELECT c.id, COUNT(ue.userid) AS enrolled
          FROM {course} c
          JOIN {enrol} e ON e.courseid = c.id
          JOIN {user_enrolments} ue ON ue.enrolid = e.id
         WHERE c.id != :siteid AND c.visible = 1
           AND ue.timecreated > :since
      GROUP BY c.id
      ORDER BY enrolled DESC
         LIMIT 10";
        $since       = time() - (30 * 24 * 3600);
        $trending_ids = $DB->get_records_sql($trending_sql, ['siteid' => SITEID, 'since' => $since]);

        // Fallback: si no hay matriculaciones recientes, coger los últimos cursos
        if (empty($trending_ids)) {
            $trending_ids = $DB->get_records_select('course',
                'id != :sid AND visible = 1', ['sid' => SITEID],
                'timecreated DESC', 'id', 0, 10);
        }

        $trending = [];
        foreach ($trending_ids as $rec) {
            $card = $this->enrich_course_card($DB->get_record('course', ['id' => $rec->id]));
            if ($card) $trending[] = $card;
        }

        // --- Tags populares ---
        $tagsql = "SELECT t.id, t.rawname, COUNT(ti.id) AS cnt
                 FROM {tag} t JOIN {tag_instance} ti ON ti.tagid = t.id
                WHERE ti.itemtype = 'course' AND ti.component = 'core'
             GROUP BY t.id, t.rawname ORDER BY cnt DESC LIMIT 20";
        $popular_tags = [];
        foreach ($DB->get_records_sql($tagsql) as $tag) {
            $popular_tags[] = [
                'name'  => $tag->rawname,
                'count' => $tag->cnt,
                'url'   => (new \moodle_url('/cursos', ['tag' => $tag->rawname]))->out(false),
            ];
        }

        // --- Búsquedas recientes del usuario ---
        $recent_searches = [];
        $recent_search_courses = [];
        if ($isloggedin) {
            $pref = get_user_preferences('local_catalog_recent_searches', '', $USER->id);
            if ($pref) {
                $searches = json_decode($pref, true) ?: [];
                foreach (array_slice($searches, 0, 6) as $q) {
                    $recent_searches[] = [
                        'query' => $q,
                        'url'   => (new \moodle_url('/cursos', ['q' => $q]))->out(false),
                    ];
                }
                // Cursos relacionados con la última búsqueda
                if (!empty($searches[0])) {
                    $filters = ['search' => $searches[0], 'category' => 0, 'price' => '', 'level' => '', 'tag' => ''];
                    $res = $this->get_courses($filters, 0, 5);
                    $recent_search_courses = $res['courses'];
                }
            }
        }

        return [
            // Stats
            'total_courses'     => number_format($total_courses),
            'total_students'    => number_format($total_students),
            'total_instructors' => number_format($total_instructors),

            // Hero
            'hero_title'    => get_config('local_catalog', 'home_hero_title') ?: 'Aprende sin límites',
            'hero_subtitle' => get_config('local_catalog', 'home_hero_subtitle') ?: '',
            'hero_bgimage'  => get_config('local_catalog', 'home_hero_bgimage') ?: '',
            'hero_buttons'  => !empty($hero_buttons_arr) ? ['buttons' => $hero_buttons_arr] : false,

            // Features
            'features'     => $features_arr,
            'has_features' => !empty($features_arr),

            // Empresas
            'companies'       => $companies_arr,
            'has_companies'   => !empty($companies_arr),
            'companies_title' => get_config('local_catalog', 'home_companies_title') ?: 'Formación de confianza para las empresas líderes',

            // Testimonios
            'testimonials'     => $testimonials_arr,
            'has_testimonials' => !empty($testimonials_arr),

            // Tendencia
            'trending_courses' => $trending,
            'has_trending'     => !empty($trending),

            // Recientes
            'recent_searches'       => $recent_searches,
            'recent_search_courses' => $recent_search_courses,
            'has_recent_searches'   => !empty($recent_searches),

            // Tags
            'popular_tags'     => $popular_tags,
            'has_popular_tags' => !empty($popular_tags),

            // Saludo
            'isloggedin' => $isloggedin,
            'firstname'  => $isloggedin ? $GLOBALS['USER']->firstname : '',

            // URL catálogo
            'catalogurl' => (new \moodle_url('/cursos'))->out(false),
        ];
    }
    */

    // ─────────────────────────────────────────────────────────────────────────────
    // get_footer_data()
    // Datos para el footer — llamar desde todos los controladores
    // ─────────────────────────────────────────────────────────────────────────────
    public function get_footer_data(): array {

        // Columnas desde admin settings
        $cols_raw = get_config('local_catalog', 'footer_columns');
        $cols     = $cols_raw ? json_decode($cols_raw, true) : [];

        // Redes sociales
        $social_raw  = get_config('local_catalog', 'footer_social');
        $social_arr  = $social_raw ? json_decode($social_raw, true) : [];

        return [
            'footer_columns' => is_array($cols) ? $cols : [],
            'has_footer_cols'=> !empty($cols),
            'social_links'   => $social_arr,
            'has_social'     => !empty($social_arr),
            'current_year'   => date('Y'),
            'homeurl'        => (new \moodle_url('/'))->out(false),
            'logourl'        => get_config('local_catalog', 'site_logo_url') ?: '',
            'sitename'       => get_config('local_catalog', 'site_name') ?: get_string('catalog', 'local_catalog'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // save_recent_search()
    // Guarda una búsqueda en el historial del usuario (máx. 10).
    // Llamar desde index.php cuando $search no está vacío.
    //
    //   $manager->save_recent_search($search);
    // ─────────────────────────────────────────────────────────────────────────────
    public function save_recent_search(string $query): void {
        global $USER;

        if (!isloggedin() || isguestuser() || empty(trim($query))) return;

        $pref    = get_user_preferences('local_catalog_recent_searches', '', $USER->id);
        $searches = $pref ? (json_decode($pref, true) ?: []) : [];

        // Eliminar duplicado si ya existe
        $searches = array_values(array_filter($searches, fn($s) => $s !== $query));

        // Añadir al principio
        array_unshift($searches, $query);

        // Mantener máximo 10
        $searches = array_slice($searches, 0, 10);

        set_user_preference('local_catalog_recent_searches', json_encode($searches), $USER->id);
    }

    // =========================================================================
    // HOME: Cursos en tendencia (más matriculados)
    // =========================================================================

    /**
     * Devuelve los N cursos con más matriculaciones activas.
     * Si ningún curso tiene matriculaciones, devuelve los más recientes.
     *
     * @param int $limit
     * @return array
     */
    public function get_trending_courses(int $limit = 8): array {

        global $DB;

        // Intentar por matriculaciones
        $sql = " SELECT c.id, c.fullname, c.shortname
FROM {course} c
JOIN {customfield_data} cd ON cd.instanceid = c.id
JOIN {customfield_field} cf ON cf.id = cd.fieldid
WHERE cf.shortname = 'destacados_home'
        AND cd.value = 1
        AND c.visible = 1
ORDER BY cd.intvalue ASC  ";
//LIMIT :lim ";

        $records = $DB->get_records_sql($sql, [
            'siteid' => SITEID,
            //'lim'    => $limit,
        ]);

        return $this->format_home_cards($records);
    }


    // =========================================================================
    // HOME: Cursos más recientes de una categoría
    // =========================================================================

    /**
     * Devuelve los N cursos más recientes de una categoría y sus hijas.
     * Usado en "Lo más nuevo en Data & IA".
     *
     * @param int $categoryid
     * @param int $limit
     * @return array
     */
    public function get_newest_by_category(int $categoryid, int $limit = 8): array {

        global $DB;

        $catids = $this->get_recursive_category_ids($categoryid);
        if (empty($catids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');

        // LIMIT :lim
        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.category,
                       0 AS enrolled_count
                  FROM {course} c
                 WHERE c.visible = 1
                   AND c.id != :siteid
                   AND c.category $insql
              ORDER BY c.timecreated DESC
                 ";

        $params['siteid'] = SITEID;
        // $params['lim']    = $limit;

        $records = $DB->get_records_sql($sql, $params);

        return $this->format_home_cards($records);
    }


    // =========================================================================
    // HOME: Tags populares
    // =========================================================================

    /**
     * Devuelve los N tags de cursos con más asociaciones.
     * Usado en "Habilidades más populares".
     *
     * @param int $limit
     * @return array  [['name'=>'JavaScript','url'=>'/cursos?q=JavaScript','count'=>34], ...]
     */
    public function get_popular_topics(int $limit = 12): array {

        global $DB;

        $sql = "SELECT t.id, t.rawname, t.name, COUNT(ti.id) AS course_count
                  FROM {tag} t
                  JOIN {tag_instance} ti ON ti.tagid    = t.id
                                        AND ti.itemtype  = 'course'
                                        AND ti.component = 'core'
              GROUP BY t.id, t.rawname, t.name
              ORDER BY course_count DESC
                 LIMIT :lim";

        $records = $DB->get_records_sql($sql, ['lim' => $limit]);

        $topics = [];
        foreach ($records as $tag) {
            $topics[] = [
                'name'  => $tag->rawname,
                'url'   => (new \moodle_url('/cursos', ['q' => $tag->rawname]))->out(false),
                'count' => (int) $tag->course_count,
            ];
        }

        return $topics;
    }

    // =========================================================================
    // HOME: Estadísticas globales del campus
    // =========================================================================

    /**
     * Estadísticas reales para la sección "Por qué Campus Virtual".
     *
     * @return array
     */
    public function get_campus_stats(): array {

        global $DB;

        // Cursos visibles (excluye SITEID)
        $totalcourses = (int) $DB->count_records_select(
            'course',
            'visible = 1 AND id != :sid',
            ['sid' => SITEID]
        );

        // Estudiantes únicos matriculados en algún curso visible
        $sql = "SELECT COUNT(DISTINCT ue.userid)
                  FROM {user_enrolments} ue
                  JOIN {enrol}  e ON e.id  = ue.enrolid  AND e.status  = 0
                  JOIN {course} c ON c.id  = e.courseid  AND c.visible = 1
                 WHERE ue.status = 0 AND e.courseid != :sid";

        $totalstudents = (int) $DB->count_records_sql($sql, ['sid' => SITEID]);

        // Instructores únicos (rol teacher=4 o editingteacher=3 en algún curso)
        $sql = "SELECT COUNT(DISTINCT ra.userid)
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                 WHERE ra.roleid IN (3, 4)";

        $totalinstructors = (int) $DB->count_records_sql($sql);

        // Categorías visibles
        $totalcategories = (int) $DB->count_records_select(
            'course_categories', 'visible = 1'
        );

        // Formatear números grandes: 1234 → "1.2K"
        $fmt = function(int $n): string {
            if ($n >= 1000) {
                return number_format($n / 1000, 1, ',', '') . 'K';
            }
            return (string) $n;
        };

        return [
            'totalcourses'     => $fmt($totalcourses),
            'totalstudents'    => $fmt($totalstudents),
            'totalinstructors' => $fmt($totalinstructors),
            'totalcategories'  => $fmt($totalcategories),
        ];
    }


    // =========================================================================
    // HOME: Testimonios de fallback (si no hay nada configurado en settings)
    // =========================================================================

    /**
     * Datos de ejemplo usados SOLO en desarrollo/demo.
     * En producción se configuran desde Admin → Ajustes del plugin.
     *
     * @return array
     */
    public function get_testimonials_fallback(): array {
        return [
            [
                'name'        => 'Ana Martínez',
                'jobtitle'    => 'Desarrolladora Frontend',
                'hasjobtitle' => true,
                'testimonial' => 'El campus ha transformado mi carrera. En 3 meses pasé de no saber nada de React a liderar el proyecto frontend de mi equipo.',
                'initials'    => 'AM',
            ],
            [
                'name'        => 'Carlos López',
                'jobtitle'    => 'Data Analyst',
                'hasjobtitle' => true,
                'testimonial' => 'Los cursos de Data son muy prácticos. Los instructores resuelven todas las dudas y el contenido está siempre actualizado.',
                'initials'    => 'CL',
            ],
            [
                'name'        => 'Sara Rodríguez',
                'jobtitle'    => 'Project Manager',
                'hasjobtitle' => true,
                'testimonial' => 'Gracias al campus obtuve la certificación PMP. El material de preparación es excelente y muy completo.',
                'initials'    => 'SR',
            ],
        ];
    }

    // =========================================================================
    // HOME: Nombre de una categoría
    // =========================================================================

    /**
     * @param int $categoryid
     * @return string
     */
    private function get_category_name(int $categoryid): string {
        global $DB;
        return (string) ($DB->get_field(
            'course_categories', 'name', ['id' => $categoryid]
        ) ?: '');
    }

    // =========================================================================
    // HOME: Orquestador — todos los datos para home.php
    // =========================================================================

    /**
     * Devuelve el array completo de datos dinámicos para home.mustache.
     * El ID de la categoría "Lo más nuevo en..." se configura en
     * Admin → Plugins → Plugins locales → Campus Virtual → Ajustes.
     *
     * @return array
     */
    public function get_home_data(): array {

        $datacat = (int) get_config('local_catalog', 'home_datacat_id');

        return [
            // Carrusel 1: tendencia
            //'trending_courses' => $this->get_trending_courses(8),
            //'has_trending'     => true,

            // Habilidades
            //'popular_topics'   => $this->get_popular_topics(12),
            //'has_topics'       => true,

            // Estadísticas (para hero + features)
            'stats'            => $this->get_campus_stats(),

            // Carrusel 2: categoría configurable
            'has_datacat'      => $datacat > 0,
            'datacat_courses'  => $datacat > 0
                ? $this->get_newest_by_category($datacat, 8)
                : [],
            'datacat_name'     => $datacat > 0
                ? $this->get_category_name($datacat)
                : '',
            'datacat_url'      => $datacat > 0
                ? (new \moodle_url('/cursos', ['cat' => $datacat]))->out(false)
                : '',
        ];

    }

    // =========================================================================
    // HELPER PRIVADO: convierte records de {course} en tarjetas para home
    // =========================================================================

    /**
     * Formato unificado para todas las tarjetas de curso de la home.
     *
     * @param array $records  Resultado de $DB->get_records_sql()
     * @return array
     */
    private function format_home_cards(array $records): array {

        $cards = [];

        foreach ($records as $r) {
            $slug        = self::slugify($r->shortname ?: $r->fullname);
            $customfields = $this->get_custom_fields_simple((int) $r->id);
            $instructors  = $this->get_instructors_simple((int) $r->id);
            $price        = $this->get_price((int) $r->id);
            $thumburl     = $this->get_course_thumb((int) $r->id);

            $cards[] = [
                'id'             => (int) $r->id,
                'fullname'       => $r->fullname,
                'slug'           => $slug,
                'courseurl'      => '/cursos/' . $slug,
                'summary'        => mb_strimwidth(
                    strip_tags(format_text($r->summary, FORMAT_HTML)),
                    0, 120, '…'
                ),
                'thumburl'       => $thumburl,
                'hasthumb'       => !empty($thumburl),
                'instructors'    => implode(', ', $instructors),
                'hasinstructors' => !empty($instructors),
                'level'          => $customfields['course_level'] ?? '',
                'haslevel'       => !empty($customfields['course_level']),
                'is_free'        => $price['is_free'],
                'pricelabel'     => $price['is_free']
                    ? get_string('free', 'local_catalog')
                    : $price['cost'] . ' ' . $price['currency'],
                // Rating: en producción conectar a core_rating o custom field
                'rating'         => '4.7',
                'ratingcount'    => rand(120, 4800),
            ];
        }

        return $cards;
    }

    // =========================================================================
    // HELPER PRIVADO: thumbnail del curso (imagen de presentación)
    // =========================================================================

    /**
     * Obtiene la URL de la imagen de presentación del curso.
     * Devuelve '' si el curso no tiene imagen.
     *
     * @param int $courseid
     * @return string
     */
    private function get_course_thumb(int $courseid): string {
        try {
            $course = new \core_course_list_element(get_course($courseid));
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
        } catch (\Exception $e) {
            // Sin imagen — devuelve ''
        }
        return '';
    }

    // =============================================================================
    // MÉTODO get_menu_tree() — añadir a catalog_manager.php antes del último }
    // =============================================================================
    // Devuelve el árbol jerárquico de categorías + cursos para el mega-menú.
    //
    // Estructura devuelta (ejemplo con tus datos reales):
    //
    //  [
    //    {                              ← nivel 1
    //      id: 1, name: "Escuela Moodle",
    //      depth: 1, data_key: "cat-1",
    //      has_subcats: true,
    //      subcats: [
    //        {                          ← nivel 2
    //          id: 2, name: "Informática",
    //          depth: 2, data_key: "cat-2",
    //          has_subcats: true,
    //          subcats: [
    //            {                      ← nivel 3 (tiene cursos directos)
    //              id: 3, name: "React",
    //              depth: 3, data_key: "cat-3",
    //              has_courses: true,
    //              courses: [
    //                { fullname: "React JS de Cero", courseurl: "/cursos/reactjs" },
    //                { fullname: "Introducción a la Programación Web", courseurl: "..." }
    //              ]
    //            },
    //            {
    //              id: 4, name: "JavaScript", ...
    //            }
    //          ]
    //        }
    //      ]
    //    }
    //  ]
    //
    // =============================================================================

    /**
     * Devuelve el árbol completo de categorías y cursos para el mega-menú.
     *
     * La jerarquía se detecta a partir del campo `depth` de {course_categories}:
     *   depth=1 → nivel raíz  (Escuela Moodle)
     *   depth=2 → segundo nivel (Informática)
     *   depth=3 → tercer nivel, contiene cursos directos (React, JavaScript)
     *
     * @return array  Array de nodos nivel-1, cada uno con sus descendientes.
     */
    public function get_menu_tree(): array {

        global $DB;

        // ── 1. Obtener TODAS las categorías visibles ──────────────────────────
        $sql_cats = "SELECT id, name, parent, depth, path, sortorder
                       FROM {course_categories}
                      WHERE visible = 1
                   ORDER BY depth ASC, sortorder ASC";

        $all_cats = $DB->get_records_sql($sql_cats);

        // ── 2. Obtener TODOS los cursos visibles (excluye SITEID) ─────────────
        $sql_courses = "SELECT id, fullname, shortname, category
                          FROM {course}
                         WHERE visible = 1
                           AND id != :siteid
                      ORDER BY fullname ASC";

        $all_courses = $DB->get_records_sql($sql_courses, ['siteid' => SITEID]);

        // ── 3. Agrupar cursos por categoria ───────────────────────────────────
        // [ catid => [ {id, fullname, courseurl}, ... ] ]
        $courses_by_cat = [];

        foreach ($all_courses as $course) {
            $slug = self::slugify($course->shortname ?: $course->fullname);
            $courses_by_cat[$course->category][] = [
                'id'        => (int) $course->id,
                'fullname'  => $course->fullname,
                'courseurl' => '/cursos/' . $slug,
                'slug'      => $slug,
            ];
        }

        /*
        echo "<pre>";
        print_r($courses_by_cat);
        echo "</pre>";
        */

        // ── 4. Convertir a array indexado por id con metadatos ────────────────
        // [ catid => { id, name, depth, parent, data_key, url, children[], courses[] } ]
        $nodes = [];

        foreach ($all_cats as $cat) {
            $nodes[$cat->id] = [
                'id'          => (int) $cat->id,
                'name'        => $cat->name,
                'depth'       => (int) $cat->depth,
                'parent'      => (int) $cat->parent,
                'data_key'    => 'cat-' . $cat->id,   // usado en data-cat del HTML
                'url'         => (new \moodle_url('/cursos', ['cat' => $cat->id]))->out(false),
                'subcats'     => [],
                'courses'     => $courses_by_cat[$cat->id] ?? [],
                'has_subcats' => false,
                'has_courses' => !empty($courses_by_cat[$cat->id]),
                'coursecount' => count($courses_by_cat[$cat->id] ?? []),
            ];
        }

        /*
        echo "<pre>";
        print_r($nodes);
        echo "</pre>";
        */

        // ── 5. Construir el árbol: anidar hijos dentro del padre ──────────────
        $roots = [];

        foreach ($nodes as $id => $node) {
            $parentid = $node['parent'];

            if ($parentid === 0 || !isset($nodes[$parentid])) {
                // Es un nodo raíz (nivel 1)
                $roots[$id] = &$nodes[$id];
            } else {
                // Añadir como hijo del padre
                $nodes[$parentid]['subcats'][]     = &$nodes[$id];
                $nodes[$parentid]['has_subcats']    = true;
            }
        }

        // ── 6. Convertir a array plano (Mustache no entiende claves asociativas) ─

        /*
        echo "<pre>";
        print_r($roots);
        echo "</pre>";
        */

        return array_values($roots);
    }


    /**
     * Versión aplanada del árbol para Mustache.
     *
     * Mustache no puede hacer recursión, así que necesitamos una estructura
     * plana pero con los niveles ya calculados.
     *
     * Devuelve DOS listas separadas para el mega-menú de dos columnas:
     *   'triggers' → columna izquierda (nivel 1 o 2 según diseño)
     *   'panels'   → columna derecha (subcategorías + cursos del nodo activo)
     *
     * @return array ['tree' => [...], 'triggers' => [...], 'panels' => [...]]
    */
    public function get_menu_data(): array {

        $tree = $this->get_menu_tree();

        /*
        echo "<pre>";
        print_r($tree);
        echo "</pre>";
        */

        // Aplanar para el mega-menú de dos columnas
        // Columna izquierda: nodos de nivel 1 (triggers)
        // Columna derecha:   hijos de nivel 1 como paneles (con sus subcats y cursos)

        $triggers = [];
        $panels   = [];

        foreach ($tree as $l1) {

            $triggers[] = [
                'id'          => $l1['id'],
                'name'        => $l1['name'],
                'url'         => $l1['url'],
                'data_key'    => $l1['data_key'],
                'has_subcats' => $l1['has_subcats'],
                'has_courses' => $l1['has_courses'],
            ];

            // Panel derecho: subcats de nivel 2, con sus subcats de nivel 3
            $panel_subcats = [];

            foreach ($l1['subcats'] as $l2) {

                $l3_nodes = [];

                foreach ($l2['subcats'] as $l3) {
                    $l3_nodes[] = [
                        'id'          => $l3['id'],
                        'name'        => $l3['name'],
                        'url'         => $l3['url'],
                        'data_key'    => $l3['data_key'],
                        'has_courses' => $l3['has_courses'],
                        'coursecount' => $l3['coursecount'],
                        'courses'     => array_slice($l3['courses'], 0, 6), // max 6 en menú
                    ];
                }

                $panel_subcats[] = [
                    'id'          => $l2['id'],
                    'name'        => $l2['name'],
                    'url'         => $l2['url'],
                    'data_key'    => $l2['data_key'],
                    'has_subcats' => !empty($l3_nodes),
                    'subcats'     => $l3_nodes,
                    'has_courses' => $l2['has_courses'],
                    'courses'     => array_slice($l2['courses'], 0, 6),
                    'coursecount' => $l2['coursecount'],
                ];
            }

            $panels[] = [
                'data_key'    => $l1['data_key'],    // debe coincidir con el trigger
                'name'        => $l1['name'],
                'url'         => $l1['url'],
                'has_subcats' => !empty($panel_subcats),
                'subcats'     => $panel_subcats,
                'has_courses' => $l1['has_courses'],
                'courses'     => array_slice($l1['courses'], 0, 6),
            ];
        }

        /*
        echo "<pre>";
        print_r($triggers);
        echo "</pre>";

        echo "<pre>";
        print_r($panels);
        echo "</pre>";
        */
        //die();

        return [
            'tree'          => $tree,        // árbol completo (para debug o uso avanzado)
            'triggers'      => $triggers,    // columna izquierda del mega-menú
            'panels'        => $panels,      // columna derecha del mega-menú
            'has_menu'      => !empty($triggers),
        ];
    }


}
