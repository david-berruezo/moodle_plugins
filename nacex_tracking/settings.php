<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin settings for local_nacex_tracking.
 *
 * @package    local_nacex_tracking
 * @copyright  2024 Nacex Formación
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_nacex_tracking',
        get_string('pluginname', 'local_nacex_tracking')
    );

    // Departments list (comma separated).
    $settings->add(new admin_setting_configtextarea(
        'local_nacex_tracking/departments',
        get_string('settings_departments', 'local_nacex_tracking'),
        get_string('settings_departments_desc', 'local_nacex_tracking'),
        'Almacén,Reparto,Atención al cliente,Administración,IT'
    ));

    // Enable email notifications.
    $settings->add(new admin_setting_configcheckbox(
        'local_nacex_tracking/enable_notifications',
        get_string('settings_notifications', 'local_nacex_tracking'),
        get_string('settings_notifications_desc', 'local_nacex_tracking'),
        1
    ));

    // Days before due date to send reminder.
    $settings->add(new admin_setting_configtext(
        'local_nacex_tracking/reminder_days',
        get_string('settings_reminder_days', 'local_nacex_tracking'),
        get_string('settings_reminder_days_desc', 'local_nacex_tracking'),
        7,
        PARAM_INT
    ));

    // Days after due date to mark as overdue.
    $settings->add(new admin_setting_configtext(
        'local_nacex_tracking/overdue_days',
        get_string('settings_overdue_days', 'local_nacex_tracking'),
        get_string('settings_overdue_days_desc', 'local_nacex_tracking'),
        0,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
