<?php
// local/sandbox/ej_combined.php
require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/sandbox/ej_combined.php'));
$PAGE->set_title(get_string('exercise_combined', 'local_sandbox'));
$PAGE->set_heading(get_string('exercise_combined', 'local_sandbox'));

echo $OUTPUT->header();

echo <<<HTML
<div class="container mt-4">
    <h3>Ejercicio 4: Combinando AJAX + Templates + Notifications</h3>
    <p>Este es el patrón completo que usarás en desarrollo real:
       llamar a un Web Service, renderizar la respuesta con un template
       y notificar al usuario.</p>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Generador de saludos</h5>

            <div class="form-inline mb-3">
                <label for="combined-name" class="mr-2">Nombre:</label>
                <input type="text" class="form-control mr-3" id="combined-name"
                       value="David" style="max-width:200px;">

                <label for="combined-time" class="mr-2">Momento:</label>
                <select class="form-control mr-3" id="combined-time">
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="evening">Evening</option>
                </select>

                <button type="button" class="btn btn-primary" id="btn-combined">
                    <i class="fa fa-magic"></i> Generar
                </button>
            </div>

            <!-- Spinner de carga -->
            <div id="combined-loading" class="text-center" style="display:none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Llamando al Web Service...</p>
            </div>

            <!-- Resultado renderizado -->
            <div id="combined-output"></div>

            <!-- Historial -->
            <div id="combined-history" class="mt-4">
                <h6>Historial de llamadas:</h6>
                <ul id="history-list" class="list-group"></ul>
            </div>
        </div>
    </div>
</div>
HTML;

$PAGE->requires->js_call_amd('local_sandbox/ej_combined', 'init');

echo $OUTPUT->footer();