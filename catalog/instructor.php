<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// PERFIL DE INSTRUCTOR
// ==========================================================================
//
// URL amigable : /profesores/{slug}
// URL interna  : /local/catalog/instructor.php?slug={slug}
//                /local/catalog/instructor.php?id={userid}  (compatibilidad)
//
// Muestra la página pública del instructor:
//   - Avatar, nombre, bio
//   - Estadísticas (cursos, estudiantes, rating)
//   - Grid de cursos que imparte
//
// El slug se genera a partir del nombre del usuario:
//   Juan García → juan-garcia
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

$userid = optional_param('id',   0,  PARAM_INT);
$slug   = optional_param('slug', '', PARAM_TEXT);

// --- Obtener datos del instructor ---
$manager = new \local_catalog\catalog_manager();

if (empty($userid) && !empty($slug)) {
    $userid = $manager->get_instructor_by_slug($slug);
    if (!$userid) {
        throw new moodle_exception('invaliduser', 'error');
    }
} elseif (empty($userid)) {
    throw new moodle_exception('invaliduser', 'error');
}

$instructordata = $manager->get_instructor_profile($userid);
if (!$instructordata) {
    throw new moodle_exception('invaliduser', 'error');
}

// Obtener slug definitivo
$slug = $slug ?: $instructordata['slug'];

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/catalog/instructor.php'));

$PAGE->set_pagelayout('standard');
$PAGE->set_title($instructordata['fullname']);
$PAGE->set_heading($instructordata['fullname']);

$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/instructor_profile', $instructordata);
echo $OUTPUT->footer();
