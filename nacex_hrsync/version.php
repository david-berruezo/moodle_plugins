<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_hrsync
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_nacex_hrsync';
$plugin->version   = 2024011500;
$plugin->requires  = 2022041900; // Moodle 4.0
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.1.0';
$plugin->dependencies = [
    'local_nacex_tracking' => 2024011500,
];
