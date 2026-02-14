<?php
require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/sandbox/ej_ajax.php'));
$PAGE->set_title(get_string('exercise_ajax', 'local_sandbox'));
$PAGE->set_heading(get_string('exercise_ajax', 'local_sandbox'));

echo $OUTPUT->header();

echo <<<HTML
<div class="container mt-4">
    <h3>Ejercicio 2: core/ajax</h3>
    <p>Llama a un Web Service de Moodle desde JavaScript sin recargar la página.</p>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Llamada simple</h5>

            <div class="form-group mb-2">
                <label for="input-name">Tu nombre:</label>
                <input type="text" class="form-control" id="input-name" value="David" style="max-width: 300px;">
            </div>

            <div class="form-group mb-3">
                <label for="select-time">Momento del día:</label>
                <select class="form-control" id="select-time" style="max-width: 300px;">
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="evening">Evening</option>
                </select>
            </div>

            <button type="button" class="btn btn-primary" id="btn-call-ws">
                Llamar al Web Service
            </button>

            <div id="ws-result" class="mt-3" style="display:none;"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Llamada múltiple (batch)</h5>
            <p>Ejecuta 3 llamadas en una sola petición HTTP.</p>

            <button type="button" class="btn btn-secondary" id="btn-call-batch">
                Ejecutar batch de 3 llamadas
            </button>

            <div id="batch-result" class="mt-3" style="display:none;"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Manejo de errores</h5>
            <p>Intenta llamar a un Web Service que no existe para ver el manejo de errores.</p>

            <button type="button" class="btn btn-danger" id="btn-call-error">
                Provocar un error
            </button>

            <div id="error-result" class="mt-3" style="display:none;"></div>
        </div>
    </div>
</div>
HTML;

$PAGE->requires->js_call_amd('local_sandbox/ej_ajax', 'init');

echo $OUTPUT->footer();