<?php

// local/sandbox/ej_dml_update.php
// Ejercicio 5.4: DML — Actualizar registros
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.4: DML — Actualizar Registros ===\n\n";

// Preparar datos de prueba
$DB->delete_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_%']
);

$pref = new stdClass();
$pref->userid = $USER->id;
$pref->name = 'sandbox_update_test';
$pref->value = 'valor_original';
$pref->id = $DB->insert_record('user_preferences', $pref);

echo "Registro creado: ID={$pref->id}, valor='{$pref->value}'\n\n";

// --- a) update_record: actualizar por ID ---
echo "--- update_record ---\n";
$pref->value = 'valor_actualizado_' . time();
$DB->update_record('user_preferences', $pref);

$check = $DB->get_record('user_preferences', ['id' => $pref->id]);
echo "Después de update_record: valor='{$check->value}'\n\n";

// --- b) set_field: actualizar UN campo ---
echo "--- set_field ---\n";
$DB->set_field('user_preferences', 'value', 'cambiado_con_set_field', ['id' => $pref->id]);

$check = $DB->get_record('user_preferences', ['id' => $pref->id]);
echo "Después de set_field: valor='{$check->value}'\n\n";

// --- c) set_field_select: actualizar con WHERE complejo ---
echo "--- set_field_select ---\n";
$DB->set_field_select(
    'user_preferences',
    'value',
    'cambiado_con_set_field_select',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_%']
);

$check = $DB->get_record('user_preferences', ['id' => $pref->id]);
echo "Después de set_field_select: valor='{$check->value}'\n\n";

// --- d) Ejemplo práctico: actualizar último acceso ---
echo "--- Ejemplo práctico ---\n";
$me = $DB->get_record('user', ['id' => $USER->id], 'id, username, lastaccess');
echo "Mi último acceso: " . userdate($me->lastaccess) . "\n";
// (No lo vamos a cambiar realmente, solo lo mostramos como ejemplo)

// Limpieza
$DB->delete_records_select('user_preferences',
    "userid = :uid AND name LIKE :p",
    ['uid' => $USER->id, 'p' => 'sandbox_%']
);
echo "\nLimpieza completada.\n";