<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    // Observar nuestro evento personalizado
    [
        'eventname' => '\sandbox\classes\event\note_created',
        'callback' => '\local_sandbox\observer\note_observer::on_note_created',
    ],
    // Observar eventos del core
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_sandbox\observer\note_observer::on_user_loggedin',
    ],
];