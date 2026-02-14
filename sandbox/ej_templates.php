<?php
require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/sandbox/ej_templates.php'));
$PAGE->set_title(get_string('exercise_templates', 'local_sandbox'));
$PAGE->set_heading(get_string('exercise_templates', 'local_sandbox'));

echo $OUTPUT->header();

echo <<<HTML
<div class="container mt-4">
    <h3>Ejercicio 3: core/templates</h3>
    <p>Renderiza plantillas Mustache desde JavaScript sin recargar la página.</p>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Renderizar una tarjeta de saludo</h5>

            <div class="form-group mb-2">
                <label for="tpl-name">Nombre:</label>
                <input type="text" class="form-control" id="tpl-name" value="David" style="max-width:300px;">
            </div>
            <div class="form-group mb-3">
                <label for="tpl-greeting">Saludo:</label>
                <input type="text" class="form-control" id="tpl-greeting"
                       value="Welcome to the Moodle templates exercise!" style="max-width:400px;">
            </div>

            <button type="button" class="btn btn-primary" id="btn-render-card">
                Renderizar template
            </button>

            <!-- Aquí se inyectará el HTML renderizado -->
            <div id="card-output" class="mt-3"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Renderizar lista de usuarios</h5>
            <p>Renderiza un template más complejo con arrays y condicionales.</p>

            <button type="button" class="btn btn-secondary" id="btn-render-list">
                Renderizar lista con datos
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btn-render-empty">
                Renderizar lista vacía
            </button>

            <div id="list-output" class="mt-3"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Renderizar y ejecutar JS del template</h5>
            <p><code>Templates.renderForPromise()</code> devuelve tanto el HTML
               como el JS que pueda contener el template.</p>

            <button type="button" class="btn btn-info" id="btn-render-promise">
                Renderizar con renderForPromise
            </button>

            <div id="promise-output" class="mt-3"></div>
        </div>
    </div>
</div>
HTML;

$PAGE->requires->js_call_amd('local_sandbox/ej_templates', 'init');

echo $OUTPUT->footer();