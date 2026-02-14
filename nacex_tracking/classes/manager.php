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
 * Tracking manager class.
 *
 * @package    local_nacex_tracking
 * @copyright  2024 Nacex Formación
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_nacex_tracking;

defined('MOODLE_INTERNAL') || die();

/**
 * Class manager
 *
 * Handles all the logic for mandatory course tracking.
 */
class manager {

    /**
     * Get available departments from settings.
     *
     * @return array List of department names.
     */
    public static function get_departments(): array {
        $departments = get_config('local_nacex_tracking', 'departments');
        if (empty($departments)) {
            return [];
        }
        return array_map('trim', explode(',', $departments));
    }

    /**
     * Assign a course as mandatory for a department.
     *
     * @param int $courseid The course ID.
     * @param string $department The department name.
     * @param int|null $duedate Optional due date timestamp.
     * @return int The ID of the new mandatory assignment.
     */
    public static function assign_mandatory_course(int $courseid, string $department, ?int $duedate = null): int {

        global $DB, $USER;

        $now = time();

        $record = new \stdClass();
        $record->courseid    = $courseid;
        $record->department  = $department;
        $record->duedate     = $duedate;
        $record->createdby   = $USER->id;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $mandatoryid = $DB->insert_record('local_nacex_mandatory', $record);

        // Create tracking records for all users in this department.
        self::create_tracking_records($mandatoryid, $courseid, $department);

        return $mandatoryid;
    }


    /**
     * Create tracking records for users in a department.
     *
     * @param int $mandatoryid The mandatory assignment ID.
     * @param int $courseid The course ID.
     * @param string $department The department name.
     */
    private static function create_tracking_records(int $mandatoryid, int $courseid, string $department): void {
        global $DB;

        $now = time();

        // Get users in this department (using profile field 'department').
        $sql = "SELECT u.id
                  FROM {user} u
                  JOIN {user_info_data} uid ON uid.userid = u.id
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uif.shortname = 'department'
                   AND uid.data = :department
                   AND u.deleted = 0
                   AND u.suspended = 0";

        $users = $DB->get_records_sql($sql, ['department' => $department]);

        foreach ($users as $user) {
            // Check if tracking record already exists.
            $exists = $DB->record_exists('local_nacex_tracking', [
                'userid'      => $user->id,
                'courseid'    => $courseid,
                'mandatoryid' => $mandatoryid,
            ]);

            if (!$exists) {
                $tracking = new \stdClass();
                $tracking->userid       = $user->id;
                $tracking->courseid     = $courseid;
                $tracking->mandatoryid  = $mandatoryid;
                $tracking->status       = 'pending';
                $tracking->notified     = 0;
                $tracking->timecreated  = $now;
                $tracking->timemodified = $now;

                $DB->insert_record('local_nacex_tracking', $tracking);
            }
        }
    }

    /**
     * Get mandatory courses for a specific department.
     *
     * @param string $department The department name.
     * @return array List of mandatory course records.
     */
    public static function get_mandatory_courses(string $department): array {
        global $DB;

        $sql = "SELECT m.*, c.fullname AS coursename, c.shortname AS courseshortname
                  FROM {local_nacex_mandatory} m
                  JOIN {course} c ON c.id = m.courseid
                 WHERE m.department = :department
              ORDER BY m.timecreated DESC";

        return $DB->get_records_sql($sql, ['department' => $department]);
    }

