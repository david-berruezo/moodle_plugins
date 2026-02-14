<?php

// local/sandbox/ej_dml_transaction.php
// Ejercicio 5.8: DML — Transacciones
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.8: DML — Transacciones ===\n\n";

// Las transacciones aseguran que TODO se ejecuta o NADA.
// Moodle usa un sistema de transacciones anidadas.

// --- Ejemplo 1: Transacción exitosa ---
echo "--- Transacción EXITOSA ---\n";

$transaction = $DB->start_delegated_transaction();
try {
    // Insertar varios registros que deben ser atómicos
    $pref1 = new stdClass();
    $pref1->userid = $USER->id;
    $pref1->name = 'sandbox_tx_1';
    $pref1->value = 'transaccion_ok_1';
    $id1 = $DB->insert_record('user_preferences', $pref1);
    echo "Insertado 1: ID={$id1}\n";

    $pref2 = new stdClass();
    $pref2->userid = $USER->id;
    $pref2->name = 'sandbox_tx_2';
    $pref2->value = 'transaccion_ok_2';
    $id2 = $DB->insert_record('user_preferences', $pref2);
    echo "Insertado 2: ID={$id2}\n";

    // Todo OK → confirmar transacción
    $transaction->allow_commit();
    echo "✅ Transacción confirmada (committed)\n\n";

} catch (Exception $e) {
    $transaction->rollback($e);
    echo "❌ Transacción revertida: " . $e->getMessage() . "\n\n";
}

// Verificar
$count = $DB->count_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_tx_%']
);
echo "Registros después del commit: {$count}\n\n";

// --- Ejemplo 2: Transacción con ROLLBACK ---
echo "--- Transacción con ROLLBACK ---\n";

// Primero limpiamos
$DB->delete_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_tx_%']
);

$transaction = $DB->start_delegated_transaction();
try {
    $pref1 = new stdClass();
    $pref1->userid = $USER->id;
    $pref1->name = 'sandbox_tx_rollback_1';
    $pref1->value = 'esto no debería guardarse';
    $DB->insert_record('user_preferences', $pref1);
    echo "Insertado registro 1 (dentro de TX)\n";

    // Simular un error
    throw new Exception('Error simulado: algo falló');

    // Esto nunca se ejecutará
    $pref2 = new stdClass();
    $pref2->userid = $USER->id;
    $pref2->name = 'sandbox_tx_rollback_2';
    $pref2->value = 'esto tampoco debería guardarse';
    $DB->insert_record('user_preferences', $pref2);

    $transaction->allow_commit();
} catch (Exception $e) {
    // Nota: después del rollback, $DB queda en estado rollback
    // No se pueden hacer más operaciones en esta ejecución
    echo "❌ Rollback ejecutado: " . $e->getMessage() . "\n";

    // En un entorno real, el rollback se maneja así:
    try {
        $transaction->rollback($e);
    } catch (Exception $ex) {
        // Silenciar: el rollback ya se hizo internamente
    }
}

echo "\n⚠️  NOTA IMPORTANTE sobre transacciones en Moodle:\n";
echo "- Moodle usa transacciones 'delegadas' (anidadas)\n";
echo "- Solo la transacción más externa hace el commit real\n";
echo "- Un rollback en cualquier nivel revierte TODO\n";
echo "- Después de un rollback, no se pueden hacer más queries\n";
echo "\nPatrón recomendado:\n";
echo '  $transaction = $DB->start_delegated_transaction();' . "\n";
echo '  try {' . "\n";
echo '      // ... tus queries ...' . "\n";
echo '      $transaction->allow_commit();' . "\n";
echo '  } catch (Exception $e) {' . "\n";
echo '      $transaction->rollback($e);' . "\n";
echo '  }' . "\n";