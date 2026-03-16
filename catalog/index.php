<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// CATÁLOGO DE CURSOS — Página principal del plugin
// ==========================================================================
//
// URL: /local/catalog/index.php
//
// Parámetros GET:
//   ?cat=3          → Filtrar por subcategoría
//   ?q=react        → Búsqueda por texto
//   ?price=free     → Filtrar por precio (free|paid)
//   ?level=beginner → Filtrar por nivel
//   ?tag=hooks      → Filtrar por tag
//   ?page=2         → Paginación
//
// FLUJO:
// 1. Recoge los filtros de la URL
// 2. Llama a catalog_manager para obtener cursos filtrados
// 3. Pasa los datos al template Mustache
// 4. El template renderiza las tarjetas + filtros + paginación
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

// --- Parámetros de filtrado ---
$categoryid = optional_param('cat', 0, PARAM_INT);
$search     = optional_param('q', '', PARAM_TEXT);
$price      = optional_param('price', '', PARAM_ALPHA);
$level      = optional_param('level', '', PARAM_INT);
$tag        = optional_param('tag', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT);
$perpage    = optional_param('perpage', 3, PARAM_INT);

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/catalog/index.php', array_filter([
    'cat'   => $categoryid ?: null,
    'q'     => $search     ?: null,
    'price' => $price      ?: null,
    'level' => $level      ?: null,
    'tag'   => $tag        ?: null,
    'page'  => $page       ?: null,
])));

// $PAGE->set_pagelayout('standard');
$PAGE->set_pagelayout('embedded'); // Sin navbar ni footer de Moodle
$PAGE->set_title(get_string('catalog', 'local_catalog'));
$PAGE->set_heading(get_string('catalog', 'local_catalog'));

// Añadir CSS del catálogo
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// Añadir JS del catálogo
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// --- Obtener datos ---
$manager = new \local_catalog\catalog_manager();

// ── Estado del usuario ────────────────────────────────────────────────────────
$isloggedin = isloggedin() && !isguestuser();
$initials   = '';
$username   = '';

if ($isloggedin) {
    $initials = strtoupper(
        substr($USER->firstname, 0, 1) . substr($USER->lastname, 0, 1)
    );
    $username = fullname($USER);
}

$menudata = $manager->get_menu_data();

// Filtros activos
$filters = [
    'category' => $categoryid,
    'search'   => $search,
    'price'    => $price,
    'level'    => $level,
    'tag'      => $tag,
];

// Obtener cursos filtrados con paginación
$result = $manager->get_courses($filters, $page, $perpage);

/*
echo "-------------- cursos -----------";
echo "<pre>";
print_r($result);
echo "<pre>";
*/

// echo "-------------- filtros -----------";
// Obtener datos para los filtros laterales
$filterdata = $manager->get_filter_data($filters);

/*
echo "<pre>";
print_r($filterdata);
echo "<pre>";
*/

// --- Construir URL base para filtros (sin el parámetro que se cambia) ---
$baseurl = new moodle_url('/cursos');

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
    // variables del menu
    'username'     => $username,
    'initials'     => $initials,
    // ── Mega-menú jerárquico ──────────────────────────────────────────────
    'menu_triggers' => $menudata['triggers'],   // columna izquierda
    'menu_panels'   => $menudata['panels'],     // columna derecha
    'has_menu'      => $menudata['has_menu'],
    // Cursos
    'courses'       => $result['courses'],
    'totalcourses'  => $result['total'],
    'hascourses'    => !empty($result['courses']),
    // Filtros laterales
    'categories'    => $filterdata['categories'],
    'levels'        => $filterdata['levels'],
    'priceoptions'  => $filterdata['prices'],
    'tags'          => $filterdata['tags'],
    // Filtros activos (para marcar los seleccionados)
    'activecategory' => $categoryid,
    'activesearch'   => $search,
    'activeprice'    => $price,
    'activelevel'    => $level,
    'activetag'      => $tag,
    // Paginación
    'pagination'    => $result['pagination'],
    'haspagination' => $result['total'] > $perpage,
    // URLs
    'baseurl'       => $baseurl->out(false),
    'isloggedin'   => $isloggedin,
];

$template_context_more_content = array_merge(
    $templatecontext,
    // $manager->get_navbar_data(),
    $manager->get_home_data(),
    $manager->get_footer_data()
);

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/catalog', $template_context_more_content);
echo $OUTPUT->footer();
