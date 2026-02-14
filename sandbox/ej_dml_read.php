<?php
// local/sandbox/ej_dml_read.php
// Ejercicio 5.1-5.2: API DML - Lectura y JOINs
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 5.1-5.2: DML — Lectura y JOINs ===\n\n";

// =============================================
// 5.1 Obtener registros
// =============================================
echo "--- 5.1 Obtener Registros ---\n\n";

// a) get_record: UN registro
$admin = $DB->get_record('user', ['username' => 'admin'], '*', MUST_EXIST);
echo "get_record: Admin = {$admin->firstname} {$admin->lastname}\n";

// b) get_record con campos específicos
$admin_mini = $DB->get_record('user', ['username' => 'admin'], 'id, email');
echo "get_record (campos): ID={$admin_mini->id}, Email={$admin_mini->email}\n\n";

// c) get_records: VARIOS registros
$users = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0], 'lastname ASC', 'id, username, firstname, lastname, email');
echo "get_records: " . count($users) . " usuarios activos\n";
foreach (array_slice($users, 0, 5, true) as $u) {
    echo "  [{$u->id}] {$u->username} — {$u->firstname} {$u->lastname}\n";
}

// d) get_records_select: con WHERE complejo
echo "\nget_records_select (WHERE con LIKE):\n";
$users_a = $DB->get_records_select(
    'user',
    "deleted = :del AND username LIKE :pattern",
    ['del' => 0, 'pattern' => 'a%'],
    'username ASC',
    'id, username'
);
foreach ($users_a as $u) {
    echo "  [{$u->id}] {$u->username}\n";
}

// e) get_records_sql: SQL completo
echo "\nget_records_sql (SQL directo):\n";
$sql = "SELECT id, username, email 
        FROM {user} 
        WHERE deleted = :del 
        AND lastaccess > :since 
        ORDER BY lastaccess DESC";
$params = [
    'del' => 0,
    'since' => time() - (30 * 24 * 60 * 60), // Último mes
];
$recent_users = $DB->get_records_sql($sql, $params, 0, 5);
foreach ($recent_users as $u) {
    echo "  [{$u->id}] {$u->username} ({$u->email})\n";
}

// f) get_field: un solo campo
$admin_email = $DB->get_field('user', 'email', ['username' => 'admin']);
echo "\nget_field: Email admin = {$admin_email}\n";

// g) get_records_menu: array id => valor
$user_menu = $DB->get_records_menu('user', ['deleted' => 0], 'lastname', 'id, ' . $DB->sql_concat('firstname', "' '", 'lastname'));
echo "\nget_records_menu (primeros 5):\n";
foreach (array_slice($user_menu, 0, 5, true) as $id => $name) {
    echo "  ID {$id} => {$name}\n";
}

// h) count_records
$total = $DB->count_records('user', ['deleted' => 0]);
echo "\ncount_records: {$total} usuarios activos\n";

// i) record_exists
$exists = $DB->record_exists('user', ['username' => 'admin']);
echo "record_exists('admin'): " . ($exists ? 'SÍ' : 'NO') . "\n";

// =============================================
// 5.2 Consultas con JOINs
// =============================================
echo "\n\n--- 5.2 Consultas con JOINs ---\n\n";

// JOIN: Usuarios matriculados en cursos
$sql = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, c.fullname AS coursename
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        WHERE u.deleted = 0
        ORDER BY c.fullname, u.lastname
        ";
$enrolled = $DB->get_records_sql($sql, [], 0, 10);

if ($enrolled) {
    echo "Usuarios matriculados (máx 10):\n";
    foreach ($enrolled as $row) {
        echo "  {$row->firstname} {$row->lastname} => {$row->coursename}\n";
    }
} else {
    echo "No hay matriculaciones. Crea un curso y matricula un usuario para probar.\n";
}

// JOIN: Cursos con número de estudiantes
$sql = "SELECT c.id, c.fullname, COUNT(ue.id) AS num_students
        FROM {course} c
        LEFT JOIN {enrol} e ON e.courseid = c.id
        LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE c.id != :siteid
        GROUP BY c.id, c.fullname
        ORDER BY num_students DESC";
$courses = $DB->get_records_sql($sql, ['siteid' => SITEID], 0, 10);

echo "\nCursos con nº de estudiantes:\n";
foreach ($courses as $c) {
    echo "  [{$c->id}] {$c->fullname} — {$c->num_students} estudiantes\n";
}

// JOIN: Actividades recientes con módulo y curso
$sql = "SELECT cm.id AS cmid, m.name AS modtype, c.shortname, cm.added
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {course} c ON c.id = cm.course
        ORDER BY cm.added DESC";
$modules = $DB->get_records_sql($sql, [], 0, 10);

echo "\nÚltimas 10 actividades añadidas:\n";
foreach ($modules as $mod) {
    echo "  [{$mod->cmid}] {$mod->modtype} en '{$mod->shortname}' — " . userdate($mod->added) . "\n";
}

// Subquery: usuarios que NO están matriculados en ningún curso
$sql = "SELECT u.id, u.username, u.firstname, u.lastname
        FROM {user} u
        WHERE u.deleted = 0
          AND u.id != :guest
          AND u.id NOT IN (
              SELECT DISTINCT ue.userid
              FROM {user_enrolments} ue
          )
        ORDER BY u.lastname";
$not_enrolled = $DB->get_records_sql($sql, ['guest' => $CFG->siteguest], 0, 10);

echo "\nUsuarios sin matriculación (máx 10):\n";
if ($not_enrolled) {
    foreach ($not_enrolled as $u) {
        echo "  [{$u->id}] {$u->username} — {$u->firstname} {$u->lastname}\n";
    }
} else {
    echo "  Todos los usuarios están matriculados en al menos un curso.\n";
}