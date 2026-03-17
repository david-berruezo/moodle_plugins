<?php

// This file is part of Moodle - http://moodle.org/
// @package    local_catalog

namespace local_catalog;

defined('MOODLE_INTERNAL') || die();

/**
 * Orquesta todos los datos del aula virtual (player tipo Udemy).
 *
 * Responsabilidades:
 *  - Construir el sidebar de vídeos por secciones
 *  - Resolver la actividad actual (por cmid o primera del curso)
 *  - Extraer el contenido reproducible (iframe embed, HTML, fichero)
 *  - Calcular la navegación anterior/siguiente entre vídeos
 *  - Calcular el progreso del alumno
 */
class course_player
{

    /** @var catalog_manager */
    private catalog_manager $manager;

    public function __construct()
    {
        $this->manager = new catalog_manager();
    }

    // =========================================================================
    // PUNTO DE ENTRADA — llamado desde learn.php
    // =========================================================================

    /**
     * Devuelve el array completo de datos para course_player.mustache.
     *
     * @param \stdClass $course Objeto curso de get_course()
     * @param int $cmid CM actual (0 = mostrar índice)
     * @param bool $isenrolled El usuario está matriculado
     * @param bool $isguest Usuario sin login
     * @return array|null
     */
    public function get_player_data(
        \stdClass $course,
        int       $cmid,
        bool      $isenrolled,
        bool      $isguest
    ): ?array
    {

        global $USER, $OUTPUT, $PAGE;

        $courseid = (int)$course->id;
        $slug = catalog_manager::slugify($course->shortname ?: $course->fullname);

        // ── 1. Secciones de vídeo (sidebar + índice central) ─────────────────
        $sectionsdata = $this->manager->get_course_sections_public($courseid);
        $sections_videos = $sectionsdata['sections_videos'];

        if (empty($sections_videos)) {
            return null;
        }

        // ── 2. Lista plana de todos los vídeos (para nav prev/next) ──────────
        $all_videos = $this->flatten_videos($sections_videos);
        //var_dump($all_videos);

        if (empty($all_videos)) {
            return null;
        }

        // ── 3. Resolver actividad actual ──────────────────────────────────────
        // Si cmid=0 → modo índice (sin vídeo activo)
        // Si cmid>0 → reproducir ese vídeo
        $current = null;
        $show_index = ($cmid === 0);

        if (!$show_index) {
            $current = $this->find_video_by_cmid($all_videos, $cmid);
            if (!$current) {
                // cmid inválido → volver al índice
                $show_index = true;
            }
        }

        // obtenemos por defecto el primero
        if ($show_index){
            if (!$current && is_array($all_videos) && count($all_videos) > 0){
                $current = $this->find_video_by_cmid($all_videos, $all_videos[0]["id"]);
                if ($current){
                    $show_index = false;
                }
            }
        }

        // ── 4. Navegación anterior / siguiente ────────────────────────────────
        $prevnext = $this->get_prevnext($all_videos, $cmid, $courseid, $slug);

        // ── 5. Marcar actividad activa en el sidebar ──────────────────────────
        $sections_sidebar = $this->mark_active($sections_videos, $cmid, $courseid, $slug);

        // ── 6. Progreso del alumno ────────────────────────────────────────────
        $progress = $isenrolled
            ? $this->get_progress($courseid, (int)$USER->id)
            : ['percent' => 0, 'done' => 0, 'total' => count($all_videos)];

        // ── 7. Instructores ───────────────────────────────────────────────────
        $instructors = $this->manager->get_instructors_for_player($courseid);

        // ── 8. Custom fields del curso (para la pestaña Descripción) ─────────
        $customfields = $this->get_course_customfields($courseid);

        // ── 9. Componer el $data ──────────────────────────────────────────────
        $data = [
            // ── Estado de vista ───────────────────────────────────────────────
            'show_index' => $show_index,   // true → muestra índice de vídeos
            'show_player' => !$show_index,  // true → muestra el player

            // ── Curso ─────────────────────────────────────────────────────────
            'courseid' => $courseid,
            'coursename' => $course->fullname,
            'courseurl' => '/cursos/' . $slug,
            'learnurl' => '/cursos/' . $slug . '/ver',

            // ── Sidebar ───────────────────────────────────────────────────────
            'sections_videos' => $sections_sidebar,

            // ── Índice central (cuando show_index=true) ───────────────────────
            'index_sections' => $show_index ? $this->build_index($sections_videos, $courseid, $slug) : [],

            // ── Player (cuando show_player=true) ──────────────────────────────
            'current_cmid' => $current['id'] ?? 0,
            'current_name' => $current['name'] ?? $course->fullname,
            'is_video' => !empty($current['videoembedurl']),
            'video_url' => $current['videoembedurl'] ?? '',
            'has_description' => !empty($current['description']),
            'description' => $current['description'] ?? '',
            'has_preview' => !empty($current['previewurl']),
            'previewurl' => $current['previewurl'] ?? '',

            // ── Navegación ────────────────────────────────────────────────────
            'hasprev' => !empty($prevnext['prev']),
            'hasnext' => !empty($prevnext['next']),
            'prevurl' => $prevnext['prev']['url'] ?? '',
            'prevname' => $prevnext['prev']['name'] ?? '',
            'nexturl' => $prevnext['next']['url'] ?? '',
            'nextname' => $prevnext['next']['name'] ?? '',

            // ── Progreso ──────────────────────────────────────────────────────
            'progress_percent' => $progress['percent'],
            'progress_done' => $progress['done'],
            'progress_total' => $progress['total'],

            // ── Instructor / custom fields (pestañas) ─────────────────────────
            'instructores' => $instructors,
            'hasinstructores' => !empty($instructors),
            'personalizados' => $customfields,

            // ── Acceso ────────────────────────────────────────────────────────
            'isenrolled' => $isenrolled,
            'isguest' => $isguest,

            // ── URLs globales ─────────────────────────────────────────────────
            'homeurl' => (new \moodle_url('/'))->out(false),
            'catalogurl' => (new \moodle_url('/cursos'))->out(false),
            'logouturl' => (new \moodle_url('/login/logout.php',
                ['sesskey' => sesskey()]))->out(false),
            'searchaction'   => (new moodle_url('/cursos'))->out(false),
            'mycoursesurl'   => (new moodle_url('/mis-cursos'))->out(false),
            'loginurl'       => (new moodle_url('/login/index.php'))->out(false),
            'registerurl'    => (new moodle_url('/registro'))->out(false),
            'instructorsurl' => (new moodle_url('/profesores'))->out(false),
            'planurl'        => (new moodle_url('/plan-personal'))->out(false),
            'compareurl'     => (new moodle_url('/comparar-planes'))->out(false),
            'demourl'        => (new moodle_url('/solicitar-demo'))->out(false),
            'teachurl'       => (new moodle_url('/ensena-aqui'))->out(false),
            'termsurl'       => (new moodle_url('/terminos'))->out(false),
            'privacyurl'     => (new moodle_url('/privacidad'))->out(false),
        ];

        // Añadir navbar y footer
        $data = array_merge($data, $this->manager->get_navbar_data());

        return $data;
    }

