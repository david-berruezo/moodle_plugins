<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'report/nacex_training:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
    ],
];
