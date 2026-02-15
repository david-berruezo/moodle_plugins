<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    report_nacex_training
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

/**
 * Add report link to the site admin reports menu.
 *
 * @param navigation_node $navigation The navigation node.
 * @param stdClass $course The course object.
 * @param context $context The context.
 */
function report_nacex_training_extend_navigation_course(navigation_node $navigation, $course, $context) {
    if (has_capability('report/nacex_training:view', context_system::instance())) {
        $url = new moodle_url('/report/nacex_training/index.php');
        $navigation->add(
            get_string('pluginname', 'report_nacex_training'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'nacex_training_report',
            new pix_icon('i/report', '')
        );
    }
}
