<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    report_nacex_training
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Training report - Main page.
 *
 * Displays comprehensive training metrics by department, course, and employee.
 * Supports filtering and export to Excel/PDF.
 */

require_once(__DIR__ . '/../../config.php');

use report_nacex_training\report_generator;

require_login();
$context = context_system::instance();
require_capability('report/nacex_training:view', $context);

$PAGE->set_url(new moodle_url('/report/nacex_training/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_nacex_training'));
$PAGE->set_heading(get_string('pluginname', 'report_nacex_training'));
$PAGE->set_pagelayout('report');

// Filters.
$department = optional_param('department', '', PARAM_TEXT);
$courseid   = optional_param('courseid', 0, PARAM_INT);
$status     = optional_param('status', '', PARAM_ALPHA);
$datefrom   = optional_param('datefrom', '', PARAM_TEXT);
$dateto     = optional_param('dateto', '', PARAM_TEXT);
$export     = optional_param('export', '', PARAM_ALPHA);

$filters = [
    'department' => $department,
    'courseid'   => $courseid,
    'status'     => $status,
    'datefrom'   => $datefrom,
    'dateto'     => $dateto,
];

$generator = new report_generator($filters);

// Handle export.
if ($export === 'excel') {
    $generator->export_excel();
    die();
}
if ($export === 'pdf') {
    $generator->export_pdf();
    die();
}

// Get report data.
$reportdata = $generator->get_report_data();
$departments = $generator->get_departments();
$courses = $generator->get_courses_with_tracking();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_nacex_training'));

// --- Filter Form ---
echo '<form method="get" class="mb-4 p-3 bg-light rounded">';
echo '<div class="form-row">';

// Department filter.
echo '<div class="col-md-3 mb-2">';
echo '<label>' . get_string('department', 'report_nacex_training') . '</label>';
echo '<select name="department" class="form-control">';
echo '<option value="">' . get_string('all', 'report_nacex_training') . '</option>';
foreach ($departments as $dept) {
    $selected = ($department === $dept) ? ' selected' : '';
    echo '<option value="' . s($dept) . '"' . $selected . '>' . s($dept) . '</option>';
}
echo '</select></div>';

// Course filter.
echo '<div class="col-md-3 mb-2">';
echo '<label>' . get_string('course') . '</label>';
echo '<select name="courseid" class="form-control">';
echo '<option value="0">' . get_string('all', 'report_nacex_training') . '</option>';
foreach ($courses as $c) {
    $selected = ($courseid == $c->id) ? ' selected' : '';
    echo '<option value="' . $c->id . '"' . $selected . '>' . format_string($c->fullname) . '</option>';
}
echo '</select></div>';

// Status filter.
echo '<div class="col-md-2 mb-2">';
echo '<label>' . get_string('status') . '</label>';
echo '<select name="status" class="form-control">';
echo '<option value="">' . get_string('all', 'report_nacex_training') . '</option>';
$statuses = ['pending', 'in_progress', 'completed', 'overdue'];
foreach ($statuses as $s) {
    $selected = ($status === $s) ? ' selected' : '';
    echo '<option value="' . $s . '"' . $selected . '>' .
         get_string('status_' . $s, 'report_nacex_training') . '</option>';
}
echo '</select></div>';

// Date range.
echo '<div class="col-md-2 mb-2">';
echo '<label>' . get_string('from', 'report_nacex_training') . '</label>';
echo '<input type="date" name="datefrom" value="' . s($datefrom) . '" class="form-control">';
echo '</div>';

echo '<div class="col-md-2 mb-2">';
echo '<label>' . get_string('to', 'report_nacex_training') . '</label>';
echo '<input type="date" name="dateto" value="' . s($dateto) . '" class="form-control">';
echo '</div>';

echo '</div>'; // .form-row

echo '<div class="mt-2">';
echo '<button type="submit" class="btn btn-primary mr-2">' . get_string('filter') . '</button>';
echo '<a href="' . new moodle_url('/report/nacex_training/index.php') . '" class="btn btn-secondary mr-2">' .
     get_string('clear', 'report_nacex_training') . '</a>';

