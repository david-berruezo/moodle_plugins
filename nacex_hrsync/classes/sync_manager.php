<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_hrsync
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_nacex_hrsync;

defined('MOODLE_INTERNAL') || die();

/**
 * Class sync_manager
 *
 * Handles synchronization between external HR CSV and Moodle users.
 *
 * Expected CSV columns:
 * employee_id;first_name;last_name;email;department;position;active
 */
class sync_manager {

    /** @var string CSV file path */
    private string $csvpath;

    /** @var string CSV delimiter */
    private string $delimiter;

    /** @var array Sync statistics */
    private array $stats = [
        'created'   => 0,
        'updated'   => 0,
        'suspended' => 0,
        'errors'    => 0,
        'skipped'   => 0,
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->csvpath   = get_config('local_nacex_hrsync', 'csv_path');
        $this->delimiter = get_config('local_nacex_hrsync', 'csv_delimiter');
    }

    /**
     * Execute the full synchronization process.
     *
     * @return array Sync statistics.
     * @throws \moodle_exception If CSV file is not readable.
     */
    public function execute(): array {

        if (!file_exists($this->csvpath) || !is_readable($this->csvpath)) {
            throw new \moodle_exception('csv_not_found', 'local_nacex_hrsync', '', $this->csvpath);
        }

        $employees = $this->parse_csv();
        $processedids = [];

        foreach ($employees as $employee) {
            try {
                $result = $this->process_employee($employee);
                $processedids[] = $employee['employee_id'];
            } catch (\Exception $e) {
                $this->log_action('error', null, $employee['employee_id'], $e->getMessage(), 'error');
                $this->stats['errors']++;
            }
        }

        // Suspend users no longer in CSV.
        $autosuspend = get_config('local_nacex_hrsync', 'auto_suspend');
        if ($autosuspend) {
            $this->suspend_removed_users($processedids);
        }

        return $this->stats;
    }

