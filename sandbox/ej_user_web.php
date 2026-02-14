<?php
// local/sandbox/ej_user_web.php
// Ejercicio 4.3: Explorar $USER
// EJECUTAR DESDE NAVEGADOR: http://localhost/moodle/local/sandbox/ej_user_web.php
require_once(__DIR__ . '/../../config.php');
require_login(); // Obliga a estar logueado

$PAGE->set_url(new moodle_url('/local/sandbox/ej_user_web.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ejercicio 4.3: $USER');
$PAGE->set_heading('Ejercicio 4.3: Explorar $USER');

echo $OUTPUT->header();

echo $OUTPUT->heading('Datos del Usuario Actual', 3);

// Datos básicos
$data = [
    'ID' => $USER->id,
    'Username' => $USER->username,
    'Nombre' => $USER->firstname,
    'Apellido' => $USER->lastname,
    'Completo' => fullname($USER),
    'Email' => $USER->email,
    'Idioma' => $USER->lang,
    'Timezone' => $USER->timezone,
    'Última IP' => $USER->lastip,
    'Último login' => $USER->lastlogin ? userdate($USER->lastlogin) : 'Nunca',
];

$table = new html_table();
$table->head = ['Propiedad', 'Valor'];
$table->attributes['class'] = 'generaltable';
foreach ($data as $key => $value) {
    $table->data[] = [$key, $value];
}
echo html_writer::table($table);

// ¿Es admin?
$is_admin = is_siteadmin($USER);
echo $OUTPUT->notification(
    $is_admin ? '✅ Eres administrador del sitio' : '❌ No eres administrador',
    $is_admin ? 'success' : 'info'
);

// Roles del usuario en el contexto del sistema
$context = context_system::instance();
$roles = get_user_roles($context, $USER->id);
if ($roles) {
    echo $OUTPUT->heading('Roles en el Sistema', 4);
    $role_table = new html_table();
    $role_table->head = ['Rol ID', 'Nombre corto', 'Nombre'];
    foreach ($roles as $role) {
        $role_table->data[] = [$role->roleid, $role->shortname, $role->name ?: role_get_name($role)];
    }
    echo html_writer::table($role_table);
}

// Cursos en los que está matriculado
$courses = enrol_get_my_courses('id, fullname, shortname', 'fullname ASC');
echo $OUTPUT->heading('Mis Cursos (' . count($courses) . ')', 4);
if ($courses) {
    $course_table = new html_table();
    $course_table->head = ['ID', 'Nombre corto', 'Nombre completo'];
    foreach ($courses as $course) {
        $course_table->data[] = [$course->id, $course->shortname, $course->fullname];
    }
    echo html_writer::table($course_table);
} else {
    echo html_writer::tag('p', 'No estás matriculado en ningún curso.');
}

echo $OUTPUT->footer();