<?php
// =============================================================================
// REGISTRO — Campus Virtual Aubay
// URL amigable  : /registro
// URL interna   : /local/catalog/register.php
// .htaccess     : RewriteRule ^registro/?$ /local/catalog/register.php [L,QSA]
//
// Flujo:
//   GET  → muestra el formulario
//   POST → valida campos → crea usuario con user_create_user()
//           · OK    → página de éxito ("revisa tu email")
//           · Error → vuelve al formulario con mensajes por campo
// =============================================================================

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

// ── Si ya está logueado, redirigir ────────────────────────────────────────────
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'));
}

// ── Configurar página ─────────────────────────────────────────────────────────
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/registro'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('register_title', 'local_catalog') . ' — ' . get_site()->fullname);

$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// ── Estado del formulario ─────────────────────────────────────────────────────
$errors    = [];
$success   = false;
$firstname = '';
$lastname  = '';
$email     = '';

// ── Procesar POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_sesskey();

    $firstname  = optional_param('firstname',  '', PARAM_TEXT);
    $lastname   = optional_param('lastname',   '', PARAM_TEXT);
    $email      = optional_param('email',      '', PARAM_EMAIL);
    $password   = optional_param('password',   '', PARAM_RAW);
    $acceptterms = optional_param('acceptterms', 0, PARAM_INT);

    // ── Validaciones ─────────────────────────────────────────────────────────
    if (empty($firstname)) {
        $errors['firstname'] = get_string('reg_error_firstname', 'local_catalog');
    }

    if (empty($lastname)) {
        $errors['lastname'] = get_string('reg_error_lastname', 'local_catalog');
    }

    if (empty($email)) {
        $errors['email'] = get_string('reg_error_email_empty', 'local_catalog');
    } else if (!validate_email($email)) {
        $errors['email'] = get_string('reg_error_email_invalid', 'local_catalog');
    } else if ($DB->record_exists('user', ['email' => $email, 'deleted' => 0])) {
        $errors['email'] = get_string('reg_error_email_exists', 'local_catalog');
    }

    if (empty($password)) {
        $errors['password'] = get_string('reg_error_password_empty', 'local_catalog');
    } else {
        $errmsg = '';
        if (!check_password_policy($password, $errmsg)) {
            $errors['password'] = $errmsg;
        }
    }

    if (!$acceptterms) {
        $errors['acceptterms'] = get_string('reg_error_terms', 'local_catalog');
    }

    // ── Crear usuario ─────────────────────────────────────────────────────────
    if (empty($errors)) {

        // Generar username único desde el email
        $baseusername = strtolower(
            preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $email))
        );
        $baseusername = substr($baseusername, 0, strpos($baseusername . '@', '@'));
        $username     = $baseusername;
        $counter      = 1;

        while ($DB->record_exists('user', ['username' => $username])) {
            $username = $baseusername . $counter;
            $counter++;
        }

        $newuser                = new stdClass();
        $newuser->firstname     = $firstname;
        $newuser->lastname      = $lastname;
        $newuser->email         = $email;
        $newuser->username      = $username;
        $newuser->password      = 'to be hashed by user_create_user';
        $newuser->auth          = 'manual';
        $newuser->confirmed     = 0;    // requiere confirmación por email
        $newuser->mnethostid    = $CFG->mnet_localhost_id;
        $newuser->lang          = current_language();
        $newuser->country       = $CFG->country;
        $newuser->timecreated   = time();
        $newuser->timemodified  = time();

        try {
            // user_create_user hashea la contraseña y dispara eventos Moodle
            $newuser->password = $password;
            $userid = user_create_user($newuser, true, true);

            // Enviar email de confirmación
            $createduser = $DB->get_record('user', ['id' => $userid]);
            send_confirmation_email($createduser);

            $success = true;

        } catch (Exception $e) {
            $errors['general'] = get_string('reg_error_general', 'local_catalog');
        }
    }
}

// ── URLs comunes ──────────────────────────────────────────────────────────────
$commonurls = [
    'homeurl'        => (new moodle_url('/'))->out(false),
    'loginurl'       => (new moodle_url('/login'))->out(false),
    'registerurl'    => (new moodle_url('/registro'))->out(false),
    'registeractionurl' => (new moodle_url('/local/catalog/registro.php'))->out(false),
    'catalogurl'     => (new moodle_url('/cursos'))->out(false),
    'termsurl'       => (new moodle_url('/terminos'))->out(false),
    'privacyurl'     => (new moodle_url('/privacidad'))->out(false),
    'sitename'       => get_site()->fullname,
    'currentyear'    => date('Y'),
    'sesskey'        => sesskey(),
];

// ── Datos para el template ────────────────────────────────────────────────────
$templatecontext = array_merge($commonurls, [
    'success'          => $success,

    // Errores globales
    'hasgeneralerror'  => !empty($errors['general']),
    'generalerror'     => $errors['general'] ?? '',

    // Campos con sus valores y errores individuales
    'firstname'        => s($firstname),
    'lastname'         => s($lastname),
    'email'            => s($email),

    'firstname_error'  => $errors['firstname']   ?? '',
    'lastname_error'   => $errors['lastname']    ?? '',
    'email_error'      => $errors['email']       ?? '',
    'password_error'   => $errors['password']    ?? '',
    'terms_error'      => $errors['acceptterms'] ?? '',

    'has_firstname_error' => !empty($errors['firstname']),
    'has_lastname_error'  => !empty($errors['lastname']),
    'has_email_error'     => !empty($errors['email']),
    'has_password_error'  => !empty($errors['password']),
    'has_terms_error'     => !empty($errors['acceptterms']),
]);

// ── Render ────────────────────────────────────────────────────────────────────
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/register', $templatecontext);
echo $OUTPUT->footer();
