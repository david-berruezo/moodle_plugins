<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_tracking
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

$string['pluginname'] = 'Nacex - Training Tracking';
$string['manage_title'] = 'Mandatory Course Management';
$string['assign_course'] = 'Assign mandatory course';
$string['department'] = 'Department';
$string['duedate'] = 'Due date';
$string['assign'] = 'Assign';
$string['tracking_summary'] = 'Department Summary';
$string['all_departments'] = 'All departments';
$string['mandatory_courses'] = 'Assigned mandatory courses';
$string['completed'] = 'Completed';
$string['in_progress'] = 'In progress';
$string['pending'] = 'Pending';
$string['overdue'] = 'Overdue';
$string['my_progress'] = 'My Training Progress';
$string['no_mandatory_courses'] = 'You have no mandatory courses assigned.';
$string['completiondate'] = 'Completion date';
$string['go_to_course'] = 'Go to course';
$string['course_assigned'] = 'Mandatory course assigned successfully.';
$string['course_deleted'] = 'Assignment deleted successfully.';
$string['confirm_delete'] = 'Are you sure you want to delete this assignment?';
$string['status_completed'] = 'Completed';
$string['status_in_progress'] = 'In progress';
$string['status_pending'] = 'Pending';
$string['status_overdue'] = 'Overdue';
$string['settings_departments'] = 'Departments';
$string['settings_departments_desc'] = 'Comma-separated list of departments.';
$string['settings_notifications'] = 'Enable notifications';
$string['settings_notifications_desc'] = 'Send email reminders to employees with pending courses.';
$string['settings_reminder_days'] = 'Reminder days in advance';
$string['settings_reminder_days_desc'] = 'Number of days before due date to send the reminder.';
$string['settings_overdue_days'] = 'Days to mark as overdue';
$string['settings_overdue_days_desc'] = 'Number of days after the due date to mark as overdue (0 = immediate).';
$string['reminder_subject'] = 'Reminder: Mandatory course "{$a}"';
$string['reminder_body'] = 'Hello {$a->firstname},

This is a reminder that you have a pending mandatory course: "{$a->coursename}".
Due date: {$a->duedate}

Please access the platform and complete the course.

Regards,
Training Department - Nacex';
$string['task_sync_completion'] = 'Sync course completion status';
$string['task_send_reminders'] = 'Send pending course reminders';
$string['messageprovider:reminder'] = 'Mandatory course reminder';
