<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_tracking
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_nacex_tracking\task;

use local_nacex_tracking\manager;

/**
 * Scheduled task to sync course completion status.
 */
class sync_completion extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_sync_completion', 'local_nacex_tracking');
    }

    public function execute(): void {
        mtrace('Nacex Tracking: Syncing completion status...');
        manager::sync_completion_status();
        mtrace('Nacex Tracking: Sync completed.');
    }
}
