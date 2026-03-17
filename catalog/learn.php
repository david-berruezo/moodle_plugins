<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// AULA VIRTUAL — Player de curso tipo Udemy
// ==========================================================================
//
// URL: /local/catalog/learn.php?id=COURSEID&cmid=ACTIVITYID
//
// Requiere que el usuario esté matriculado en el curso.
// Muestra:
//   - Barra superior con título del curso y progreso
//   - Sidebar derecha con temario (secciones + actividades)
//   - Área principal con el contenido de la actividad (vídeo, página, etc.)
//   - Navegación anterior/siguiente entre actividades
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

$courseid = optional_param('id', 0, PARAM_INT);
$slug     = optional_param('slug', '', PARAM_TEXT);
$cmid     = optional_param('cmid', 0,  PARAM_INT);

// Resolver slug → ID
$manager = new \local_catalog\catalog_manager();

if (empty($courseid) && !empty($slug)) {
    $courseid = $manager->get_course_by_slug($slug);
    if (!$courseid) {
        throw new moodle_exception('invalidcourseid', 'error');
    }
} elseif (empty($courseid)) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// --- Control de acceso según tipo de curso ---
$price = $manager->get_price($courseid);

$isenrolled = false;
$isguest = true;

if (isloggedin() && !isguestuser()) {
    $context = context_course::instance($courseid);
    $isenrolled = is_enrolled($context);
    $isguest = false;
    // Si está logueado y matriculado, experiencia completa
} elseif (!$price['is_free']) {
    // Curso de pago + no logueado → redirige al detalle
    // redirect(new moodle_url('/local/catalog/course.php', ['id' => $courseid]));
    redirect(new moodle_url('/cursos/' . $slug));
}

// Cursos gratuitos sin login → acceso en modo lectura (sin completación)
$PAGE->set_context(context_system::instance());

$course  = get_course($courseid);
$context = context_course::instance($courseid);

// --- Obtener datos del curso ---
$player     = new \local_catalog\course_player();
$playerdata = $player->get_player_data($course, $cmid  , $isenrolled, $isguest);

if (!$playerdata) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// --- Configurar la página ---
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/catalog/learn.php', [
    'id'   => $courseid,
    'cmid' => $playerdata['current_cmid'],
]));

$PAGE->set_pagelayout('embedded'); // Sin navbar ni footer de Moodle
$PAGE->set_title($course->fullname . ' - ' . $playerdata['current_name']);

// CSS
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// Añadir JS del catálogo
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/course_player', $playerdata);
echo $OUTPUT->footer();
