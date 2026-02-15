<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_hrsync
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../config.php');

use local_nacex_hrsync\sync_manager;

require_login();
$context = context_system::instance();
require_capability('local/nacex_hrsync:viewlogs', $context);

$PAGE->set_url(new moodle_url('/local/nacex_hrsync/sync_log.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('sync_log', 'local_nacex_hrsync'));
$PAGE->set_heading(get_string('sync_log', 'local_nacex_hrsync'));
$PAGE->set_pagelayout('admin');

// Handle manual sync trigger.
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'runsync' && confirm_sesskey()) {
    require_capability('local/nacex_hrsync:manage', $context);

    try {
        $manager = new sync_manager();
        $stats = $manager->execute();

        $msg = get_string('sync_result', 'local_nacex_hrsync', (object) $stats);
        redirect(
            new moodle_url('/local/nacex_hrsync/sync_log.php'),
            $msg,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\Exception $e) {
        redirect(
            new moodle_url('/local/nacex_hrsync/sync_log.php'),
            get_string('sync_error', 'local_nacex_hrsync') . ': ' . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();

// Manual sync button.
if (has_capability('local/nacex_hrsync:manage', $context)) {
    $syncurl = new moodle_url('/local/nacex_hrsync/sync_log.php', [
        'action'  => 'runsync',
        'sesskey' => sesskey(),
    ]);
    echo '<div class="mb-3">';
    echo '<a href="' . $syncurl . '" class="btn btn-primary" onclick="return confirm(\'' .
         get_string('confirm_sync', 'local_nacex_hrsync') . '\');">';
    echo get_string('run_sync', 'local_nacex_hrsync');
    echo '</a></div>';
}

// Display logs.
$logs = sync_manager::get_logs(100);

echo '<table class="table table-striped table-sm">';
echo '<thead><tr>';
echo '<th>' . get_string('date') . '</th>';
echo '<th>' . get_string('action') . '</th>';
echo '<th>' . get_string('employeeid', 'local_nacex_hrsync') . '</th>';
echo '<th>' . get_string('details') . '</th>';
echo '<th>' . get_string('status') . '</th>';
echo '</tr></thead><tbody>';

foreach ($logs as $log) {
    $statusclass = $log->status === 'success' ? 'badge-success' : 'badge-danger';
    echo '<tr>';
    echo '<td>' . userdate($log->timecreated, '%d/%m/%Y %H:%M') . '</td>';
    echo '<td><span class="badge badge-info">' . s($log->action) . '</span></td>';
    echo '<td>' . s($log->employeeid) . '</td>';
    echo '<td>' . s($log->details) . '</td>';
    echo '<td><span class="badge ' . $statusclass . '">' . s($log->status) . '</span></td>';
    echo '</tr>';
}

if (empty($logs)) {
    echo '<tr><td colspan="5" class="text-center">' . get_string('nologs', 'local_nacex_hrsync') . '</td></tr>';
}

echo '</tbody></table>';

echo $OUTPUT->footer();
