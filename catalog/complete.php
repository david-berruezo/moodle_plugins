<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// Marca una actividad como completada y redirige de vuelta al player.
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$cmid     = required_param('cmid', PARAM_INT);

require_login($courseid);
require_sesskey();

$course = get_course($courseid);
$completion = new completion_info($course);
$modinfo = get_fast_modinfo($course);
$cm = $modinfo->get_cm($cmid);

if ($completion->is_enabled($cm) == COMPLETION_TRACKING_MANUAL) {
    $current = $completion->get_data($cm);
    $newstate = ($current->completionstate == COMPLETION_COMPLETE) ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE;
    $completion->update_state($cm, $newstate);
}

// redirect(new moodle_url('/local/catalog/learn.php', ['id' => $courseid, 'cmid' => $cmid]));
redirect(new moodle_url('/cursos/' . $slug . '/ver/' . $cmid));