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
$level      = optional_param('level', '', PARAM_ALPHA);
$tag        = optional_param('tag', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT);
$perpage    = optional_param('perpage', 3, PARAM_INT);

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/catalog/index.php', [
    'cat'   => $categoryid,
    'q'     => $search,
    'price' => $price,
    'level' => $level,
    'tag'   => $tag,
    'page'  => $page,
]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('catalog', 'local_catalog'));
$PAGE->set_heading(get_string('catalog', 'local_catalog'));

// Añadir CSS del catálogo
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// --- Obtener datos ---
$manager = new \local_catalog\catalog_manager();

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

// Obtener datos para los filtros laterales
$filterdata = $manager->get_filter_data($filters);

// --- Construir URL base para filtros (sin el parámetro que se cambia) ---
$baseurl = new moodle_url('/local/catalog/index.php');

// --- Preparar contexto para el template ---
$templatecontext = [
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
    'searchaction'  => $baseurl->out(false),
];

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/catalog', $templatecontext);
echo $OUTPUT->footer();
