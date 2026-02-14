<?php
// local/sandbox/test.php
// Script de pruebas — NO USAR EN PRODUCCIÓN
define('CLI_SCRIPT', true); // Ejecutar desde línea de comandos
require_once(__DIR__ . '/../../config.php');

echo "=== Moodle Sandbox de Pruebas ===\n\n";
echo "Moodle cargado correctamente.\n";
echo "Versión: " . $CFG->version . "\n";
echo "wwwroot: " . $CFG->wwwroot . "\n";
echo "dirroot: " . $CFG->dirroot . "\n\n";
echo "dataroot: ". $CFG->dataroot . "\n\n";
echo "libdir: ". $CFG->libdir  . "\n\n";
echo "backuptempdir: ". $CFG->backuptempdir . "\n\n";
echo "tempdir: " . $CFG->tempdir . "\n\n";
echo "cachedir: " . $CFG->cachedir. "\n\n";
echo "localcachedir: " . $CFG->localcachedir . "\n\n";
echo "localrequestdir: " . $CFG->localrequestdir . "\n\n";