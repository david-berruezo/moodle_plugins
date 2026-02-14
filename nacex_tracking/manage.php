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
 * Management page for mandatory course assignments.
 *
 * @package    local_nacex_tracking
 * @copyright  2024 Nacex Formación
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_nacex_tracking\manager;

require_login();
$context = context_system::instance();
require_capability('local/nacex_tracking:manage', $context);

$PAGE->set_url(new moodle_url('/local/nacex_tracking/manage.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_title', 'local_nacex_tracking'));
$PAGE->set_heading(get_string('manage_title', 'local_nacex_tracking'));
$PAGE->set_pagelayout('admin');

// Handle form submission.
$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'assign' && confirm_sesskey()) {
    $courseid   = required_param('courseid', PARAM_INT);
    $department = required_param('department', PARAM_TEXT);
    $duedate    = optional_param('duedate', '', PARAM_TEXT);

    $duetimestamp = !empty($duedate) ? strtotime($duedate) : null;

    manager::assign_mandatory_course($courseid, $department, $duetimestamp);
    redirect(
        new moodle_url('/local/nacex_tracking/manage.php'),
        get_string('course_assigned', 'local_nacex_tracking'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'delete' && confirm_sesskey()) {
    $mandatoryid = required_param('id', PARAM_INT);
    manager::delete_mandatory($mandatoryid);
    redirect(
        new moodle_url('/local/nacex_tracking/manage.php'),
        get_string('course_deleted', 'local_nacex_tracking'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Get data for display.
$departments = manager::get_departments();
$courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, fullname, shortname');

// Filter parameter.
$filterdept = optional_param('department', '', PARAM_TEXT);

echo $OUTPUT->header();

// --- Assignment Form ---
echo $OUTPUT->heading(get_string('assign_course', 'local_nacex_tracking'), 3);
echo '<form method="post" action="" class="mb-4">';
echo '<input type="hidden" name="action" value="assign">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';

echo '<div class="mb-3 row">';
echo '<label class="col-sm-2 col-form-label">' . get_string('course') . '</label>';
echo '<div class="col-sm-4"><select name="courseid" class="form-select" required>';
echo '<option value="">-- ' . get_string('select') . ' --</option>';
foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    echo '<option value="' . $course->id . '">' . format_string($course->fullname) . '</option>';
}
echo '</select></div>';
echo '</div>';

echo '<div class="mb-3 row">';
echo '<label class="col-sm-2 col-form-label">' . get_string('department', 'local_nacex_tracking') . '</label>';
echo '<div class="col-sm-4"><select name="department" class="form-select" required>';
echo '<option value="">-- ' . get_string('select') . ' --</option>';
foreach ($departments as $dept) {
    echo '<option value="' . s($dept) . '">' . s($dept) . '</option>';
}
echo '</select></div>';
echo '</div>';

echo '<div class="mb-3 row">';
echo '<label class="col-sm-2 col-form-label">' . get_string('duedate', 'local_nacex_tracking') . '</label>';
echo '<div class="col-sm-4"><input type="date" name="duedate" class="form-control"></div>';
echo '</div>';

echo '<div class="mb-3 row">';
echo '<div class="col-sm-4 offset-sm-2">';
echo '<button type="submit" class="btn btn-primary">' . get_string('assign', 'local_nacex_tracking') . '</button>';
echo '</div></div>';
echo '</form>';

echo '<hr>';

// --- Department Summary ---
echo $OUTPUT->heading(get_string('tracking_summary', 'local_nacex_tracking'), 3);

// Department filter.
echo '<form method="get" class="mb-3">';
echo '<div class="d-flex align-items-center gap-2">';
echo '<select name="department" class="form-select" style="max-width: 300px;">';
echo '<option value="">' . get_string('all_departments', 'local_nacex_tracking') . '</option>';
foreach ($departments as $dept) {
    $selected = ($filterdept === $dept) ? ' selected' : '';
    echo '<option value="' . s($dept) . '"' . $selected . '>' . s($dept) . '</option>';
}
echo '</select>';
echo '<button type="submit" class="btn btn-secondary">' . get_string('filter') . '</button>';
echo '</div></form>';

// Display summary table.
$deptlist = !empty($filterdept) ? [$filterdept] : $departments;

echo '<table class="table table-striped">';
echo '<thead><tr>';
echo '<th>' . get_string('department', 'local_nacex_tracking') . '</th>';
echo '<th>' . get_string('total') . '</th>';
echo '<th>' . get_string('completed', 'local_nacex_tracking') . '</th>';
echo '<th>' . get_string('in_progress', 'local_nacex_tracking') . '</th>';
echo '<th>' . get_string('pending', 'local_nacex_tracking') . '</th>';
echo '<th>' . get_string('overdue', 'local_nacex_tracking') . '</th>';
echo '</tr></thead><tbody>';

foreach ($deptlist as $dept) {
    $summary = manager::get_department_summary($dept);
    echo '<tr>';
    echo '<td>' . s($dept) . '</td>';
    echo '<td>' . $summary['total'] . '</td>';
    echo '<td><span class="badge bg-success">' . $summary['completed'] . '</span></td>';
    echo '<td><span class="badge bg-info text-dark">' . $summary['in_progress'] . '</span></td>';
    echo '<td><span class="badge bg-warning text-dark">' . $summary['pending'] . '</span></td>';
    echo '<td><span class="badge bg-danger">' . $summary['overdue'] . '</span></td>';
    echo '</tr>';
}

echo '</tbody></table>';

// --- Mandatory Courses List ---
echo $OUTPUT->heading(get_string('mandatory_courses', 'local_nacex_tracking'), 3);

foreach ($deptlist as $dept) {
    $mandatorycourses = manager::get_mandatory_courses($dept);
    if (empty($mandatorycourses)) {
        continue;
    }

    echo '<h5>' . s($dept) . '</h5>';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>' . get_string('course') . '</th><th>' . get_string('duedate', 'local_nacex_tracking') .
        '</th><th>' . get_string('actions') . '</th></tr></thead><tbody>';

    foreach ($mandatorycourses as $mc) {
        echo '<tr>';
        echo '<td>' . format_string($mc->coursename) . '</td>';
        echo '<td>' . (!empty($mc->duedate) ? userdate($mc->duedate, '%d/%m/%Y') : '-') . '</td>';
        echo '<td>';
        $deleteurl = new moodle_url('/local/nacex_tracking/manage.php', [
            'action'  => 'delete',
            'id'      => $mc->id,
            'sesskey' => sesskey(),
        ]);
        echo '<a href="' . $deleteurl . '" class="btn btn-sm btn-danger" ' .
            'onclick="return confirm(\'' . get_string('confirm_delete', 'local_nacex_tracking') . '\');">' .
            get_string('delete') . '</a>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
}

echo $OUTPUT->footer();