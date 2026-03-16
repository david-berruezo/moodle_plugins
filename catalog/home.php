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

// ── Configurar página ─────────────────────────────────────────────────────────
$PAGE->set_context(context_system::instance());

//$PAGE->set_url(new moodle_url('/'));
$PAGE->set_url(new moodle_url('/local/catalog/index.php'));

$PAGE->set_pagelayout('embedded'); // Sin navbar ni footer de Moodle
$PAGE->set_title(get_string('home_title', 'local_catalog'));
$PAGE->set_heading(get_string('pluginname', 'local_catalog'));

// ── Assets ───────────────────────────────────────────────────────────────────
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// Añadir JS del catálogo
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// ── Datos dinámicos ───────────────────────────────────────────────────────────
$manager  = new \local_catalog\catalog_manager();

$homedata = $manager->get_home_data();

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
// ── Categorías de cursos tendencia ───────────────────────
$cursos_tendencia = $manager->get_trending_courses(9);
if (is_array($cursos_tendencia) && count($cursos_tendencia) > 0) {
    $has_trending = true;
}else if(is_array($cursos_tendencia) && count($cursos_tendencia) == 0){
    $has_trending = false;
}


// ── Testimonios desde settings ────────────────────────────────────────────────
// Configurables en Admin → Plugins → Plugins locales → Campus Virtual → Ajustes
$testimonials = [];
for ($i = 1; $i <= 3; $i++) {
    $name = get_config('local_catalog', "testimonial{$i}_name");
    $text = get_config('local_catalog', "testimonial{$i}_text");
    if (!empty($name) && !empty($text)) {
        $parts    = explode(' ', trim($name));
        $initials_t = strtoupper(
            substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1)
        );
        $testimonials[] = [
            'name'        => $name,
            'jobtitle'    => get_config('local_catalog', "testimonial{$i}_role") ?: '',
            'hasjobtitle' => !empty(get_config('local_catalog', "testimonial{$i}_role")),
            'testimonial' => $text,
            'initials'    => $initials_t,
        ];
    }
}

// Si no hay testimonios configurados, usar los del manager (fallback con ejemplos)
if (empty($testimonials)) {
    $testimonials = $manager->get_testimonials_fallback();
}


// ── Textos de "Por qué Campus Virtual" desde settings ────────────────────────
$features = [];
for ($i = 1; $i <= 4; $i++) {
    $features[] = [
        'title' => get_config('local_catalog', "feature{$i}_title")
            ?: get_string("feature{$i}_title_default", 'local_catalog'),
        'text'  => get_config('local_catalog', "feature{$i}_text")
            ?: get_string("feature{$i}_text_default",  'local_catalog'),
        'icon'  => $i, // 1=estrella 2=personas 3=gráfica — el mustache elige el SVG
    ];
}

$menudata = $manager->get_menu_data();

/*
// -- Tags
$tags = \core_tag_tag::get_item_tags(
    'core',
    'course',
    4
);

echo "----------- mostramos tags cursos ---------- <br>";
foreach ($tags as $tag) {
    echo "<pre>";
    echo $tag->name;
    echo "</pre>";
}
echo "----------- mostramos tags usuarios ---------- <br>";

$userid = 2; // ID del usuario

$tags = \core_tag_tag::get_item_tags(
    'core',       // component
    'user',       // itemtype
    $userid       // itemid
);

foreach ($tags as $tag) {
    echo "<pre>";
    echo $tag->name . '<br>';
    echo "</pre>";
}

echo "----------- mostramos tags paginas ---------- <br>";

// --- PAGINA ---
// Opción 1: si tienes el id de la página (mdl_page.id)
$cm = get_coursemodule_from_instance('page', 7, 4);
$cmid = $cm->id;

$tags = \core_tag_tag::get_item_tags(
    'core',            // component
    'course_modules',  // itemtype (genérico para actividades simples)
    $cmid              // itemid = course module id
);

foreach ($tags as $tag) {
    echo "<pre>";
    echo $tag->name . '<br>';
    echo "</pre>";
}
*/

//die();

// ── Contexto completo del template ───────────────────────────────────────────
$templatecontext = array_merge($homedata, [
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
    // Mega-menú
    'menu_triggers' => $menudata['triggers'],   // columna izquierda
    'menu_panels'   => $menudata['panels'],     // columna derecha
    'has_menu'      => $menudata['has_menu'],
    // Usuario
    'isloggedin'   => $isloggedin,
    'sitename'     => get_site()->fullname,
    'currentyear'  => date('Y'),
    // cursos tendencia
    'cursos_tendencia' => $cursos_tendencia,
    'has_trending' => true,
    'courseurl' => '',
    // Features configurables
    'features'     => $features,
    // Testimonios
    'testimonials'     => $testimonials,
    'has_testimonials' => !empty($testimonials),
]);

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/home',  $templatecontext);
echo $OUTPUT->footer();
