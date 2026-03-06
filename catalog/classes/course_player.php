<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_catalog;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/mod/page/lib.php');
require_once($GLOBALS['CFG']->libdir . '/completionlib.php');

/**
 * Gestor del player de curso (aula virtual tipo Udemy).
 *
 * Maneja:
 * - Obtención del contenido de la actividad actual
 * - Sidebar con temario y progreso
 * - Navegación anterior/siguiente
 * - Estado de completación por actividad
 */
class course_player {

    /**
     * Obtiene todos los datos necesarios para el player.
     *
     * @param \stdClass $course El curso
     * @param int $cmid ID de la actividad actual (0 = primera actividad)
     * @return array|null Datos para el template
     */
    public function get_player_data(\stdClass $course, int $cmid = 0): ?array {
        global $USER, $DB;

        $modinfo = get_fast_modinfo($course);
        $context = \context_course::instance($course->id);

        // --- 1. Construir lista ordenada de todas las actividades navegables ---
        $allactivities = $this->get_ordered_activities($modinfo);

        if (empty($allactivities)) {
            return null;
        }

        // --- 2. Determinar actividad actual ---
        if ($cmid == 0) {
            // Si no se especifica, ir a la primera actividad
            $cmid = $allactivities[0]['cmid'];
        }

        // Buscar índice actual
        $currentindex = 0;
        foreach ($allactivities as $idx => $act) {
            if ($act['cmid'] == $cmid) {
                $currentindex = $idx;
                break;
            }
        }

        // --- 3. Obtener contenido de la actividad actual ---
        $cm = $modinfo->get_cm($cmid);
        $activitycontent = $this->get_activity_content($cm, $course);

        // --- 4. Navegación anterior/siguiente ---
        $hasprev = $currentindex > 0;
        $hasnext = $currentindex < (count($allactivities) - 1);

        $prevurl = $hasprev
            ? (new \moodle_url('/local/catalog/learn.php', [
                'id' => $course->id,
                'cmid' => $allactivities[$currentindex - 1]['cmid'],
            ]))->out(false)
            : '';

        $nexturl = $hasnext
            ? (new \moodle_url('/local/catalog/learn.php', [
                'id' => $course->id,
                'cmid' => $allactivities[$currentindex + 1]['cmid'],
            ]))->out(false)
            : '';

        // --- 5. Construir sidebar con secciones y estado de completación ---
        $sections = $this->build_sidebar($modinfo, $course, $cmid);

        // --- 6. Progreso del curso ---
        $completioninfo = new \completion_info($course);
        $progress = $this->get_course_progress($completioninfo, $modinfo);

        // --- 7. Completar actividad actual vía AJAX (URL para marcar como completada) ---
        $completeurl = '';
        if ($completioninfo->is_enabled($cm) == COMPLETION_TRACKING_MANUAL) {
            $completeurl = (new \moodle_url('/local/catalog/complete.php', [
                'id'     => $course->id,
                'cmid'   => $cmid,
                'sesskey' => sesskey(),
            ]))->out(false);
        }

        // --- Datos para el template ---
        return [
            // Curso
            'courseid'     => $course->id,
            'coursename'   => $course->fullname,
            'courseurl'     => (new \moodle_url('/local/catalog/course.php', ['id' => $course->id]))->out(false),
            'catalogurl'   => (new \moodle_url('/local/catalog/index.php'))->out(false),
            'dashboardurl' => (new \moodle_url('/my/'))->out(false),

            // Actividad actual
            'current_cmid'    => $cmid,
            'current_name'    => $cm->name,
            'current_type'    => $cm->modname,
            'current_content' => $activitycontent,
            'current_number'  => $currentindex + 1,
            'total_activities' => count($allactivities),

            // Tipo de contenido (para condicionales en template)
            'is_video'     => $activitycontent['is_video'] ?? false,
            'is_page'      => $activitycontent['is_page'] ?? false,
            'is_other'     => $activitycontent['is_other'] ?? false,
            'video_url'    => $activitycontent['video_url'] ?? '',
            'html_content' => $activitycontent['html_content'] ?? '',
            'activity_url' => $activitycontent['activity_url'] ?? '',

            // Navegación
            'hasprev' => $hasprev,
            'hasnext' => $hasnext,
            'prevurl' => $prevurl,
            'nexturl' => $nexturl,

            // Sidebar
            'sections' => $sections,

            // Progreso
            'progress'         => $progress['percentage'],
            'progress_text'    => $progress['text'],
            'completed_count'  => $progress['completed'],
            'total_count'      => $progress['total'],

            // Completar manualmente
            'cancomplete'  => !empty($completeurl),
            'completeurl'  => $completeurl,
            'iscompleted'  => $this->is_activity_completed($completioninfo, $cm),
        ];
    }

