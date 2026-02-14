<?php
require_once(__DIR__ . '/../../config.php');

// Requiere que el usuario esté logueado
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/sandbox/ej_notification.php'));
$PAGE->set_title(get_string('exercise_notification', 'local_sandbox'));
$PAGE->set_heading(get_string('exercise_notification', 'local_sandbox'));

echo $OUTPUT->header();

// Contenido HTML con botones para probar las notificaciones
echo <<<HTML
<div class="container mt-4">
    <h3>Ejercicio 1: core/notification</h3>
    <p>Pulsa los botones para ver los diferentes tipos de notificaciones de Moodle.</p>

    <div class="btn-group mb-3" role="group">
        <button type="button" class="btn btn-success" id="btn-success">
            Success
        </button>
        <button type="button" class="btn btn-danger" id="btn-error">
            Error
        </button>
        <button type="button" class="btn btn-warning" id="btn-warning">
            Warning
        </button>
        <button type="button" class="btn btn-info" id="btn-info">
            Info
        </button>
    </div>

    <hr>

    <h4>Notificación con redirección</h4>
    <p>Esta notificación se guarda en sesión y se muestra tras recargar la página:</p>
    <button type="button" class="btn btn-primary" id="btn-redirect">
        Notificar y recargar
    </button>

    <hr>

    <h4>Alert (diálogo modal nativo)</h4>
    <button type="button" class="btn btn-secondary" id="btn-alert">
        Mostrar alert modal
    </button>

    <hr>

    <h4>Confirm (diálogo de confirmación)</h4>
    <button type="button" class="btn btn-secondary" id="btn-confirm">
        Mostrar confirm modal
    </button>

    <div id="confirm-result" class="mt-2 p-2" style="display:none;"></div>
</div>
HTML;

// Cargar nuestro módulo AMD
$PAGE->requires->js_call_amd('local_sandbox/ej_notification', 'init');

echo $OUTPUT->footer();