<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/local/sandbox/ej_events.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ejercicio 8: Eventos');
$PAGE->set_heading('Ejercicio 8: Events API');

echo $OUTPUT->header();

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'fire') {
    require_sesskey(); // Protección CSRF

    // Disparar nuestro evento personalizado
    $event = \sandbox\classes\event\note_created::create([
        'context' => context_system::instance(),
        'objectid' => 42,  // ID ficticio de la nota
        'other' => [
            'title' => 'Nota de prueba desde el ejercicio',
        ],
    ]);
    $event->trigger();

    echo $OUTPUT->notification('✅ Evento note_created disparado. Revisa el log de PHP/Moodle.', 'success');
    echo $OUTPUT->notification('Busca en: Administración → Informes → Registros (Logs)', 'info');
}

echo $OUTPUT->heading('Disparar un Evento', 3);
echo $OUTPUT->box('Al pulsar el botón, se dispara el evento note_created y el observer lo captura.');

echo $OUTPUT->single_button(
    new moodle_url('/local/sandbox/ej_events.php', ['action' => 'fire', 'sesskey' => sesskey()]),
    '🔥 Disparar evento note_created',
    'get'
);

// Mostrar últimos logs del sistema
echo $OUTPUT->heading('Últimos logs del sistema', 3);
$sql = "SELECT l.id, l.eventname, l.userid, l.timecreated, l.action, l.target
        FROM {logstore_standard_log} l
        ORDER BY l.timecreated DESC";
$logs = $DB->get_records_sql($sql, [], 0, 15);

if ($logs) {
    $table = new html_table();
    $table->head = ['ID', 'Evento', 'User ID', 'Action', 'Target', 'Fecha'];
    $table->attributes['class'] = 'generaltable table-sm';
    foreach ($logs as $log) {
        $table->data[] = [
            $log->id,
            s(str_replace('\\', ' \\ ', $log->eventname)),
            $log->userid,
            $log->action,
            $log->target,
            userdate($log->timecreated, '%H:%M:%S %d/%m'),
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No hay logs disponibles.', 'info');
}

echo $OUTPUT->footer();