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
 * Library functions for local_nacex_tracking.
 *
 * @package    local_nacex_tracking
 * @copyright  2024 Nacex Formación
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add navigation nodes to the site navigation.
 *
 * @param global_navigation $navigation The navigation tree.
 */
function local_nacex_tracking_extend_navigation(global_navigation $navigation) {
    global $USER, $PAGE;

    if (isloggedin() && !isguestuser()) {
        $context = context_system::instance();

        // Add menu item for managers.
        if (has_capability('local/nacex_tracking:manage', $context)) {
            $node = $navigation->add(
                get_string('pluginname', 'local_nacex_tracking'),
                new moodle_url('/local/nacex_tracking/manage.php'),
                navigation_node::TYPE_CUSTOM,
                null,
                'nacex_tracking',
                new pix_icon('i/completion-auto-enabled', '')
            );
        }
    }
}

/**
 * Add settings to the admin settings page.
 *
 * @param settings_navigation $settingsnav The settings navigation object.
 * @param context $context The context.
 */
function local_nacex_tracking_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    // Settings are handled via settings.php.
}
