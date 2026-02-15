<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_nacex_hrsync\task\sync_users',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '6',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '1-5',
    ],
];