    // =========================================================================
    // MÉTODO PÚBLICO — expone get_course_sections a learn.php si hiciera falta
    // =========================================================================
    // (lo usamos internamente vía catalog_manager)


    // =========================================================================
    // PRIVADOS — lógica interna
    // =========================================================================

    /**
     * Aplana las secciones de vídeo en una lista plana de actividades.
     * Resultado: [ ['id'=>cmid, 'name'=>..., 'videoembedurl'=>...], ... ]
     */
    private function flatten_videos(array $sections_videos): array
    {
        $flat = [];
        foreach ($sections_videos as $section) {
            foreach ($section['activities'] as $activity) {
                $flat[] = $activity;
            }
        }
        return $flat;
    }

    /**
     * Busca una actividad por cmid en la lista plana.
     */
    private function find_video_by_cmid(array $all_videos, int $cmid): ?array
    {
        foreach ($all_videos as $video) {
            if ((int)$video['id'] === $cmid) {
                return $video;
            }
        }
        return null;
    }

    /**
     * Calcula la URL anterior y siguiente respecto al cmid actual.
     */
    private function get_prevnext(array $all_videos, int $cmid, int $courseid, string $slug): array
    {
        $result = ['prev' => null, 'next' => null];

        if ($cmid === 0 || empty($all_videos)) {
            return $result;
        }

        $baseurl = '/cursos/' . $slug . '/ver?cmid=';

        foreach ($all_videos as $i => $video) {
            if ((int)$video['id'] !== $cmid) {
                continue;
            }
            if ($i > 0) {
                $prev = $all_videos[$i - 1];
                $result['prev'] = [
                    'url' => $baseurl . $prev['id'],
                    'name' => $prev['name'],
                ];
            }
            if ($i < count($all_videos) - 1) {
                $next = $all_videos[$i + 1];
                $result['next'] = [
                    'url' => $baseurl . $next['id'],
                    'name' => $next['name'],
                ];
            }
            break;
        }

        return $result;
    }

