<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_tracking
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_nacex_tracking\task\sync_completion',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '*/2',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_nacex_tracking\task\send_reminders',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '8',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '1-5',
    ],
];