    /**
     * Parse the CSV file.
     *
     * @return array Array of employee data.
     */
    private function parse_csv(): array {
        $employees = [];
        $handle = fopen($this->csvpath, 'r');

        if ($handle === false) {
            return [];
        }

        // Read header row.
        $headers = fgetcsv($handle, 0, $this->delimiter);
        if ($headers === false) {
            fclose($handle);
            return [];
        }

        // Clean BOM from first header if present.
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows.
            }
            $employee = array_combine($headers, array_map('trim', $row));
            if (!empty($employee['employee_id']) && !empty($employee['email'])) {
                $employees[] = $employee;
            }
        }

        fclose($handle);
        return $employees;
    }

    /**
     * Process a single employee record.
     *
     * @param array $employee Employee data from CSV.
     * @return string Action taken: created, updated, or skipped.
     */
    private function process_employee(array $employee): string {

        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $email = strtolower($employee['email']);

        // Look for existing user by email or idnumber (employee_id).
        $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
        if (!$user) {
            $user = $DB->get_record('user', ['idnumber' => $employee['employee_id'], 'deleted' => 0]);
        }

        if ($user) {
            // Update existing user.
            return $this->update_user($user, $employee);
        } else {
            // Create new user.
            return $this->create_user($employee);
        }
    }

    /**
     * Create a new Moodle user from HR data.
     *
     * @param array $employee Employee data.
     * @return string 'created'
     */
    private function create_user(array $employee): string {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $defaultpassword = get_config('local_nacex_hrsync', 'default_password');

        $user = new \stdClass();
        $user->auth        = 'manual';
        $user->confirmed   = 1;
        $user->mnethostid  = $CFG->mnet_localhost_id;
        $user->username    = $this->generate_username($employee);
        $user->password    = hash_internal_user_password($defaultpassword);
        $user->firstname   = $employee['first_name'];
        $user->lastname    = $employee['last_name'];
        $user->email       = strtolower($employee['email']);
        $user->idnumber    = $employee['employee_id'];
        $user->lang        = 'es';
        $user->suspended   = ($employee['active'] ?? '1') === '0' ? 1 : 0;
        $user->timecreated = time();
        $user->timemodified = time();

        $userid = user_create_user($user, false, false);

        // Save department and position in custom profile fields.
        $this->save_profile_field($userid, 'department', $employee['department'] ?? '');
        $this->save_profile_field($userid, 'position', $employee['position'] ?? '');
        $this->save_profile_field($userid, 'employeeid', $employee['employee_id']);

        // Assign default role at system level.
        $defaultrole = get_config('local_nacex_hrsync', 'default_role');
        if ($defaultrole) {
            $context = \context_system::instance();
            role_assign($defaultrole, $userid, $context->id);
        }

        $this->log_action('create', $userid, $employee['employee_id'],
            "User created: {$user->username} ({$user->email})");
        $this->stats['created']++;

        return 'created';
    }

    /**
     * Update an existing Moodle user from HR data.
     *
     * @param \stdClass $user Existing Moodle user.
     * @param array $employee Employee data from CSV.
     * @return string 'updated' or 'skipped'
     */
    private function update_user(\stdClass $user, array $employee): string {
        global $DB;

        $changes = [];

        // Check and update basic fields.
        $fieldmap = [
            'firstname' => 'first_name',
            'lastname'  => 'last_name',
            'idnumber'  => 'employee_id',
        ];

        foreach ($fieldmap as $moodlefield => $csvfield) {
            if (isset($employee[$csvfield]) && $user->$moodlefield !== $employee[$csvfield]) {
                $changes[$moodlefield] = $employee[$csvfield];
            }
        }

        // Check suspended status.
        $shouldsuspend = ($employee['active'] ?? '1') === '0' ? 1 : 0;
        if ((int) $user->suspended !== $shouldsuspend) {
            $changes['suspended'] = $shouldsuspend;
        }

        if (empty($changes)) {
            // Update profile fields anyway.
            $this->save_profile_field($user->id, 'department', $employee['department'] ?? '');
            $this->save_profile_field($user->id, 'position', $employee['position'] ?? '');
            $this->stats['skipped']++;
            return 'skipped';
        }

        // Apply changes.
        $update = (object) ['id' => $user->id, 'timemodified' => time()];
        foreach ($changes as $field => $value) {
            $update->$field = $value;
        }
        $DB->update_record('user', $update);

        // Update profile fields.
        $this->save_profile_field($user->id, 'department', $employee['department'] ?? '');
        $this->save_profile_field($user->id, 'position', $employee['position'] ?? '');

        $this->log_action('update', $user->id, $employee['employee_id'],
            'Updated fields: ' . implode(', ', array_keys($changes)));
        $this->stats['updated']++;

        return 'updated';
    }

    /**
     * Suspend users that are no longer present in the HR CSV.
     *
     * @param array $processedids List of employee IDs that were in the CSV.
     */
    private function suspend_removed_users(array $processedids): void {
        global $DB;

        if (empty($processedids)) {
            return;
        }

        // Get all users with an employee ID (idnumber) that were synced previously.
        list($insql, $params) = $DB->get_in_or_equal($processedids, SQL_PARAMS_NAMED, 'emp', false);
        $sql = "SELECT id, username, idnumber
                  FROM {user}
                 WHERE idnumber != ''
                   AND idnumber {$insql}
                   AND deleted = 0
                   AND suspended = 0
                   AND auth = 'manual'";

        $users = $DB->get_records_sql($sql, $params);

        foreach ($users as $user) {
            $DB->set_field('user', 'suspended', 1, ['id' => $user->id]);
            $DB->set_field('user', 'timemodified', time(), ['id' => $user->id]);

            $this->log_action('suspend', $user->id, $user->idnumber,
                "User suspended (not found in HR export): {$user->username}");
            $this->stats['suspended']++;
        }
    }

    /**
     * Save or update a custom user profile field.
     *
     * @param int $userid The user ID.
     * @param string $shortname The profile field shortname.
     * @param string $data The value to save.
     */
    private function save_profile_field(int $userid, string $shortname, string $data): void {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => $shortname]);
        if (!$field) {
            return; // Profile field doesn't exist.
        }

        $existing = $DB->get_record('user_info_data', [
            'userid'  => $userid,
            'fieldid' => $field->id,
        ]);

        if ($existing) {
            if ($existing->data !== $data) {
                $DB->set_field('user_info_data', 'data', $data, ['id' => $existing->id]);
            }
        } else {
            $DB->insert_record('user_info_data', (object) [
                'userid'  => $userid,
                'fieldid' => $field->id,
                'data'    => $data,
            ]);
        }
    }

    /**
     * Generate a unique username from employee data.
     *
     * @param array $employee Employee data.
     * @return string Generated username.
     */
    private function generate_username(array $employee): string {
        global $DB;

        // Format: first letter of first name + last name, lowercase, no accents.
        $firstname = strtolower(substr($employee['first_name'], 0, 1));
        $lastname  = strtolower($employee['last_name']);

        // Remove accents and special characters.
        $username = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $firstname . $lastname);
        $username = preg_replace('/[^a-z0-9]/', '', $username);

        // Ensure uniqueness.
        $base = $username;
        $counter = 1;
        while ($DB->record_exists('user', ['username' => $username])) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Log a sync action.
     *
     * @param string $action The action type.
     * @param int|null $userid The Moodle user ID.
     * @param string $employeeid The HR employee ID.
     * @param string $details Additional details.
     * @param string $status 'success' or 'error'.
     */
    private function log_action(string $action, ?int $userid, string $employeeid,
                                string $details, string $status = 'success'): void {
        global $DB;

        $DB->insert_record('local_nacex_hrsync_log', (object) [
            'action'      => $action,
            'userid'      => $userid,
            'employeeid'  => $employeeid,
            'details'     => $details,
            'status'      => $status,
            'timecreated' => time(),
        ]);
    }

    /**
     * Get sync statistics.
     *
     * @return array Current stats.
     */
    public function get_stats(): array {
        return $this->stats;
    }

    /**
     * Get recent sync logs.
     *
     * @param int $limit Number of logs to return.
     * @return array Log records.
     */
    public static function get_logs(int $limit = 50): array {
        global $DB;

        return $DB->get_records('local_nacex_hrsync_log', null, 'timecreated DESC', '*', 0, $limit);
    }
}
