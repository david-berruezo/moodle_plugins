<?php
// local/sandbox/ej_db.php
// Ejercicio 4.2: Explorar $DB
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 4.2: Variable \$DB ===\n\n";

// $DB es una instancia de moodle_database
// Es el ÚNICO modo permitido de interactuar con la BD en Moodle

echo "Clase de \$DB: " . get_class($DB) . "\n";
echo "Tipo de BD:   " . $DB->get_dbfamily() . "\n";
echo "Nombre BD:    " . $DB->get_name() . "\n\n";

// --- Contar tablas ---
$tables = $DB->get_tables();
echo "Total de tablas en Moodle: " . count($tables) . "\n";
echo "Primeras 10 tablas:\n";
$slice = array_slice($tables, 0, 10);
foreach ($slice as $table) {
    echo "  - {$CFG->prefix}{$table}\n";
}

// --- Operaciones básicas de lectura ---
echo "\n--- Lectura Básica ---\n";

// get_record: UN solo registro
$admin = $DB->get_record('user', ['username' => 'admin']);
echo "Admin: {$admin->firstname} {$admin->lastname} ({$admin->email})\n";

// get_records: VARIOS registros
$users = $DB->get_records('user', ['deleted' => 0], 'lastname ASC', 'id, username, firstname, lastname', 0, 5);
echo "\nPrimeros 5 usuarios activos:\n";
foreach ($users as $user) {
    echo "  [{$user->id}] {$user->username} - {$user->firstname} {$user->lastname}\n";
}

// count_records: contar
$total_users = $DB->count_records('user', ['deleted' => 0]);
$total_courses = $DB->count_records('course');
echo "\nTotal usuarios activos: {$total_users}\n";
echo "Total cursos:          {$total_courses}\n";

// get_field: un solo campo
$admin_email = $DB->get_field('user', 'email', ['username' => 'admin']);
echo "\nEmail del admin: {$admin_email}\n";

// record_exists: comprobar existencia
$exists = $DB->record_exists('user', ['username' => 'admin']);
echo "¿Existe usuario 'admin'? " . ($exists ? 'SÍ' : 'NO') . "\n";


// raw sql
// Obtener múltiples registros
$records = $DB->get_records_sql('SELECT * FROM {user} WHERE confirmed = ?', [1]);
// print_r($records);

// Obtener un solo registro
$record = $DB->get_record_sql('SELECT * FROM {course} WHERE id = ?', [5]);
// print_r($record);

// Obtener un solo campo/valor
$name = $DB->get_field_sql('SELECT firstname FROM {user} WHERE id = ?', [2]);
// print_r($name);

// Usar recordset para grandes volúmenes (más eficiente en memoria)
$rs = $DB->get_recordset_sql('SELECT * FROM {user} WHERE deleted = ?', [0]);
foreach ($rs as $record) {
    // procesar
    // print_r($record);
}
$rs->close(); // ¡Importante cerrar!

// Ejecutar SQL arbitrario (UPDATE, DELETE, etc.)
$DB->execute('UPDATE {user} SET confirmed = ? WHERE id = ?', [1, 0]);

// DELETE
// $DB->execute('DELETE FROM {user_preferences} WHERE userid = ?', [5]);

// placeholders nombre
$sql = 'SELECT * FROM {user} WHERE firstname = :name AND city = :city';
$params = ['name' => 'Administrador', 'city' => 'Barcelona'];
$records = $DB->get_records_sql($sql, $params);
// print_r($records);

// joins
$sql = "SELECT u.id, u.firstname, u.lastname, c.fullname
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        WHERE c.id = :courseid";

$students = $DB->get_records_sql($sql, ['courseid' => 1]);
// print_r($students);