<?php
// local/sandbox/ej_output.php
// Ejercicio 4.5: Explorar $OUTPUT y html_writer
// EJECUTAR DESDE NAVEGADOR
require_once(__DIR__ . '/../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/local/sandbox/ej_output.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ejercicio 4.5: $OUTPUT');
$PAGE->set_heading('Explorar $OUTPUT y html_writer');

echo $OUTPUT->header();

// --- 1. Headings ---
echo $OUTPUT->heading('1. Headings con $OUTPUT', 2);
echo $OUTPUT->heading('Heading nivel 3', 3);
echo $OUTPUT->heading('Heading nivel 4', 4);

// --- 2. Boxes ---
echo $OUTPUT->heading('2. Boxes', 2);
echo $OUTPUT->box('Esto es un box simple con $OUTPUT->box()');
echo $OUTPUT->box('Box con clase CSS personalizada', 'generalbox p-3 bg-light');
echo $OUTPUT->box_start('generalbox border border-primary p-3');
echo '<p>Contenido dentro de box_start/box_end</p>';
echo '<p>Permite contenido HTML complejo</p>';
echo $OUTPUT->box_end();

// --- 3. Notificaciones ---
echo $OUTPUT->heading('3. Notificaciones', 2);
echo $OUTPUT->notification('Esto es un INFO', \core\output\notification::NOTIFY_INFO);
echo $OUTPUT->notification('Esto es un SUCCESS', \core\output\notification::NOTIFY_SUCCESS);
echo $OUTPUT->notification('Esto es un WARNING', \core\output\notification::NOTIFY_WARNING);
echo $OUTPUT->notification('Esto es un ERROR', \core\output\notification::NOTIFY_ERROR);

// --- 4. Botones ---
echo $OUTPUT->heading('4. Botones', 2);
echo $OUTPUT->single_button(
    new moodle_url('/local/sandbox/ej_output.php'),
    'Botón POST',
    'post'
);
echo $OUTPUT->single_button(
    new moodle_url('/local/sandbox/ej_output.php', ['action' => 'test']),
    'Botón GET con parámetro',
    'get'
);

// --- 5. html_writer estático ---
echo $OUTPUT->heading('5. html_writer — Generador HTML estático', 2);

// Links
echo html_writer::link(
    new moodle_url('/admin/index.php'),
    'Ir al panel de administración',
    ['class' => 'btn btn-primary', 'target' => '_blank']
);
echo '<br><br>';

// Div con contenido
echo html_writer::div('Contenido en un div', 'alert alert-info');

// Span
echo html_writer::tag('p',
    'Texto normal y ' .
    html_writer::span('texto destacado', 'text-danger font-weight-bold') .
    ' en la misma línea.'
);

// Select/dropdown
$options = ['es' => 'Español', 'en' => 'English', 'pt' => 'Português'];
echo html_writer::label('Idioma: ', 'menu_lang');
echo html_writer::select($options, 'lang', 'es', null, ['id' => 'menu_lang']);
echo '<br><br>';

// --- 6. Tablas ---
echo $OUTPUT->heading('6. Tablas con html_table', 2);

$table = new html_table();
$table->attributes['class'] = 'generaltable table-striped';
$table->head = ['#', 'Método $OUTPUT', 'Descripción'];
$table->data = [
    ['1', 'header() / footer()', 'Inicio y fin de página HTML'],
    ['2', 'heading($text, $level)', 'Encabezados H1-H6'],
    ['3', 'box($content)', 'Contenedor div con estilos'],
    ['4', 'notification($msg, $type)', 'Mensajes de alerta'],
    ['5', 'single_button($url, $label)', 'Botón simple'],
    ['6', 'pix_icon($name, $alt)', 'Iconos de Moodle'],
    ['7', 'render_from_template()', 'Renderizar Mustache'],
    ['8', 'confirm($msg, $yes, $no)', 'Diálogo de confirmación'],
];
echo html_writer::table($table);

// --- 7. Iconos ---
echo $OUTPUT->heading('7. Iconos de Moodle (pix_icon)', 2);
$icons = ['i/edit', 'i/delete', 'i/settings', 'i/user', 'i/grades', 'i/info', 't/check', 't/email'];
foreach ($icons as $icon) {
    echo $OUTPUT->pix_icon($icon, $icon, 'moodle', ['class' => 'icon mr-2']);
    echo " <code>{$icon}</code> &nbsp;&nbsp;";
}

// --- 8. Diálogo de confirmación ---
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'confirmar') {
    echo $OUTPUT->confirm(
        '¿Estás seguro de que quieres continuar con esta acción?',
        new moodle_url('/local/sandbox/ej_output.php', ['action' => 'confirmado']),
        new moodle_url('/local/sandbox/ej_output.php')
    );
} else if ($action === 'confirmado') {
    echo $OUTPUT->notification('¡Acción confirmada con éxito!', 'success');
} else {
    echo '<br>';
    echo $OUTPUT->heading('8. Diálogo de Confirmación', 2);
    echo $OUTPUT->single_button(
        new moodle_url('/local/sandbox/ej_output.php', ['action' => 'confirmar']),
        'Probar diálogo de confirmación',
        'get'
    );
}

echo $OUTPUT->footer();