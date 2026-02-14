<?php
// local/sandbox/ej_dml_advanced.php
// Ejercicio 5.6: DML — Condiciones Avanzadas
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.6: DML — Condiciones Avanzadas ===\n\n";

// --- a) get_in_or_equal: IN (lista de valores) ---
echo "--- get_in_or_equal ---\n";
$ids = [1, 2, 3, 4, 5];
list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'id');
$users = $DB->get_records_select('user', "id {$insql}", $params, '', 'id, username');
echo "Usuarios con ID in (1,2,3,4,5):\n";
foreach ($users as $u) {
    echo "  [{$u->id}] {$u->username}\n";
}

// Con NOT IN
list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'id', false); // false = NOT IN
$count = $DB->count_records_select('user', "id {$insql} AND deleted = 0", $params);
echo "\nUsuarios con ID NOT IN (1,2,3,4,5): {$count}\n\n";

// --- b) sql_like: búsqueda LIKE segura ---
echo "--- sql_like ---\n";
$like = $DB->sql_like('username', ':pattern', false); // false = case insensitive
$users = $DB->get_records_select('user', $like, ['pattern' => '%a%'], 'username', 'id, username', 0, 5);
echo "Usuarios con 'a' en el username:\n";
foreach ($users as $u) {
    echo "  [{$u->id}] {$u->username}\n";
}

// NOT LIKE
$notlike = $DB->sql_like('username', ':pattern', false, true, true); // último true = NOT LIKE
$count = $DB->count_records_select('user', $notlike . " AND deleted = 0", ['pattern' => '%admin%']);
echo "\nUsuarios sin 'admin' en username: {$count}\n\n";

// --- c) sql_concat: concatenar campos ---
echo "--- sql_concat ---\n";
$fullname = $DB->sql_concat('firstname', "' '", 'lastname');
$sql = "SELECT id, {$fullname} AS fullname FROM {user} WHERE deleted = 0 ORDER BY lastname LIMIT 5";
$users = $DB->get_records_sql($sql);
echo "Nombres completos (sql_concat):\n";
foreach ($users as $u) {
    echo "  [{$u->id}] {$u->fullname}\n";
}

// --- d) sql_order_by_text: ORDER BY en campos TEXT ---
echo "\n--- sql_order_by_text ---\n";
echo "Útil para ordenar por campos TEXT/CLOB en Oracle/MSSQL\n";
echo "Ejemplo: ORDER BY " . $DB->sql_order_by_text('description') . "\n\n";

// --- e) sql_cast_char2int: cast ---
echo "--- sql_cast_char2int ---\n";
$cast = $DB->sql_cast_char2int('value');
echo "Cast char a int: {$cast}\n\n";

// --- f) Ejemplo combinado ---
echo "--- Ejemplo Combinado ---\n";
$search = 'admin';
$like = $DB->sql_like('u.username', ':search', false);
$fullname = $DB->sql_concat('u.firstname', "' '", 'u.lastname');

$sql = "SELECT u.id, u.username, {$fullname} AS fullname, u.email, u.lastaccess
        FROM {user} u
        WHERE u.deleted = :del
          AND ({$like} OR {$DB->sql_like('u.email', ':emailsearch', false)})
        ORDER BY u.lastaccess DESC";

$params = [
    'del' => 0,
    'search' => "%{$search}%",
    'emailsearch' => "%{$search}%",
];

$results = $DB->get_records_sql($sql, $params, 0, 5);
echo "Búsqueda combinada por '{$search}':\n";
foreach ($results as $r) {
    $last = $r->lastaccess ? userdate($r->lastaccess) : 'Nunca';
    echo "  [{$r->id}] {$r->username} | {$r->fullname} | {$r->email} | {$last}\n";
}