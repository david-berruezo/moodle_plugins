<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_sandbox_get_greeting' => [
        'classname' => 'local_sandbox\external\get_greeting',
        'description' => 'Returns a personalised greeting',
        'type' => 'read',
        'ajax' => true,    // ← IMPORTANTE: permite llamarlo desde core/ajax
        'capabilities' => '',      // Sin capabilities especiales requeridas
    ],
];