<?php
// =============================================================================
// LOGIN — Campus Virtual Aubay
// URL amigable : /login
// URL interna  : /local/catalog/login.php
// .htaccess    : RewriteRule ^login/?$ /local/catalog/login.php [L,QSA]
//
// Flujo:
//   GET  → muestra el formulario
//   POST → autentica con authenticate_user_login()
//           · Admin / Manager    → /admin/
//           · Profesor           → /mis-cursos
//           · Estudiante         → / (home del campus)
//           · Error              → vuelve al formulario con mensaje
// =============================================================================

require_once(__DIR__ . '/../../config.php');

// ── Si ya está logueado, redirigir ────────────────────────────────────────────
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'));
}

// ── Configurar página ─────────────────────────────────────────────────────────
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/login'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('login', 'local_catalog') . ' — ' . get_site()->fullname);

$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// ── Procesar POST ─────────────────────────────────────────────────────────────
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_sesskey(); // CSRF protection

    $username = optional_param('username', '', PARAM_RAW_TRIMMED);
    $password = optional_param('password', '', PARAM_RAW);

    if (empty($username) || empty($password)) {
        $error = get_string('login_error_empty', 'local_catalog');
    } else {
        // Moodle acepta tanto username como email en authenticate_user_login
        // si la opción "Permitir login con email" está activada en admin
        $user = authenticate_user_login($username, $password);

        if ($user) {
            complete_user_login($user);

            // ── Redirigir según rol ───────────────────────────────────────────
            if (is_siteadmin($user)) {
                // Administrador → backend Moodle
                redirect(new moodle_url('/admin/'));

            } else if (
                has_capability('moodle/course:update', context_system::instance(), $user) ||
                has_capability('moodle/site:config', context_system::instance(), $user)
            ) {
                // Manager / profesor con permisos globales → backend
                redirect(new moodle_url('/admin/'));

            } else if (
                has_capability('moodle/course:viewhiddencourses', context_system::instance(), $user)
            ) {
                // Profesor de curso → mis cursos del frontend
                redirect(new moodle_url('/mis-cursos'));

            } else {
                // Estudiante → home del campus
                // Si venía de una URL concreta (?wantsurl=), respetar esa URL
                $wantsurl = optional_param('wantsurl', '', PARAM_LOCALURL);
                if (!empty($wantsurl)) {
                    redirect(new moodle_url($wantsurl));
                } else {
                    redirect(new moodle_url('/'));
                }
            }

        } else {
            // Login fallido — Moodle ya registra el intento fallido internamente
            $error = get_string('login_error_invalid', 'local_catalog');
        }
    }
}

// ── Datos para el template ────────────────────────────────────────────────────
$templatecontext = [
    // URLs
    'homeurl' => (new moodle_url('/'))->out(false),
    'catalogurl' => (new moodle_url('/cursos'))->out(false),
    'searchaction' => (new moodle_url('/cursos'))->out(false),
    'mycoursesurl' => (new moodle_url('/mis-cursos'))->out(false),
    'loginurl' => (new moodle_url('/login'))->out(false),
    'logouturl'      => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    'registerurl' => (new moodle_url('/registro'))->out(false),
    'instructorsurl' => (new moodle_url('/profesores'))->out(false),
    'planurl'        => (new moodle_url('/plan-personal'))->out(false),
    'compareurl'     => (new moodle_url('/comparar-planes'))->out(false),
    'demourl'        => (new moodle_url('/solicitar-demo'))->out(false),
    'teachurl'       => (new moodle_url('/ensena-aqui'))->out(false),
    'termsurl'       => (new moodle_url('/terminos'))->out(false),
    'privacyurl'     => (new moodle_url('/privacidad'))->out(false),
    'forgoturl' => (new moodle_url('/login/forgot_password.php'))->out(false),
    // Sesskey para CSRF
    'sesskey' => sesskey(),

    // Estado del formulario
    'haserror' => !empty($error),
    'errormsg' => $error,
    'username' => s($username), // valor previo si hay error

    // Meta
    'sitename' => get_site()->fullname,
    'currentyear' => date('Y'),

    // wantsurl — para redirigir al destino original tras login
    'wantsurl' => optional_param('wantsurl', '', PARAM_LOCALURL),

    // Menú jerárquico
    'has_menu' => false, // login no necesita mega-menú completo
];

// ── Render ────────────────────────────────────────────────────────────────────
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/login', $templatecontext);
echo $OUTPUT->footer();