<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_hrsync
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_nacex_hrsync\task;

use local_nacex_hrsync\sync_manager;

/**
 * Scheduled task to sync HR data with Moodle users.
 */
class sync_users extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_sync_users', 'local_nacex_hrsync');
    }

    public function execute(): void {
        mtrace('Nacex HR Sync: Starting user synchronization...');

        try {
            $manager = new sync_manager();
            $stats = $manager->execute();

            error_log("Nacex HR Sync: Completed. " .
                "Created: {$stats['created']}, " .
                "Updated: {$stats['updated']}, " .
                "Suspended: {$stats['suspended']}, " .
                "Skipped: {$stats['skipped']}, " .
                "Errors: {$stats['errors']}");

            mtrace("Nacex HR Sync: Completed. " .
                "Created: {$stats['created']}, " .
                "Updated: {$stats['updated']}, " .
                "Suspended: {$stats['suspended']}, " .
                "Skipped: {$stats['skipped']}, " .
                "Errors: {$stats['errors']}");
        } catch (\Exception $e) {
            mtrace('Nacex HR Sync ERROR: ' . $e->getMessage());
        }
    }
}
