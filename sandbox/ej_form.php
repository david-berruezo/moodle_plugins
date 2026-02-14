<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/sandbox/classes/form/note_form.php');
require_once($CFG->dirroot . '/local/sandbox/classes/form/contact_form.php');
require_login();

$PAGE->set_url(new moodle_url('/local/sandbox/ej_form.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ejercicio 7: Formularios');
$PAGE->set_heading('Ejercicio 7: Moodle Forms API');
$PAGE->navbar->add('Sandbox', new moodle_url('/local/sandbox/'));
$PAGE->navbar->add('Formulario');

// Datos personalizados para pasar al formulario de notas
$customdata = [
    'priorities' => [0 => 'Baja', 1 => 'Media', 2 => 'Alta', 3 => 'Urgente'],
];

// Datos personalizados para el formulario de contacto
$contactdata = [
    'categories' => [
        '' => 'Selecciona...',
        'general' => 'Consulta general',
        'technical' => 'Soporte técnico',
        'billing' => 'Facturación',
        'suggestion' => 'Sugerencia',
    ],
];

// Crear los formularios
$form = new \sandbox\classes\form\note_form(null, $customdata);
$contactform = new \sandbox\classes\form\contact_form(null, $contactdata);

// Procesar el formulario de notas
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/sandbox/ej_form.php'), 'Formulario cancelado.', null, \core\output\notification::NOTIFY_INFO);

} else if ($data = $form->get_data()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Datos Recibidos del Formulario de Notas', 3);

    $info = [
        'ID' => $data->id,
        'Título' => $data->title,
        'Contenido' => $data->content,
        'Prioridad' => $customdata['priorities'][$data->priority] ?? $data->priority,
        'Pública' => $data->is_public ? 'Sí' : 'No',
        'Estado' => $data->status,
        'Fecha límite' => userdate($data->duedate),
        'Recordatorio' => !empty($data->reminder) ? userdate($data->reminder) : 'No establecido',
    ];

    if (isset($data->description_editor)) {
        $info['Descripción HTML'] = $data->description_editor['text'];
    }

    $table = new html_table();
    $table->head = ['Campo', 'Valor'];
    $table->attributes['class'] = 'generaltable';
    foreach ($info as $key => $value) {
        $table->data[] = [$key, s($value)];
    }
    echo html_writer::table($table);

    echo $OUTPUT->heading('Dump completo de $data:', 4);
    echo html_writer::tag('pre', print_r($data, true));

    echo $OUTPUT->footer();

// Procesar el formulario de contacto
} else if ($contactform->is_cancelled()) {
    redirect(new moodle_url('/local/sandbox/ej_form.php'), 'Formulario de contacto cancelado.', null, \core\output\notification::NOTIFY_INFO);

} else if ($contactdata_submitted = $contactform->get_data()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Datos Recibidos del Formulario de Contacto', 3);

    $urgency_labels = [0 => 'Baja', 1 => 'Normal', 2 => 'Alta'];

    $info = [
        'Nombre' => $contactdata_submitted->fullname,
        'Email' => $contactdata_submitted->email,
        'Teléfono' => !empty($contactdata_submitted->phone) ? $contactdata_submitted->phone : 'No proporcionado',
        'Categoría' => $contactdata['categories'][$contactdata_submitted->category] ?? $contactdata_submitted->category,
        'Asunto' => $contactdata_submitted->subject,
        'Mensaje' => $contactdata_submitted->message,
        'Urgencia' => $urgency_labels[$contactdata_submitted->urgency] ?? $contactdata_submitted->urgency,
        'Enviar copia' => $contactdata_submitted->send_copy ? 'Sí' : 'No',
    ];

    $table = new html_table();
    $table->head = ['Campo', 'Valor'];
    $table->attributes['class'] = 'generaltable';
    foreach ($info as $key => $value) {
        $table->data[] = [$key, s($value)];
    }
    echo html_writer::table($table);

    echo $OUTPUT->heading('Dump completo de $data:', 4);
    echo html_writer::tag('pre', print_r($contactdata_submitted, true));

    echo $OUTPUT->footer();

} else {
    // Mostrar ambos formularios
    echo $OUTPUT->header();

    // Formulario de notas
    echo $OUTPUT->heading('Crear una Nota de Prueba', 3);
    echo $OUTPUT->box('Este formulario demuestra los principales tipos de campo de Moodle Forms API.');
    $form->display();

    echo html_writer::tag('hr', '');

    // Formulario de contacto
    echo $OUTPUT->heading('Formulario de Contacto', 3);
    echo $OUTPUT->box('Este formulario demuestra campos de contacto: email, teléfono, categorías y validaciones personalizadas.');
    $contactform->display();

    echo $OUTPUT->heading('7.3 Tipos PARAM para sanitización', 3);
    $params_table = new html_table();
    $params_table->head = ['Constante', 'Uso'];
    $params_table->data = [
        ['PARAM_INT', 'Números enteros'],
        ['PARAM_FLOAT', 'Números decimales'],
        ['PARAM_TEXT', 'Texto plano (sin HTML)'],
        ['PARAM_CLEANHTML', 'HTML limpio (sin scripts)'],
        ['PARAM_RAW', 'Sin limpiar (para editores HTML)'],
        ['PARAM_ALPHA', 'Solo letras a-z'],
        ['PARAM_ALPHANUMEXT', 'Letras, números, guiones'],
        ['PARAM_BOOL', 'Booleano (0/1)'],
        ['PARAM_URL', 'URL válida'],
        ['PARAM_EMAIL', 'Email válido'],
        ['PARAM_FILE', 'Nombre de archivo seguro'],
        ['PARAM_PATH', 'Ruta de directorio segura'],
    ];
    echo html_writer::table($params_table);

    echo $OUTPUT->footer();
}