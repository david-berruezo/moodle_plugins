<?php

// local/sandbox/ej_dml_recordset.php
// Ejercicio 5.7: DML — Recordsets para grandes volúmenes
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.7: DML — Recordsets ===\n\n";

// Los recordsets NO cargan todos los registros en memoria.
// Procesan uno a uno, ideal para miles/millones de registros.

// --- Comparación: get_records vs get_recordset ---

// MAL: Esto carga TODO en memoria
// $all_users = $DB->get_records('user'); // Si hay 50.000 usuarios → PHP out of memory

// BIEN: Recordset procesa uno a uno
echo "--- get_recordset_select ---\n";
$rs = $DB->get_recordset_select('user', 'deleted = :del', ['del' => 0], 'id ASC', 'id, username, email');

$count = 0;
$first_five = [];
foreach ($rs as $record) {
    $count++;
    if ($count <= 5) {
        $first_five[] = $record;
    }
    // Aquí harías tu procesamiento pesado
    // por ejemplo: enviar email, generar PDF, actualizar otro sistema, etc.
}
$rs->close(); // ¡SIEMPRE cerrar el recordset!

echo "Procesados: {$count} usuarios\n";
echo "Primeros 5:\n";
foreach ($first_five as $u) {
    echo "  [{$u->id}] {$u->username} ({$u->email})\n";
}

// --- get_recordset_sql con query compleja ---
echo "\n--- get_recordset_sql ---\n";
$sql = "SELECT u.id, u.username, u.firstname, u.lastname, 
               COUNT(ue.id) AS enrolments
        FROM {user} u
        LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
        WHERE u.deleted = 0
        GROUP BY u.id, u.username, u.firstname, u.lastname
        ORDER BY enrolments DESC";

$rs = $DB->get_recordset_sql($sql);
echo "Usuarios con sus matriculaciones:\n";
$i = 0;
foreach ($rs as $record) {
    if ($i >= 5) break;
    echo "  {$record->username}: {$record->enrolments} matriculaciones\n";
    $i++;
}
$rs->close(); // ¡SIEMPRE cerrar!

echo "\n⚠️  IMPORTANTE: SIEMPRE llamar a \$rs->close() cuando termines.\n";
echo "Si no lo haces, puedes dejar conexiones de BD abiertas.\n";
echo "\nPatrón recomendado:\n";
echo '  $rs = $DB->get_recordset(...);' . "\n";
echo '  foreach ($rs as $record) { ... }' . "\n";
echo '  $rs->close();' . "\n";