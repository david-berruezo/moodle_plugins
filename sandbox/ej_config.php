<?php

// local/sandbox/ej_config.php
// Ejercicio 3.1: Explorar config.php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== EJERCICIO 3.1: Explorar config.php ===\n\n";

// 1. Datos de conexión a BD (vienen de config.php)
echo "--- Conexión a Base de Datos ---\n";
echo "Tipo BD:   {$CFG->dbtype}\n";      // mysqli
echo "Host BD:   {$CFG->dbhost}\n";      // localhost
echo "Nombre BD: {$CFG->dbname}\n";      // moodle
echo "Usuario:   {$CFG->dbuser}\n";      // tu_usuario
echo "Prefijo:   {$CFG->prefix}\n\n";    // mdl_

// 2. Rutas del sistema
echo "--- Rutas del Sistema ---\n";
echo "wwwroot (URL base):     {$CFG->wwwroot}\n";
echo "dirroot (ruta física):  {$CFG->dirroot}\n";
echo "dataroot (datos):       {$CFG->dataroot}\n";
echo "tempdir:                {$CFG->tempdir}\n";
echo "cachedir:               {$CFG->cachedir}\n";
echo "localcachedir:          {$CFG->localcachedir}\n\n";

// 3. Configuraciones adicionales que puedes añadir a config.php
echo "--- Configuraciones Útiles para Desarrollo ---\n";
echo "Para activar modo debug, añade a config.php ANTES del require:\n";
echo '  @error_reporting(E_ALL | E_STRICT);' . "\n";
echo '  @ini_set("display_errors", "1");' . "\n";
echo '  $CFG->debug = (E_ALL | E_STRICT);' . "\n";
echo '  $CFG->debugdisplay = 1;' . "\n\n";

// 4. Comprobar si hay settings personalizados
echo "--- Parámetros de config.php (todos) ---\n";
$config_vars = get_object_vars($CFG);
echo "Total de configuraciones: " . count($config_vars) . "\n";
echo "Primeras 15:\n";
$i = 0;
foreach ($config_vars as $key => $value) {
    if ($i >= 15) break;
    if (is_string($value) || is_numeric($value)) {
        echo "  \$CFG->{$key} = " . substr((string)$value, 0, 60) . "\n";
    }
    $i++;
}