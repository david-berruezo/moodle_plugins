<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// DETALLE DEL CURSO — Página tipo Udemy
// ==========================================================================
//
// URL: /local/catalog/course.php?id=X
//
// Esta página muestra toda la información del curso ANTES de inscribirse:
// - Hero con breadcrumbs, título, descripción, instructor, estadísticas
// - Sidebar con imagen/video, precio, botón de inscripción
// - Objetivos de aprendizaje
// - Temario (secciones + actividades)
// - Requisitos previos
// - Descripción extendida
// - Información del instructor
// - Cursos relacionados (misma categoría)
// - Más cursos del instructor
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

$courseid = optional_param('id', 0, PARAM_INT);
$slug     = optional_param('slug', '', PARAM_TEXT);

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

// Obtener datos del curso
$coursedata = $manager->get_course_detail($courseid);

if (!$coursedata) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// Obtener el slug definitivo para la URL amigable
$slug = $slug ?: \local_catalog\catalog_manager::slugify(
    get_field('course', 'shortname', 'id', $courseid) ?: $coursedata['fullname']
);

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

// ✅ URL NO AMIGABLE
$PAGE->set_url(new moodle_url('/local/catalog/course.php', ['id' => $courseid]));

//$PAGE->set_pagelayout('standard');
$PAGE->set_pagelayout('embedded'); // Sin navbar ni footer de Moodle
$PAGE->set_title($coursedata['fullname']);
$PAGE->set_heading($coursedata['fullname']);

// CSS
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// Añadir JS del catálogo
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/course_detail', $coursedata);
echo $OUTPUT->footer();
