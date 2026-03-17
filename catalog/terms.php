<?php
// =============================================================================
// TERMS — Términos de uso
// URL amigable : /terminos
// URL interna  : /local/catalog/terms.php
// =============================================================================
// COPIAR ESTE BLOQUE A terms.php
// =============================================================================

require_once(__DIR__ . '/../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/terminos'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title('Términos de uso — ' . get_site()->fullname);
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

$isloggedin = isloggedin() && !isguestuser();

$templatecontext = [
    'isloggedin'   => $isloggedin,
    'homeurl'      => (new moodle_url('/'))->out(false),
    'loginurl'     => (new moodle_url('/login'))->out(false),
    'registerurl'  => (new moodle_url('/registro'))->out(false),
    'termsurl'     => (new moodle_url('/terminos'))->out(false),
    'privacyurl'   => (new moodle_url('/privacidad'))->out(false),
    'sitename'     => get_site()->fullname,
    'currentyear'  => date('Y'),
    'page_title'   => 'Términos de uso',
    'last_updated' => 'Enero 2026',
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/legal', $templatecontext);
echo $OUTPUT->footer();