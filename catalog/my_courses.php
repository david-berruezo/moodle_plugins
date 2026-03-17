<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// MIS CURSOS — Dashboard del estudiante
// ==========================================================================
//
// URL amigable : /mis-cursos
// URL interna  : /local/catalog/my_courses.php
//
// Requiere login. Muestra:
//   - Grid de cursos en los que el usuario está matriculado
//   - Progreso por curso (% completado)
//   - Acceso rápido al player (continuar donde lo dejó)
//   - Estado: en progreso / completado
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

// Requiere login
require_login();

if (isguestuser()) {
    redirect(new moodle_url('/login/index.php',
        ['wantsurl' => (new moodle_url('/mis-cursos'))->out(false)]));
}

// --- Parámetros ---
$filter  = optional_param('filter', 'all',  PARAM_ALPHA); // all | inprogress | completed
$page    = optional_param('page',    0,     PARAM_INT);
$perpage = optional_param('perpage', 12,    PARAM_INT);

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/catalog/my_courses.php', array_filter([  // ✅ URL amigable
    'filter' => $filter !== 'all' ? $filter : null,
    'page'   => $page ?: null,
])));

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mycourses', 'local_catalog'));
$PAGE->set_heading(get_string('mycourses', 'local_catalog'));

$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// --- Obtener cursos del usuario ---
$manager = new \local_catalog\catalog_manager();
$result  = $manager->get_my_courses($USER->id, $filter, $page, $perpage);

// --- Contexto del template ---
$templatecontext = [
    // Cursos
    'courses'        => $result['courses'],
    'totalcourses'   => $result['total'],
    'hascourses'     => !empty($result['courses']),
    // Filtros de estado
    'filterall'       => $filter === 'all',
    'filterinprogress'=> $filter === 'inprogress',
    'filtercompleted' => $filter === 'completed',
    'filterallurl'    => (new moodle_url('/mis-cursos'))->out(false),
    'filterprogressurl'=> (new moodle_url('/mis-cursos', ['filter' => 'inprogress']))->out(false),
    'filtercompletedurl'=> (new moodle_url('/mis-cursos', ['filter' => 'completed']))->out(false),
    // Paginación
    'pagination'     => $result['pagination'],
    'haspagination'  => $result['total'] > $perpage,
    // Usuario
    'username'       => fullname($USER),
    'useravatarurl'  => (new user_picture($USER, ['size' => 100]))->get_url($PAGE)->out(false),
    // Navegación
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
    'termsurl'       => (new moodle_url('/terminos'))->out(false),
    'privacyurl'     => (new moodle_url('/privacidad'))->out(false),
];

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/my_courses', $templatecontext);
echo $OUTPUT->footer();