    /**
     * Copia sections_videos añadiendo 'is_active' y 'url' a cada actividad.
     */
    private function mark_active(array $sections_videos, int $cmid, int $courseid, string $slug): array
    {
        $baseurl = '/cursos/' . $slug . '/ver?cmid=';
        $marked = [];

        foreach ($sections_videos as $section) {
            $activities = [];
            $section_active = false;

            foreach ($section['activities'] as $activity) {
                $is_active = ((int)$activity['id'] === $cmid);
                if ($is_active) {
                    $section_active = true;
                }
                $activities[] = array_merge($activity, [
                    'is_active' => $is_active,
                    'playerurl' => $baseurl . $activity['id'],
                ]);
            }

            $marked[] = array_merge($section, [
                'activities' => $activities,
                'section_active' => $section_active,
                // La sección activa se muestra expandida
                'isopen' => $section_active || $section['isopen'],
            ]);
        }

        return $marked;
    }

    /**
     * Construye el array para el índice central (show_index=true).
     * Igual que sections_videos pero con thumbnail y url de reproducción.
     */
    private function build_index(array $sections_videos, int $courseid, string $slug): array
    {
        $baseurl = '/cursos/' . $slug . '/ver?cmid=';
        $index = [];

        foreach ($sections_videos as $section) {
            $activities = [];
            foreach ($section['activities'] as $activity) {
                $activities[] = array_merge($activity, [
                    'playerurl' => $baseurl . $activity['id'],
                ]);
            }

            $index[] = array_merge($section, [
                'activities' => $activities,
            ]);
        }

        return $index;
    }

    /**
     * Calcula el progreso del alumno en el curso.
     */
    private function get_progress(int $courseid, int $userid): array
    {
        global $DB;

        $total = (int)$DB->count_records('course_modules', [
            'course' => $courseid,
            'completion' => 1,
        ]);

        $done = (int)$DB->count_records_select(
            'course_modules_completion',
            'userid = :uid AND completionstate IN (1,2)
             AND coursemoduleid IN (
                 SELECT id FROM {course_modules}
                  WHERE course = :cid AND completion = 1
             )',
            ['uid' => $userid, 'cid' => $courseid]
        );

        return [
            'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
            'done' => $done,
            'total' => $total,
        ];
    }

    /**
     * Devuelve los instructores en el formato que espera el template.
     */
    private function get_course_customfields(int $courseid): array
    {
        $result = [
            'course_long_description' => '',
            'course_objectives' => '',
            'course_requirements' => '',
        ];

        try {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            foreach ($handler->get_instance_data($courseid) as $field) {
                $shortname = $field->get_field()->get('shortname');
                if (array_key_exists($shortname, $result)) {
                    $value = $field->export_value();
                    if (!empty($value)) {
                        $result[$shortname] = $value;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $result;
    }
}