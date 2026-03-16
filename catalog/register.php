<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// REGISTRO DE USUARIO
// ==========================================================================
//
// URL amigable : /registro
// URL interna  : /local/catalog/register.php
//
// Wrapper visual de la página de registro de Moodle, con el diseño
// del catálogo. Si el auto-registro de Moodle está desactivado, muestra
// un mensaje alternativo.
//
// Flujo:
//   1. Si el usuario ya tiene sesión → redirige a /mis-cursos
//   2. Si Moodle tiene auto-registro activado → muestra el formulario
//      nativo de Moodle embebido en el layout del catálogo
//   3. Tras el registro exitoso → redirige a /cursos
// ==========================================================================

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/login/lib.php');

// Si ya tiene sesión, redirigir
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/mis-cursos'));
}

// Verificar si el auto-registro está habilitado
$authplugins   = get_enabled_auth_plugins();
$registerenabled = false;
foreach ($authplugins as $pluginname) {
    $plugin = get_auth_plugin($pluginname);
    if ($plugin->can_signup()) {
        $registerenabled = true;
        break;
    }
}

// --- Configurar la página ---
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/catalog/register.php'));

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('createaccount'));
$PAGE->set_heading(get_string('createaccount'));

$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// --- Contexto del template ---
$templatecontext = [
    // Estado
    'registerenabled'  => $registerenabled,
    'nativeregisterurl'=> (new moodle_url('/login/signup.php'))->out(false),

    // URLs de navegación
    'catalogurl'       => (new moodle_url('/cursos'))->out(false),
    'loginurl'         => (new moodle_url('/login/index.php',
                              ['wantsurl' => (new moodle_url('/mis-cursos'))->out(false)]))->out(false),

    // Textos
    'sitename'         => format_string($SITE->fullname),
];

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/register', $templatecontext);
echo $OUTPUT->footer();
