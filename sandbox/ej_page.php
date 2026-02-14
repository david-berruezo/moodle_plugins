<?php
// local/sandbox/ej_page.php
// Ejercicio 4.4: Explorar $PAGE y sus layouts
// EJECUTAR DESDE NAVEGADOR
require_once(__DIR__ . '/../../config.php');
require_login();

// --- Configurar $PAGE ---
$PAGE->set_url(new moodle_url('/local/sandbox/ej_page.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ejercicio 4.4: $PAGE');
$PAGE->set_heading('Explorar $PAGE — Layouts y Configuración');
$PAGE->set_pagelayout('standard'); // Prueba: standard, admin, course, popup, embedded, maintenance

// Breadcrumbs personalizados
$PAGE->navbar->add('Sandbox', new moodle_url('/local/sandbox/'));
$PAGE->navbar->add('Ejercicio 4.4');

// Añadir CSS inline
/*
$PAGE->requires->css_init_code('
    .sandbox-box { background: #e3f2fd; border: 2px solid #1976d2; padding: 15px; margin: 10px 0; border-radius: 8px; }
    .sandbox-highlight { color: #d32f2f; font-weight: bold; }
');
*/

echo $OUTPUT->header();

echo $OUTPUT->heading('Configuración de $PAGE', 3);

// Mostrar propiedades de $PAGE
echo html_writer::start_div('sandbox-box');
echo html_writer::tag('p', 'URL: ' . $PAGE->url->out());
echo html_writer::tag('p', 'Título: ' . $PAGE->title);
echo html_writer::tag('p', 'Heading: ' . $PAGE->heading);
echo html_writer::tag('p', 'Layout: ' . $PAGE->pagelayout);
echo html_writer::tag('p', 'Context: ' . $PAGE->context->get_context_name());
echo html_writer::tag('p', 'Body ID: ' . $PAGE->bodyid);
echo html_writer::end_div();

// Ejemplo de diferentes layouts
echo $OUTPUT->heading('Layouts Disponibles en Moodle', 3);

$layouts = [
    'standard' => 'Layout normal con bloques laterales',
    'admin' => 'Para páginas de administración',
    'course' => 'Dentro de un curso',
    'incourse' => 'Actividad dentro de un curso',
    'popup' => 'Ventana popup sin navegación',
    'embedded' => 'Sin cabecera ni pie (para iframes)',
    'maintenance' => 'Página de mantenimiento',
    'login' => 'Página de login',
    'report' => 'Para informes',
];

$layout_table = new html_table();
$layout_table->head = ['Layout', 'Descripción', 'Probar'];
foreach ($layouts as $name => $desc) {
    $test_url = new moodle_url('/local/sandbox/ej_page.php', ['layout' => $name]);
    $link = html_writer::link($test_url, "Probar '{$name}'");
    $current = ($PAGE->pagelayout === $name) ? ' ← ACTUAL' : '';
    $layout_table->data[] = [$name . $current, $desc, $link];
}
echo html_writer::table($layout_table);

// Aplicar layout desde parámetro GET
$requested_layout = optional_param('layout', '', PARAM_ALPHA);
if ($requested_layout) {
    echo $OUTPUT->notification("Has solicitado el layout '{$requested_layout}'. Recarga la página para verlo.", 'info');
}

echo $OUTPUT->footer();