    /**
     * Obtiene la lista ordenada de actividades navegables.
     * Excluye labels y recursos no visibles.
     */
    private function get_ordered_activities(\course_modinfo $modinfo): array {
        $activities = [];

        foreach ($modinfo->get_section_info_all() as $sectionnum => $sectioninfo) {
            if (!isset($modinfo->sections[$sectionnum])) {
                continue;
            }

            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];

                // Excluir labels y actividades no visibles
                if ($cm->modname === 'label' || !$cm->uservisible) {
                    continue;
                }

                $activities[] = [
                    'cmid'    => $cm->id,
                    'name'    => $cm->name,
                    'modname' => $cm->modname,
                    'section' => $sectionnum,
                ];
            }
        }

        return $activities;
    }

    /**
     * Obtiene el contenido renderizable de una actividad.
     *
     * Detecta si contiene un vídeo (YouTube/Vimeo), contenido HTML,
     * o si debe redirigir a la URL nativa de Moodle.
     */
    private function get_activity_content(\cm_info $cm, \stdClass $course): array {
        global $DB;

        $result = [
            'is_video'     => false,
            'is_page'      => false,
            'is_other'     => false,
            'video_url'    => '',
            'html_content' => '',
            'activity_url' => (new \moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]))->out(false),
        ];

        switch ($cm->modname) {
            case 'page':
                $page = $DB->get_record('page', ['id' => $cm->instance]);
                if ($page) {
                    $content = $page->content;

                    // Detectar si el contenido contiene un vídeo embebido
                    $videourl = $this->extract_video_url($content);

                    if ($videourl) {
                        $result['is_video'] = true;
                        $result['video_url'] = $videourl;
                        // Si hay contenido además del vídeo, mostrarlo debajo
                        $cleanedcontent = $this->remove_video_from_content($content);
                        if (!empty(trim(strip_tags($cleanedcontent)))) {
                            $result['html_content'] = format_text($cleanedcontent, FORMAT_HTML);
                        }
                    } else {
                        $result['is_page'] = true;
                        $result['html_content'] = format_text($content, FORMAT_HTML);
                    }
                }
                break;

            case 'url':
                $urlrecord = $DB->get_record('url', ['id' => $cm->instance]);
                if ($urlrecord) {
                    $videourl = $this->extract_video_url($urlrecord->externalurl);
                    if ($videourl) {
                        $result['is_video'] = true;
                        $result['video_url'] = $videourl;
                    } else {
                        $result['is_other'] = true;
                    }
                }
                break;

            case 'quiz':
            case 'assign':
            case 'forum':
            case 'h5p':
            case 'lesson':
            case 'scorm':
                // Para estas actividades, mostrar en iframe o redirigir
                $result['is_other'] = true;
                break;

            case 'resource':
                // Archivo descargable
                $result['is_other'] = true;
                break;

            default:
                $result['is_other'] = true;
                break;
        }

        return $result;
    }

    /**
     * Extrae la URL de embed de YouTube o Vimeo desde contenido HTML o URL.
     *
     * Soporta:
     * - URLs directas: https://www.youtube.com/watch?v=xxxx
     * - URLs cortas: https://youtu.be/xxxx
     * - Embeds: https://www.youtube.com/embed/xxxx
     * - Iframes ya embebidos
     * - Vimeo: https://vimeo.com/xxxx
     */
    private function extract_video_url(string $content): string {
        // 1. Buscar iframe existente con YouTube/Vimeo
        if (preg_match('/src=["\']([^"\']*(?:youtube|youtu\.be|vimeo)[^"\']*)["\']/', $content, $matches)) {
            return $this->normalize_embed_url($matches[1]);
        }

        // 2. Buscar URL de YouTube en texto/href
        if (preg_match('#(?:https?://)?(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]+)#', $content, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // 3. YouTube corto
        if (preg_match('#(?:https?://)?youtu\.be/([a-zA-Z0-9_-]+)#', $content, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // 4. YouTube embed directo
        if (preg_match('#(?:https?://)?(?:www\.)?youtube\.com/embed/([a-zA-Z0-9_-]+)#', $content, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // 5. Vimeo
        if (preg_match('#(?:https?://)?(?:www\.)?vimeo\.com/(\d+)#', $content, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return '';
    }

    /**
     * Normaliza una URL de embed de vídeo.
     */
    private function normalize_embed_url(string $url): string {
        // Si ya es un embed URL, devolverla limpia
        if (strpos($url, 'youtube.com/embed/') !== false) {
            // Extraer solo el ID y reconstruir
            if (preg_match('#youtube\.com/embed/([a-zA-Z0-9_-]+)#', $url, $m)) {
                return 'https://www.youtube.com/embed/' . $m[1];
            }
        }
        if (strpos($url, 'player.vimeo.com/video/') !== false) {
            if (preg_match('#player\.vimeo\.com/video/(\d+)#', $url, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1];
            }
        }
        return $url;
    }

    /**
     * Elimina el vídeo embebido del contenido HTML para mostrar el resto.
     */
    private function remove_video_from_content(string $content): string {
        // Eliminar iframes
        $content = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $content);
        // Eliminar divs de video wrapper comunes
        $content = preg_replace('/<div[^>]*class="[^"]*video[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        // Eliminar URLs de YouTube/Vimeo sueltas
        $content = preg_replace('#https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/|vimeo\.com/)\S+#', '', $content);
        return trim($content);
    }

    /**
     * Construye la sidebar con secciones, actividades y estado de completación.
     */
    private function build_sidebar(\course_modinfo $modinfo, \stdClass $course, int $currentcmid): array {
        $completioninfo = new \completion_info($course);
        $courseformat = course_get_format($course->id);
        $sections = [];

        foreach ($modinfo->get_section_info_all() as $sectionnum => $sectioninfo) {
            if (!isset($modinfo->sections[$sectionnum])) {
                continue;
            }

            $sectionname = $courseformat->get_section_name($sectioninfo);
            if (empty($sectionname)) {
                $sectionname = get_string('section') . ' ' . $sectionnum;
            }

            $activities = [];
            $sectioncompleted = 0;
            $sectiontotal = 0;
            $containscurrent = false;

            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];

                if ($cm->modname === 'label' || !$cm->uservisible) {
                    continue;
                }

                $sectiontotal++;
                $iscurrent = ($cm->id == $currentcmid);
                if ($iscurrent) {
                    $containscurrent = true;
                }

                // Estado de completación
                $completed = $this->is_activity_completed($completioninfo, $cm);
                if ($completed) {
                    $sectioncompleted++;
                }

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
                }

                // Detectar si es vídeo
                $isvideo = $this->is_video_activity($cm);

                $activities[] = [
                    'cmid'       => $cm->id,
                    'name'       => $cm->name,
                    'icon'       => $isvideo ? '▶' : $icon,
                    'type'       => get_string('modulename', $cm->modname),
                    'iscurrent'  => $iscurrent,
                    'completed'  => $completed,
                    'url'        => (new \moodle_url('/local/catalog/learn.php', [
                        'id'   => $course->id,
                        'cmid' => $cm->id,
                    ]))->out(false),
                ];
            }

            if (!empty($activities)) {
                $sections[] = [
                    'name'            => $sectionname,
                    'num'             => $sectionnum,
                    'activities'      => $activities,
                    'activitycount'   => $sectiontotal,
                    'completedcount'  => $sectioncompleted,
                    'isopen'          => $containscurrent || $sectionnum <= 1,
                    'containscurrent' => $containscurrent,
                    'progress_text'   => $sectioncompleted . '/' . $sectiontotal,
                ];
            }
        }

        return $sections;
    }

    /**
     * Verifica si una actividad tiene vídeo.
     */
    private function is_video_activity(\cm_info $cm): bool {
        global $DB;

        if ($cm->modname === 'page') {
            $page = $DB->get_record('page', ['id' => $cm->instance], 'content');
            if ($page) {
                return !empty($this->extract_video_url($page->content));
            }
        } elseif ($cm->modname === 'url') {
            $url = $DB->get_record('url', ['id' => $cm->instance], 'externalurl');
            if ($url) {
                return !empty($this->extract_video_url($url->externalurl));
            }
        }

        return false;
    }

    /**
     * Verifica si una actividad está completada por el usuario actual.
     */
    private function is_activity_completed(\completion_info $completioninfo, \cm_info $cm): bool {
        if (!$completioninfo->is_enabled($cm)) {
            return false;
        }

        $data = $completioninfo->get_data($cm);
        return $data->completionstate == COMPLETION_COMPLETE
            || $data->completionstate == COMPLETION_COMPLETE_PASS;
    }

    /**
     * Obtiene el progreso general del curso.
     */
    private function get_course_progress(\completion_info $completioninfo, \course_modinfo $modinfo): array {
        $total = 0;
        $completed = 0;

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'label' || !$cm->uservisible) {
                continue;
            }
            if ($completioninfo->is_enabled($cm)) {
                $total++;
                if ($this->is_activity_completed($completioninfo, $cm)) {
                    $completed++;
                }
            }
        }

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'percentage' => $percentage,
            'completed'  => $completed,
            'total'      => $total,
            'text'       => $completed . '/' . $total . ' (' . $percentage . '%)',
        ];
    }
}
