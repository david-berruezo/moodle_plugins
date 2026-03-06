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

$courseid = required_param('id', PARAM_INT);

// --- Obtener datos del curso ---
$manager = new \local_catalog\catalog_manager();
$coursedata = $manager->get_course_detail($courseid);

if (!$coursedata) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/catalog/course.php', ['id' => $courseid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title($coursedata['fullname']);
$PAGE->set_heading($coursedata['fullname']);

// CSS
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/course_detail', $coursedata);
echo $OUTPUT->footer();
