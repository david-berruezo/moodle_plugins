<?php
// local/sandbox/ej_cfg.php
// Ejercicio 4.1: Explorar $CFG en profundidad
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 4.1: Variable \$CFG ===\n\n";

// $CFG contiene TODA la configuración del sitio.
// Viene de: config.php + tabla mdl_config

// --- Valores de config.php ---
echo "--- Desde config.php ---\n";
echo "URL base:  {$CFG->wwwroot}\n";
echo "Ruta:      {$CFG->dirroot}\n";
echo "Datos:     {$CFG->dataroot}\n\n";

// --- Valores desde la tabla mdl_config ---
echo "--- Desde mdl_config (BD) ---\n";
echo "Nombre del sitio:  " . $CFG->{'siteguest'} . " (ID usuario invitado)\n";

// Leer configuración dinámica
$sitename = $DB->get_field('course', 'fullname', ['id' => SITEID]);
echo "Nombre sitio:      {$sitename}\n";

// Ver todas las configs de la BD
$allconfigs = $DB->get_records('config', null, 'name ASC', 'id, name, value', 0, 10);
echo "\n--- Primeros 10 registros de mdl_config ---\n";
foreach ($allconfigs as $cfg) {
    $val = strlen($cfg->value) > 50 ? substr($cfg->value, 0, 50) . '...' : $cfg->value;
    echo "  {$cfg->name} = {$val}\n";
}

// --- Uso práctico: construir URLs ---
echo "\n--- Construir URLs con \$CFG ---\n";
echo "Login:    {$CFG->wwwroot}/login/index.php\n";
echo "Admin:    {$CFG->wwwroot}/admin/index.php\n";
echo "Perfil:   {$CFG->wwwroot}/user/profile.php\n";

// --- moodle_url (la forma correcta) ---
$url = new moodle_url('/user/profile.php', ['id' => 2]);
echo "moodle_url: " . $url->out() . "\n";

$url2 = new moodle_url('/course/view.php', ['id' => 1, 'section' => 3]);
echo "moodle_url: " . $url2->out() . "\n";