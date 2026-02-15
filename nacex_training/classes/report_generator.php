<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    report_nacex_training
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace report_nacex_training;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/excellib.class.php');

/**
 * Class report_generator
 *
 * Generates training reports with filtering and export capabilities.
 */
class report_generator {

    /** @var array Active filters */
    private array $filters;

    /**
     * Constructor.
     *
     * @param array $filters Report filters.
     */
    public function __construct(array $filters = []) {
        $this->filters = $filters;
    }

    /**
     * Get the main report data with applied filters.
     *
     * @return array Report records.
     */
    public function get_report_data(): array {
        global $DB;

        $params = [];
        $where = ['1 = 1'];

        $sql = "SELECT t.id, t.userid, t.courseid, t.status, t.completiondate,
                       u.firstname, u.lastname, u.email,
                       c.fullname AS coursename, c.shortname AS courseshortname,
                       m.department, m.duedate,
                       CASE WHEN t.completiondate IS NOT NULL AND t.timecreated > 0
                            THEN ROUND((t.completiondate - t.timecreated) / 3600.0, 1)
                            ELSE NULL
                       END AS training_hours
                  FROM {local_nacex_tracking} t
                  JOIN {user} u ON u.id = t.userid
                  JOIN {course} c ON c.id = t.courseid
                  JOIN {local_nacex_mandatory} m ON m.id = t.mandatoryid
                 WHERE u.deleted = 0";

        // Apply filters.
        if (!empty($this->filters['department'])) {
            $sql .= " AND m.department = :department";
            $params['department'] = $this->filters['department'];
        }

        if (!empty($this->filters['courseid'])) {
            $sql .= " AND t.courseid = :courseid";
            $params['courseid'] = $this->filters['courseid'];
        }

        if (!empty($this->filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $this->filters['status'];
        }

        if (!empty($this->filters['datefrom'])) {
            $sql .= " AND t.timecreated >= :datefrom";
            $params['datefrom'] = strtotime($this->filters['datefrom']);
        }

        if (!empty($this->filters['dateto'])) {
            $sql .= " AND t.timecreated <= :dateto";
            $params['dateto'] = strtotime($this->filters['dateto'] . ' 23:59:59');
        }

        $sql .= " ORDER BY m.department ASC, u.lastname ASC, u.firstname ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get global summary statistics.
     *
     * @return array Summary data.
     */
    public function get_global_summary(): array {
        global $DB;

        $data = $this->get_report_data();

        $summary = [
            'total_users'     => count(array_unique(array_column((array) $data, 'userid'))),
            'total_records'   => count($data),
            'completed'       => 0,
            'in_progress'     => 0,
            'pending'         => 0,
            'overdue'         => 0,
            'completion_rate' => 0,
        ];

        foreach ($data as $row) {
            if (isset($summary[$row->status])) {
                $summary[$row->status]++;
            }
        }

        $summary['completion_rate'] = $summary['total_records'] > 0
            ? round(($summary['completed'] / $summary['total_records']) * 100, 1)
            : 0;

        return $summary;
    }

    /**
     * Get list of departments.
     *
     * @return array Department names.
     */
    public function get_departments(): array {
        $departments = get_config('local_nacex_tracking', 'departments');
        if (empty($departments)) {
            return [];
        }
        return array_map('trim', explode(',', $departments));
    }

    /**
     * Get courses that have tracking records.
     *
     * @return array Course records.
     */
    public function get_courses_with_tracking(): array {
        global $DB;

        $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname
                  FROM {course} c
                  JOIN {local_nacex_mandatory} m ON m.courseid = c.id
              ORDER BY c.fullname ASC";

        return $DB->get_records_sql($sql);
    }

    /**
     * Export report data to Excel.
     */
    public function export_excel(): void {
        global $CFG;

        $data = $this->get_report_data();
        $filename = 'nacex_training_report_' . date('Ymd_His');

        $workbook = new \MoodleExcelWorkbook("-");
        $workbook->send($filename . '.xlsx');

        // Summary sheet.
        $summary = $this->get_global_summary();
        $sheetsummary = $workbook->add_worksheet(get_string('summary', 'report_nacex_training'));

        $titleformat = $workbook->add_format(['bold' => 1, 'size' => 14]);
        $headerformat = $workbook->add_format(['bold' => 1, 'bg_color' => '#4472C4', 'color' => 'white']);
        $numberformat = $workbook->add_format(['bold' => 1, 'size' => 16]);

        $sheetsummary->write_string(0, 0, get_string('pluginname', 'report_nacex_training'), $titleformat);
        $sheetsummary->write_string(1, 0, get_string('generated_on', 'report_nacex_training') . ': ' . userdate(time()));

        $row = 3;
        $summaryitems = [
            'total_employees'   => $summary['total_users'],
            'total_assignments' => $summary['total_records'],
            'completed'         => $summary['completed'],
            'in_progress'       => $summary['in_progress'],
            'pending'           => $summary['pending'],
            'overdue'           => $summary['overdue'],
            'completion_rate'   => $summary['completion_rate'] . '%',
        ];

        foreach ($summaryitems as $key => $value) {
            $sheetsummary->write_string($row, 0, get_string($key, 'report_nacex_training'));
            $sheetsummary->write_string($row, 1, (string) $value, $numberformat);
            $row++;
        }

        // Detail sheet.
        $sheetdetail = $workbook->add_worksheet(get_string('detail', 'report_nacex_training'));

        $headers = [
            get_string('employee', 'report_nacex_training'),
            get_string('email'),
            get_string('department', 'report_nacex_training'),
            get_string('course'),
            get_string('status'),
            get_string('duedate', 'report_nacex_training'),
            get_string('completiondate', 'report_nacex_training'),
            get_string('training_hours', 'report_nacex_training'),
        ];

        foreach ($headers as $col => $header) {
            $sheetdetail->write_string(0, $col, $header, $headerformat);
        }

        $row = 1;
        foreach ($data as $record) {
            $sheetdetail->write_string($row, 0, $record->lastname . ', ' . $record->firstname);
            $sheetdetail->write_string($row, 1, $record->email);
            $sheetdetail->write_string($row, 2, $record->department);
            $sheetdetail->write_string($row, 3, $record->coursename);
            $sheetdetail->write_string($row, 4, get_string('status_' . $record->status, 'report_nacex_training'));
            $sheetdetail->write_string($row, 5,
                !empty($record->duedate) ? userdate($record->duedate, '%d/%m/%Y') : '-');
            $sheetdetail->write_string($row, 6,
                !empty($record->completiondate) ? userdate($record->completiondate, '%d/%m/%Y') : '-');
            $sheetdetail->write_string($row, 7, $record->training_hours ?? '-');
            $row++;
        }

        // Set column widths.
        $sheetdetail->set_column(0, 0, 30);
        $sheetdetail->set_column(1, 1, 30);
        $sheetdetail->set_column(2, 2, 20);
        $sheetdetail->set_column(3, 3, 40);
        $sheetdetail->set_column(4, 4, 15);
        $sheetdetail->set_column(5, 6, 15);
        $sheetdetail->set_column(7, 7, 12);

        $workbook->close();
    }

    /**
     * Export report data to PDF.
     */
    public function export_pdf(): void {
        global $CFG;
        require_once($CFG->libdir . '/pdflib.php');

        $data = $this->get_report_data();
        $summary = $this->get_global_summary();

        $pdf = new \pdf('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Nacex Training Report');
        $pdf->SetAuthor('Nacex Formación');
        $pdf->SetTitle(get_string('pluginname', 'report_nacex_training'));
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Title.
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, get_string('pluginname', 'report_nacex_training'), 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, get_string('generated_on', 'report_nacex_training') . ': ' . userdate(time()), 0, 1, 'C');
        $pdf->Ln(5);

        // Summary.
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, get_string('summary', 'report_nacex_training'), 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $summarytext = get_string('total_employees', 'report_nacex_training') . ': ' . $summary['total_users'] . ' | ' .
                       get_string('completed', 'report_nacex_training') . ': ' . $summary['completed'] . ' | ' .
                       get_string('pending', 'report_nacex_training') . ': ' . $summary['pending'] . ' | ' .
                       get_string('overdue', 'report_nacex_training') . ': ' . $summary['overdue'] . ' | ' .
                       get_string('completion_rate', 'report_nacex_training') . ': ' . $summary['completion_rate'] . '%';
        $pdf->Cell(0, 6, $summarytext, 0, 1);
        $pdf->Ln(5);

        // Table header.
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(68, 114, 196);
        $pdf->SetTextColor(255, 255, 255);

        $widths = [45, 50, 30, 60, 25, 25, 25, 20];
        $headers = [
            get_string('employee', 'report_nacex_training'),
            get_string('email'),
            get_string('department', 'report_nacex_training'),
            get_string('course'),
            get_string('status'),
            get_string('duedate', 'report_nacex_training'),
            get_string('completiondate', 'report_nacex_training'),
            get_string('training_hours', 'report_nacex_training'),
        ];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Table data.
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;

        foreach ($data as $record) {
            if ($fill) {
                $pdf->SetFillColor(230, 240, 250);
            }

            $pdf->Cell($widths[0], 6, $record->lastname . ', ' . $record->firstname, 1, 0, 'L', $fill);
            $pdf->Cell($widths[1], 6, $record->email, 1, 0, 'L', $fill);
            $pdf->Cell($widths[2], 6, $record->department, 1, 0, 'C', $fill);
            $pdf->Cell($widths[3], 6, $record->coursename, 1, 0, 'L', $fill);
            $pdf->Cell($widths[4], 6,
                get_string('status_' . $record->status, 'report_nacex_training'), 1, 0, 'C', $fill);
            $pdf->Cell($widths[5], 6,
                !empty($record->duedate) ? userdate($record->duedate, '%d/%m/%Y') : '-', 1, 0, 'C', $fill);
            $pdf->Cell($widths[6], 6,
                !empty($record->completiondate) ? userdate($record->completiondate, '%d/%m/%Y') : '-', 1, 0, 'C', $fill);
            $pdf->Cell($widths[7], 6, $record->training_hours ?? '-', 1, 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }

        $filename = 'nacex_training_report_' . date('Ymd_His') . '.pdf';
        $pdf->Output($filename, 'D');
    }
}
