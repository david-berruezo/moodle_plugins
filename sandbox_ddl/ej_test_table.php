<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 6.1: Verificar tabla creada ===\n\n";

// Verificar que la tabla existe
$dbman = $DB->get_manager();
$table_exists = $dbman->table_exists('sandbox_ddl_notes');
echo "¿Tabla sandbox_ddl_notes existe? " . ($table_exists ? 'SÍ ✅' : 'NO ❌') . "\n\n";

if ($table_exists) {
    // Insertar datos de prueba
    $note = new stdClass();
    $note->userid = $USER->id;
    $note->title = 'Mi primera nota DDL';
    $note->content = 'Contenido de prueba para el ejercicio DDL';
    $note->priority = 2; // Alta
    $note->status = 0;   // Pendiente
    $note->timecreated = time();
    $note->timemodified = time();

    $note->id = $DB->insert_record('sandbox_ddl_notes', $note);
    echo "Nota insertada con ID: {$note->id}\n";

    // Leer
    $check = $DB->get_record('sandbox_ddl_notes', ['id' => $note->id]);
    echo "Título: {$check->title}\n";
    echo "Prioridad: {$check->priority}\n";
    echo "Creada: " . userdate($check->timecreated) . "\n";

    // Limpiar
    $DB->delete_records('sandbox_ddl_notes', ['id' => $note->id]);
    echo "\nNota de prueba eliminada.\n";
}