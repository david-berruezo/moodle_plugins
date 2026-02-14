<?php
// local/sandbox_ddl/db/access.php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/sandbox_ddl:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];