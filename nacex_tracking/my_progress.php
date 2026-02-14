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
 * Employee progress page.
 *
 * @package    nacex_tracking
 * @copyright  2024 Nacex Formación
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use nacex_tracking\manager;

require_login();
$context = context_system::instance();
require_capability('local/nacex_tracking:viewown', $context);

$PAGE->set_url(new moodle_url('/local/nacex_tracking/my_progress.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('my_progress', 'local_nacex_tracking'));
$PAGE->set_heading(get_string('my_progress', 'local_nacex_tracking'));
$PAGE->set_pagelayout('standard');

$userid = $USER->id;
$tracking = manager::get_user_tracking($userid);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('my_progress', 'local_nacex_tracking'), 3);

if (empty($tracking)) {
    echo $OUTPUT->notification(
        get_string('no_mandatory_courses', 'local_nacex_tracking'),
        \core\output\notification::NOTIFY_INFO
    );
} else {
    // Summary counts.
    $counts = ['completed' => 0, 'in_progress' => 0, 'pending' => 0, 'overdue' => 0];
    foreach ($tracking as $t) {
        if (isset($counts[$t->status])) {
            $counts[$t->status]++;
        }
    }

    // Progress bar.
    $total = count($tracking);
    $pct = $total > 0 ? round(($counts['completed'] / $total) * 100) : 0;

    echo '<div class="progress mb-4" style="height: 30px;">';
    echo '<div class="progress-bar bg-success" role="progressbar" style="width: ' . $pct . '%">';
    echo $pct . '% ' . get_string('completed', 'local_nacex_tracking');
    echo '</div></div>';

    // Table.
    echo '<table class="table table-striped">';
    echo '<thead><tr>';
    echo '<th>' . get_string('course') . '</th>';
    echo '<th>' . get_string('department', 'local_nacex_tracking') . '</th>';
    echo '<th>' . get_string('duedate', 'local_nacex_tracking') . '</th>';
    echo '<th>' . get_string('status') . '</th>';
    echo '<th>' . get_string('completiondate', 'local_nacex_tracking') . '</th>';
    echo '<th>' . get_string('actions') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($tracking as $t) {
        $statusclass = [
            'completed'   => 'bg-success',
            'in_progress' => 'bg-info text-dark',
            'pending'     => 'bg-warning text-dark',
            'overdue'     => 'bg-danger',
        ];

        echo '<tr>';
        echo '<td>' . format_string($t->coursename) . '</td>';
        echo '<td>' . s($t->department) . '</td>';
        echo '<td>' . (!empty($t->duedate) ? userdate($t->duedate, '%d/%m/%Y') : '-') . '</td>';
        echo '<td><span class="badge ' . ($statusclass[$t->status] ?? 'bg-secondary') . '">';
        echo get_string('status_' . $t->status, 'local_nacex_tracking');
        echo '</span></td>';
        echo '<td>' . (!empty($t->completiondate) ? userdate($t->completiondate, '%d/%m/%Y') : '-') . '</td>';
        echo '<td>';
        if ($t->status !== 'completed') {
            $courseurl = new moodle_url('/course/view.php', ['id' => $t->courseid]);
            echo '<a href="' . $courseurl . '" class="btn btn-sm btn-primary">';
            echo get_string('go_to_course', 'local_nacex_tracking') . '</a>';
        }
        echo '</td></tr>';
    }

    echo '</tbody></table>';
}

echo $OUTPUT->footer();