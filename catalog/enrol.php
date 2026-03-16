<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// INSCRIPCIÓN AL CURSO
// ==========================================================================
//
// URL amigable : /inscribirse/{slug}
// URL interna  : /local/catalog/enrol.php?slug={slug}
//
// Flujo de inscripción:
//   1. Usuario no logueado → redirige a /login con wantsurl de vuelta
//   2. Ya matriculado → redirige directamente al player
//   3. Curso de pago → redirige al proceso de pago nativo de Moodle
//   4. Curso gratuito → matricula directamente y redirige al player
// ==========================================================================

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/enrol/locallib.php');

$slug = required_param('slug', PARAM_TEXT);

// --- Resolver slug → course ---
$manager  = new \local_catalog\catalog_manager();
$courseid = $manager->get_course_by_slug($slug);

if (!$courseid) {
    throw new moodle_exception('invalidcourseid', 'error');
}

$course = get_course($courseid);

// URLs amigables relevantes
$courseurl  = new moodle_url('/cursos/' . $slug);
$learnurl   = new moodle_url('/cursos/' . $slug . '/ver');
$enrolurl   = new moodle_url('/inscribirse/' . $slug);
$loginurl   = new moodle_url('/login/index.php', ['wantsurl' => $enrolurl->out(false)]);

// --- Flujo 1: No logueado → login ---
if (!isloggedin() || isguestuser()) {
    redirect($loginurl);
}

$context    = context_course::instance($courseid);
$isenrolled = is_enrolled($context, $USER);

// --- Flujo 2: Ya matriculado → player ---
if ($isenrolled) {
    redirect($learnurl);
}

// --- Flujo 3/4: Procesar inscripción ---
$price = $manager->get_price($courseid);

if (!$price['is_free']) {
    // Curso de pago → delegar al sistema de inscripción nativo de Moodle
    redirect(new moodle_url('/enrol/index.php', ['id' => $courseid]));
}

// Curso gratuito → intentar auto-inscripción
$enrolplugins = enrol_get_instances($courseid, true);
$enrolled = false;

foreach ($enrolplugins as $instance) {
    $plugin = enrol_get_plugin($instance->enrol);
    if ($plugin && $instance->enrol === 'self' && $plugin->can_self_enrol($instance) === true) {
        $plugin->enrol_self($instance);
        $enrolled = true;
        break;
    }
    if ($plugin && $instance->enrol === 'guest') {
        // Modo invitado no genera matrícula real; ignorar
        continue;
    }
}

if ($enrolled) {
    // Inscripción exitosa → player
    redirect($learnurl, get_string('enrolsuccess', 'local_catalog'), null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    // Sin método de auto-inscripción disponible → formulario nativo de Moodle
    redirect(new moodle_url('/enrol/index.php', ['id' => $courseid]));
}
