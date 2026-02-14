<?php

// local/sandbox/ej_dml_insert.php
// Ejercicio 5.3: DML — Insertar registros
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.3: DML — Insertar Registros ===\n\n";

// Usaremos la tabla mdl_tag como ejemplo (es segura para pruebas)
// También crearemos registros en mdl_user_preferences

// --- a) insert_record: insertar un registro ---
echo "--- insert_record ---\n";

// Insertar una preferencia de usuario (seguro para testing)
$pref = new stdClass();
$pref->userid = $USER->id;
$pref->name = 'sandbox_test_pref';
$pref->value = 'Hola desde el sandbox - ' . date('Y-m-d H:i:s');

// Primero borramos si existe de pruebas anteriores
$DB->delete_records('user_preferences', ['userid' => $USER->id, 'name' => 'sandbox_test_pref']);

$newid = $DB->insert_record('user_preferences', $pref);
echo "Insertado con ID: {$newid}\n";

// Verificar
$check = $DB->get_record('user_preferences', ['id' => $newid]);
echo "Verificación: userid={$check->userid}, name={$check->name}, value={$check->value}\n\n";

// --- b) insert_record sin devolver ID ---
$pref2 = new stdClass();
$pref2->userid = $USER->id;
$pref2->name = 'sandbox_test_pref_2';
$pref2->value = 'Sin retorno de ID';

$DB->delete_records('user_preferences', ['userid' => $USER->id, 'name' => 'sandbox_test_pref_2']);
$result = $DB->insert_record('user_preferences', $pref2, false); // false = no devolver ID
echo "insert_record sin return_id: " . ($result ? 'true' : 'false') . "\n\n";

// --- c) insert_records: insertar MÚLTIPLES registros (batch) ---
echo "--- insert_records (batch) ---\n";

// Limpiar pruebas anteriores
$DB->delete_records_select('user_preferences',
    "userid = :uid AND name LIKE :pattern",
    ['uid' => $USER->id, 'pattern' => 'sandbox_batch_%']
);

$batch = [];
for ($i = 1; $i <= 5; $i++) {
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->name = "sandbox_batch_{$i}";
    $record->value = "Valor batch #{$i}";
    $batch[] = $record;
}

$DB->insert_records('user_preferences', $batch);
echo "Insertados " . count($batch) . " registros en batch\n";

// Verificar
$inserted = $DB->get_records_select(
    'user_preferences',
    "userid = :uid AND name LIKE :pattern",
    ['uid' => $USER->id, 'pattern' => 'sandbox_batch_%']
);
echo "Verificación - encontrados: " . count($inserted) . "\n";
foreach ($inserted as $rec) {
    echo "  [{$rec->id}] {$rec->name} = {$rec->value}\n";
}

// --- Limpieza ---
echo "\n--- Limpieza ---\n";
$DB->delete_records_select('user_preferences',
    "userid = :uid AND name LIKE :pattern",
    ['uid' => $USER->id, 'pattern' => 'sandbox_%']
);
echo "Registros de prueba eliminados.\n";