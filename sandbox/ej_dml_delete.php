<?php
// local/sandbox/ej_dml_delete.php
// Ejercicio 5.5: DML — Eliminar registros
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.5: DML — Eliminar Registros ===\n\n";

// Crear registros de prueba
echo "--- Preparando datos de prueba ---\n";
$DB->delete_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_del_%']
);

for ($i = 1; $i <= 5; $i++) {
    $rec = new stdClass();
    $rec->userid = $USER->id;
    $rec->name = "sandbox_del_{$i}";
    $rec->value = "valor_{$i}";
    $rec->id = $DB->insert_record('user_preferences', $rec);
    echo "  Creado: [{$rec->id}] sandbox_del_{$i}\n";
}

$count = $DB->count_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_del_%']
);
echo "Total creados: {$count}\n\n";

// --- a) delete_records: por condiciones exactas ---
echo "--- delete_records (eliminar sandbox_del_1) ---\n";
$DB->delete_records('user_preferences', [
    'userid' => $USER->id,
    'name' => 'sandbox_del_1'
]);
$count = $DB->count_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_del_%']
);
echo "Quedan: {$count}\n\n";

// --- b) delete_records_select: con WHERE complejo ---
echo "--- delete_records_select (eliminar 2 y 3) ---\n";
$DB->delete_records_select(
    'user_preferences',
    "userid = :uid AND name IN (:n1, :n2)",
    ['uid' => $USER->id, 'n1' => 'sandbox_del_2', 'n2' => 'sandbox_del_3']
);
$count = $DB->count_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_del_%']
);
echo "Quedan: {$count}\n\n";

// --- c) delete_records_list: eliminar por lista de IDs ---
echo "--- delete_records_list (eliminar por IDs) ---\n";
$remaining = $DB->get_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_del_%'],
    '', 'id'
);
$ids = array_keys($remaining);
echo "IDs a eliminar: " . implode(', ', $ids) . "\n";

if ($ids) {
    // Usa get_in_or_equal para listas de IDs
    list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
    $DB->delete_records_select('user_preferences', "id {$insql}", $inparams);
}

$count = $DB->count_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_del_%']
);
echo "Quedan: {$count}\n";
echo "\n✅ Todas las operaciones de eliminación completadas.\n";