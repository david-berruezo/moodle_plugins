<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// LISTADO DE INSTRUCTORES
// ==========================================================================
//
// URL amigable : /profesores
// URL interna  : /local/catalog/instructors.php
//
// Muestra todos los usuarios con rol de editingteacher o teacher,
// con tarjetas estilo Udemy: avatar, nombre, especialidad, nº de cursos,
// nº de estudiantes y rating.
//
// Parámetros GET:
//   ?q=nombre   → Búsqueda por nombre del instructor
//   ?page=2     → Paginación
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

$search  = optional_param('q',    '', PARAM_TEXT);
$page    = optional_param('page',  0, PARAM_INT);
$perpage = optional_param('perpage', 12, PARAM_INT);

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/catalog/instructors.php', array_filter([  // ✅ URL amigable
    'q'    => $search ?: null,
    'page' => $page   ?: null,
])));

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('instructors', 'local_catalog'));
$PAGE->set_heading(get_string('instructors', 'local_catalog'));

$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// --- Obtener instructores ---
$manager     = new \local_catalog\catalog_manager();
$result      = $manager->get_instructors_list($search, $page, $perpage);

// --- Contexto del template ---
$templatecontext = [
    // Instructores
    'instructors'    => $result['instructors'],
    'totalcount'     => $result['total'],
    'hasinstructors' => !empty($result['instructors']),
    // Búsqueda activa
    'activesearch'   => $search,
    // Paginación
    'pagination'     => $result['pagination'],
    'haspagination'  => $result['total'] > $perpage,
    // ── URLs de navegación ────────────────────────────────────────────────
    'homeurl'        => (new moodle_url('/'))->out(false),
    'catalogurl'     => (new moodle_url('/cursos'))->out(false),
    'searchaction'   => (new moodle_url('/profesores'))->out(false),
    'mycoursesurl'   => (new moodle_url('/mis-cursos'))->out(false),
    'loginurl'       => (new moodle_url('/login/index.php'))->out(false),
    'logouturl'      => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    'registerurl'    => (new moodle_url('/registro'))->out(false),
    'instructorsurl' => (new moodle_url('/profesores'))->out(false),
    'planurl'        => (new moodle_url('/plan-personal'))->out(false),
    'compareurl'     => (new moodle_url('/comparar-planes'))->out(false),
    'demourl'        => (new moodle_url('/solicitar-demo'))->out(false),
    'teachurl'       => (new moodle_url('/ensena-aqui'))->out(false),
    'termsurl'       => (new moodle_url('/terminos'))->out(false),
    'privacyurl'     => (new moodle_url('/privacidad'))->out(false),

];

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/instructors', $templatecontext);
echo $OUTPUT->footer();
