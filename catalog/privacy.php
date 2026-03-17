<?php
// =============================================================================
// PRIVACY — Política de privacidad
// URL amigable : /privacidad
// URL interna  : /local/catalog/privacy.php
// =============================================================================
// COPIAR ESTE BLOQUE A privacy.php
// =============================================================================

require_once(__DIR__ . '/../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/privacidad'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title('Política de privacidad — ' . get_site()->fullname);
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

$isloggedin = isloggedin() && !isguestuser();

$templatecontext = [
    'isloggedin'   => $isloggedin,
    'sitename'     => get_site()->fullname,
    'currentyear'  => date('Y'),
    'page_title'   => 'Política de privacidad',
    'last_updated' => 'Enero 2026',
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

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/legal', $templatecontext);
echo $OUTPUT->footer();