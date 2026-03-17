<?php
require_once(__DIR__ . '/../../config.php');

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

// $PAGE->set_pagelayout('standard');
$PAGE->set_pagelayout('embedded'); // Sin navbar ni footer de Moodle
$PAGE->set_title(get_string('catalog', 'local_catalog'));
$PAGE->set_heading(get_string('catalog', 'local_catalog'));

// Añadir CSS del catálogo
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// Añadir JS del catálogo
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// --- Preparar contexto para el template ---
$templatecontext = [
    // ── URLs de navegación ────────────────────────────────────────────────
    'homeurl'        => (new moodle_url('/'))->out(false),
    'catalogurl'     => (new moodle_url('/cursos'))->out(false),
    'searchaction'   => (new moodle_url('/cursos'))->out(false),
    'mycoursesurl'   => (new moodle_url('/mis-cursos'))->out(false),
    'loginurl'       => (new moodle_url('/login/index.php'))->out(false),
    'logouturl'      => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    'registerurl'    => (new moodle_url('/registro'))->out(false),
    'instructorsurl' => (new moodle_url('/profesores'))->out(false),
    'planurl'        => (new moodle_url('/plan-personal'))->out(false),
    'compareurl'     => (new moodle_url('/comparar-planes'))->out(false),
    'demourl'        => (new moodle_url('/solicitar-demo'))->out(false),
    'teachurl'       => (new moodle_url('/ensena-aqui'))->out(false),
    'privacyurl'     => '#', // Sustituir por URL real de privacidad
];

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/teach', $templatecontext);
echo $OUTPUT->footer();