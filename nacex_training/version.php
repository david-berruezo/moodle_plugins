<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'report_nacex_training';
$plugin->version   = 2024011500;
$plugin->requires  = 2022041900;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
$plugin->dependencies = [
    'local_nacex_tracking' => 2024011500,
];