    /**
     * Get tracking status for a specific user.
     *
     * @param int $userid The user ID.
     * @return array List of tracking records with course info.
     */
    public static function get_user_tracking(int $userid): array {
        global $DB;

        $sql = "SELECT t.*, c.fullname AS coursename, c.shortname AS courseshortname,
                       m.department, m.duedate
                  FROM {local_nacex_tracking} t
                  JOIN {course} c ON c.id = t.courseid
                  JOIN {local_nacex_mandatory} m ON m.id = t.mandatoryid
                 WHERE t.userid = :userid
              ORDER BY t.status ASC, m.duedate ASC";

        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Get department tracking summary.
     *
     * @param string $department The department name.
     * @return array Summary with total, completed, in_progress, pending, overdue counts.
     */
    public static function get_department_summary(string $department): array {
        global $DB;

        $sql = "SELECT t.status, COUNT(*) AS total
                  FROM {local_nacex_tracking} t
                  JOIN {local_nacex_mandatory} m ON m.id = t.mandatoryid
                 WHERE m.department = :department
              GROUP BY t.status";

        $results = $DB->get_records_sql($sql, ['department' => $department]);

        $summary = [
            'total'       => 0,
            'completed'   => 0,
            'in_progress' => 0,
            'pending'     => 0,
            'overdue'     => 0,
        ];

        foreach ($results as $row) {
            $summary[$row->status] = (int) $row->total;
            $summary['total'] += (int) $row->total;
        }

        return $summary;
    }


    /**
     * Update tracking status based on Moodle course completion.
     *
     * This method is called by the scheduled task to sync completion data.
     */
    public static function sync_completion_status(): void {

        global $DB;

        // Get all non-completed tracking records.
        $sql = "SELECT t.id, t.userid, t.courseid, t.mandatoryid, t.status,
                       m.duedate
                  FROM {local_nacex_tracking} t
                  JOIN {local_nacex_mandatory} m ON m.id = t.mandatoryid
                 WHERE t.status != 'completed'";

        $records = $DB->get_records_sql($sql);
        $now = time();

        foreach ($records as $record) {
            $completion = $DB->get_record('course_completions', [
                'userid' => $record->userid,
                'course' => $record->courseid,
            ]);

            $newstatus = $record->status;

            if ($completion && !empty($completion->timecompleted)) {
                // User has completed the course.
                $newstatus = 'completed';
                $DB->set_field('local_nacex_tracking', 'completiondate', $completion->timecompleted,
                    ['id' => $record->id]);
            } else if ($completion) {
                // User has started but not completed.
                $newstatus = 'in_progress';
            }

            // Check if overdue.
            if ($newstatus !== 'completed' && !empty($record->duedate) && $now > $record->duedate) {
                $newstatus = 'overdue';
            }

            // Update status if changed.
            if ($newstatus !== $record->status) {
                $DB->update_record('local_nacex_tracking', (object) [
                    'id'           => $record->id,
                    'status'       => $newstatus,
                    'timemodified' => $now,
                ]);
            }
        }
    }

    /**
     * Send reminder notifications to users with pending/overdue courses.
     */
    public static function send_reminders(): void {
        global $DB;

        $enabled = get_config('local_nacex_tracking', 'enable_notifications');
        if (!$enabled) {
            return;
        }

        $reminderdays = (int) get_config('local_nacex_tracking', 'reminder_days');
        $remindertime = time() + ($reminderdays * DAYSECS);

        // Get users who need reminders.
        $sql = "SELECT t.id, t.userid, t.courseid, m.duedate,
                       c.fullname AS coursename, u.firstname, u.lastname, u.email
                  FROM {local_nacex_tracking} t
                  JOIN {local_nacex_mandatory} m ON m.id = t.mandatoryid
                  JOIN {course} c ON c.id = t.courseid
                  JOIN {user} u ON u.id = t.userid
                 WHERE t.status IN ('pending', 'in_progress', 'overdue')
                   AND t.notified = 0
                   AND (m.duedate IS NULL OR m.duedate <= :remindertime)";

        $records = $DB->get_records_sql($sql, ['remindertime' => $remindertime]);

        foreach ($records as $record) {
            $user = $DB->get_record('user', ['id' => $record->userid]);
            $subject = get_string('reminder_subject', 'local_nacex_tracking', $record->coursename);
            $body = get_string('reminder_body', 'local_nacex_tracking', (object) [
                'firstname'  => $record->firstname,
                'coursename' => $record->coursename,
                'duedate'    => !empty($record->duedate) ? userdate($record->duedate) : '-',
            ]);

            // Send message using Moodle messaging API.
            $message = new \core\message\message();
            $message->component         = 'local_nacex_tracking';
            $message->name              = 'reminder';
            $message->userfrom          = \core_user::get_noreply_user();
            $message->userto            = $user;
            $message->subject           = $subject;
            $message->fullmessage       = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml   = '<p>' . nl2br(s($body)) . '</p>';
            $message->smallmessage      = $subject;
            $message->notification      = 1;

            message_send($message);

            // Mark as notified.
            $DB->set_field('local_nacex_tracking', 'notified', 1, ['id' => $record->id]);
        }
    }

    /**
     * Delete a mandatory course assignment and its tracking records.
     *
     * @param int $mandatoryid The mandatory assignment ID.
     * @return bool True if deleted successfully.
     */
    public static function delete_mandatory(int $mandatoryid): bool {
        global $DB;

        $DB->delete_records('local_nacex_tracking', ['mandatoryid' => $mandatoryid]);
        $DB->delete_records('local_nacex_mandatory', ['id' => $mandatoryid]);

        return true;
    }
}
