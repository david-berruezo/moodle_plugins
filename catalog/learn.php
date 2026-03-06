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

$courseid = required_param('id', PARAM_INT);
$cmid     = optional_param('cmid', 0, PARAM_INT);

// --- Requiere login y matrícula ---
require_login($courseid);

$course  = get_course($courseid);
$context = context_course::instance($courseid);

// --- Obtener datos del curso ---
$manager = new \local_catalog\course_player();
$playerdata = $manager->get_player_data($course, $cmid);

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

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/course_player', $playerdata);
echo $OUTPUT->footer();
