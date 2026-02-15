<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_hrsync
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_nacex_hrsync', get_string('pluginname', 'local_nacex_hrsync'));

    // CSV file path for HR data import.
    $settings->add(new admin_setting_configtext(
        'local_nacex_hrsync/csv_path',
        get_string('settings_csv_path', 'local_nacex_hrsync'),
        get_string('settings_csv_path_desc', 'local_nacex_hrsync'),
        '/var/data/nacex/employees.csv'
    ));

    // CSV delimiter.
    $settings->add(new admin_setting_configselect(
        'local_nacex_hrsync/csv_delimiter',
        get_string('settings_csv_delimiter', 'local_nacex_hrsync'),
        get_string('settings_csv_delimiter_desc', 'local_nacex_hrsync'),
        ';',
        [';' => '; (punto y coma)', ',' => ', (coma)', "\t" => 'TAB']
    ));

    // Default password for new users.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_nacex_hrsync/default_password',
        get_string('settings_default_password', 'local_nacex_hrsync'),
        get_string('settings_default_password_desc', 'local_nacex_hrsync'),
        'Nacex2024!'
    ));

    // Auto-suspend users not in CSV.
    $settings->add(new admin_setting_configcheckbox(
        'local_nacex_hrsync/auto_suspend',
        get_string('settings_auto_suspend', 'local_nacex_hrsync'),
        get_string('settings_auto_suspend_desc', 'local_nacex_hrsync'),
        1
    ));

    // Default role for new users.
    $roles = role_get_names(null, ROLENAME_ORIGINAL);
    $roleoptions = [];
    foreach ($roles as $role) {
        $roleoptions[$role->id] = $role->localname;
    }
    $settings->add(new admin_setting_configselect(
        'local_nacex_hrsync/default_role',
        get_string('settings_default_role', 'local_nacex_hrsync'),
        get_string('settings_default_role_desc', 'local_nacex_hrsync'),
        5, // Student role by default.
        $roleoptions
    ));

    $ADMIN->add('localplugins', $settings);
}