// Export buttons.
$exportparams = array_merge($filters, ['export' => 'excel']);
echo '<a href="' . new moodle_url('/report/nacex_training/index.php', $exportparams) . '" class="btn btn-success mr-2">';
echo '<i class="fa fa-file-excel-o"></i> ' . get_string('export_excel', 'report_nacex_training') . '</a>';

$exportparams['export'] = 'pdf';
echo '<a href="' . new moodle_url('/report/nacex_training/index.php', $exportparams) . '" class="btn btn-danger">';
echo '<i class="fa fa-file-pdf-o"></i> ' . get_string('export_pdf', 'report_nacex_training') . '</a>';

echo '</div></form>';

// --- Summary Cards ---
$summary = $generator->get_global_summary();

echo '<div class="row mb-4">';
$cards = [
    ['label' => get_string('total_employees', 'report_nacex_training'), 'value' => $summary['total_users'], 'color' => 'primary'],
    ['label' => get_string('total_assignments', 'report_nacex_training'), 'value' => $summary['total_records'], 'color' => 'info'],
    ['label' => get_string('completed', 'report_nacex_training'), 'value' => $summary['completed'], 'color' => 'success'],
    ['label' => get_string('completion_rate', 'report_nacex_training'), 'value' => $summary['completion_rate'] . '%', 'color' => 'warning'],
    ['label' => get_string('overdue', 'report_nacex_training'), 'value' => $summary['overdue'], 'color' => 'danger'],
];

foreach ($cards as $card) {
    echo '<div class="col-md">';
    echo '<div class="card border-' . $card['color'] . ' mb-2">';
    echo '<div class="card-body text-center p-2">';
    echo '<h3 class="text-' . $card['color'] . ' mb-0">' . $card['value'] . '</h3>';
    echo '<small class="text-muted">' . $card['label'] . '</small>';
    echo '</div></div></div>';
}
echo '</div>';

// --- Detail Table ---
echo '<div class="table-responsive">';
echo '<table class="table table-striped table-hover table-sm">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . get_string('employee', 'report_nacex_training') . '</th>';
echo '<th>' . get_string('email') . '</th>';
echo '<th>' . get_string('department', 'report_nacex_training') . '</th>';
echo '<th>' . get_string('course') . '</th>';
echo '<th>' . get_string('status') . '</th>';
echo '<th>' . get_string('duedate', 'report_nacex_training') . '</th>';
echo '<th>' . get_string('completiondate', 'report_nacex_training') . '</th>';
echo '<th>' . get_string('training_hours', 'report_nacex_training') . '</th>';
echo '</tr></thead><tbody>';

if (empty($reportdata)) {
    echo '<tr><td colspan="8" class="text-center">' .
         get_string('no_data', 'report_nacex_training') . '</td></tr>';
} else {
    foreach ($reportdata as $row) {
        $statusclass = [
            'completed'   => 'badge-success',
            'in_progress' => 'badge-info',
            'pending'     => 'badge-warning',
            'overdue'     => 'badge-danger',
        ];

        echo '<tr>';
        echo '<td>' . s($row->lastname . ', ' . $row->firstname) . '</td>';
        echo '<td>' . s($row->email) . '</td>';
        echo '<td>' . s($row->department) . '</td>';
        echo '<td>' . format_string($row->coursename) . '</td>';
        echo '<td><span class="badge ' . ($statusclass[$row->status] ?? 'badge-secondary') . '">';
        echo get_string('status_' . $row->status, 'report_nacex_training');
        echo '</span></td>';
        echo '<td>' . (!empty($row->duedate) ? userdate($row->duedate, '%d/%m/%Y') : '-') . '</td>';
        echo '<td>' . (!empty($row->completiondate) ? userdate($row->completiondate, '%d/%m/%Y') : '-') . '</td>';
        echo '<td>' . ($row->training_hours ?? '-') . '</td>';
        echo '</tr>';
    }
}

echo '</tbody></table>';
echo '</div>';

echo $OUTPUT->footer();
