<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_tracking
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_nacex_tracking\task;

use local_nacex_tracking\manager;

/**
 * Scheduled task to send reminder notifications.
 */
class send_reminders extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_send_reminders', 'local_nacex_tracking');
    }

    public function execute(): void {
        mtrace('Nacex Tracking: Sending reminders...');
        manager::send_reminders();
        mtrace('Nacex Tracking: Reminders sent.');
    }
}
