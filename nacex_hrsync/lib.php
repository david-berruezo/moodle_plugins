<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_hrsync
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

function local_nacex_hrsync_extend_navigation(global_navigation $navigation) {
    if (isloggedin() && !isguestuser()) {
        $context = context_system::instance();
        if (has_capability('local/nacex_hrsync:viewlogs', $context)) {
            $navigation->add(
                get_string('sync_log', 'local_nacex_hrsync'),
                new moodle_url('/local/nacex_hrsync/sync_log.php'),
                navigation_node::TYPE_CUSTOM,
                null,
                'nacex_hrsync',
                new pix_icon('i/user', '')
            );
        }
    }
}
