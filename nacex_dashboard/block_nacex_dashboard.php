<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    block_nacex_dashboard
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

/**
 * Block nacex_dashboard - Employee training dashboard block.
 *
 * Shows a personalized panel with quick access to assigned courses,
 * training progress, and department-specific information.
 */
class block_nacex_dashboard extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_nacex_dashboard');
    }

    /**
     * Allow multiple instances on a page.
     *
     * @return bool
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Where can this block be displayed.
     * Moodle 5.1 uses 'all' => false with specific overrides.
     *
     * @return array
     */
    public function applicable_formats(): array {
        return [
            'all'            => false,
            'my'             => true,
            'my-index'       => true,
            'site-index'     => true,
            'course-view'    => false,
            'mod'            => false,
        ];
    }

    /**
     * Get the block content.
     *
     * @return stdClass The block content.
     */
    public function get_content(): \stdClass {
        global $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new \stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // Get user's department from profile field.
        $department = $this->get_user_department($USER->id);

        // Get user's tracking data if the tracking plugin is installed.
        $tracking = $this->get_user_tracking($USER->id);

        // Build block content.
        $html = '';

        // Welcome message.
        $html .= '<div class="nacex-dashboard-welcome mb-3">';
        $html .= '<p class="fw-bold">' .
            get_string('welcome', 'block_nacex_dashboard', $USER->firstname) . '</p>';
        if (!empty($department)) {
            $html .= '<p class="text-muted small">' .
                get_string('your_department', 'block_nacex_dashboard', $department) . '</p>';
        }
        $html .= '</div>';

        // Training progress summary.
        if (!empty($tracking)) {
            $total = count($tracking);
            $completed = 0;
            $pending = 0;
            $overdue = 0;

            foreach ($tracking as $t) {
                switch ($t->status) {
                    case 'completed':
                        $completed++;
                        break;
                    case 'overdue':
                        $overdue++;
                        break;
                    default:
                        $pending++;
                        break;
                }
            }

            $pct = $total > 0 ? round(($completed / $total) * 100) : 0;

            $html .= '<div class="nacex-dashboard-progress mb-3">';
            $html .= '<h6>' . get_string('training_progress', 'block_nacex_dashboard') . '</h6>';

            // Progress bar.
            $barclass = $pct >= 75 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
            $html .= '<div class="progress mb-2" style="height: 20px;">';
            $html .= '<div class="progress-bar ' . $barclass . '" role="progressbar" ' .
                'style="width: ' . $pct . '%">' . $pct . '%</div>';
            $html .= '</div>';

            // Stats (Bootstrap 5 classes).
            $html .= '<div class="row text-center">';
            $html .= '<div class="col-4"><span class="badge bg-success d-block">' .
                $completed . '</span><small>' .
                get_string('completed', 'block_nacex_dashboard') . '</small></div>';
            $html .= '<div class="col-4"><span class="badge bg-warning text-dark d-block">' .
                $pending . '</span><small>' .
                get_string('pending', 'block_nacex_dashboard') . '</small></div>';
            $html .= '<div class="col-4"><span class="badge bg-danger d-block">' .
                $overdue . '</span><small>' .
                get_string('overdue', 'block_nacex_dashboard') . '</small></div>';
            $html .= '</div>';
            $html .= '</div>';

            // Urgent courses (overdue or near deadline).
            $urgentcourses = $this->get_urgent_courses($tracking);
            if (!empty($urgentcourses)) {
                $html .= '<div class="nacex-dashboard-urgent mb-3">';
                $html .= '<h6 class="text-danger">' .
                    get_string('urgent_courses', 'block_nacex_dashboard') . '</h6>';
                $html .= '<ul class="list-unstyled">';
                foreach ($urgentcourses as $course) {
                    $url = new moodle_url('/course/view.php', ['id' => $course->courseid]);
                    $html .= '<li class="mb-1">';
                    $html .= '<a href="' . $url . '">' . format_string($course->coursename) . '</a>';
                    if (!empty($course->duedate)) {
                        $html .= ' <small class="text-danger">(' .
                            userdate($course->duedate, '%d/%m/%Y') . ')</small>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul></div>';
            }
        }

        // Quick links.
        $html .= '<div class="nacex-dashboard-links">';
        $html .= '<h6>' . get_string('quick_links', 'block_nacex_dashboard') . '</h6>';

        $links = [
            ['url' => '/my/courses.php', 'label' => get_string('my_courses', 'block_nacex_dashboard'), 'icon' => 'i/course'],
        ];

        // Add tracking link if plugin is installed.
        if ($DB->get_manager()->table_exists('local_nacex_tracking')) {
            $links[] = [
                'url'   => '/local/nacex_tracking/my_progress.php',
                'label' => get_string('my_progress', 'block_nacex_dashboard'),
                'icon'  => 'i/completion-auto-enabled',
            ];
        }

        $links[] = ['url' => '/user/profile.php', 'label' => get_string('my_profile', 'block_nacex_dashboard'), 'icon' => 'i/user'];
        $links[] = ['url' => '/calendar/view.php', 'label' => get_string('calendar'), 'icon' => 'i/calendar'];

        foreach ($links as $link) {
            $url = new moodle_url($link['url']);
            $icon = $OUTPUT->pix_icon($link['icon'], '', 'moodle', ['class' => 'me-1']);
            $html .= '<a href="' . $url . '" class="btn btn-outline-secondary btn-sm d-block mb-1 text-start">' .
                $icon . $link['label'] . '</a>';
        }

        $html .= '</div>';

        $this->content->text = $html;

        // Footer with Nacex branding.
        $this->content->footer = '<small class="text-muted">' .
            get_string('powered_by', 'block_nacex_dashboard') . '</small>';

        return $this->content;
    }

    /**
     * Get user's department from custom profile field.
     *
     * @param int $userid User ID.
     * @return string Department name or empty string.
     */
    private function get_user_department(int $userid): string {
        global $DB;

        $sql = "SELECT uid.data
                  FROM {user_info_data} uid
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uid.userid = :userid
                   AND uif.shortname = 'department'";

        $result = $DB->get_field_sql($sql, ['userid' => $userid]);
        return $result ?: '';
    }

    /**
     * Get user's tracking records.
     *
     * @param int $userid User ID.
     * @return array Tracking records or empty array.
     */
    private function get_user_tracking(int $userid): array {
        global $DB;

        // Check if tracking table exists.
        if (!$DB->get_manager()->table_exists('local_nacex_tracking')) {
            return [];
        }

        $sql = "SELECT t.*, c.fullname AS coursename, m.duedate, m.department
                  FROM {local_nacex_tracking} t
                  JOIN {course} c ON c.id = t.courseid
                  JOIN {local_nacex_mandatory} m ON m.id = t.mandatoryid
                 WHERE t.userid = :userid";

        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Get urgent courses (overdue or due within 7 days).
     *
     * @param array $tracking All tracking records.
     * @return array Urgent course records.
     */
    private function get_urgent_courses(array $tracking): array {
        $now = time();
        $weekahead = $now + (7 * DAYSECS);
        $urgent = [];

        foreach ($tracking as $t) {
            if ($t->status === 'completed') {
                continue;
            }
            if ($t->status === 'overdue' || (!empty($t->duedate) && $t->duedate <= $weekahead)) {
                $urgent[] = $t;
            }
        }

        // Sort by due date ascending.
        usort($urgent, function ($a, $b) {
            return ($a->duedate ?? PHP_INT_MAX) <=> ($b->duedate ?? PHP_INT_MAX);
        });

        return array_slice($urgent, 0, 5);
    }

    /**
     * This block has a settings form.
     *
     * @return bool
     */
    public function has_config(): bool {
        return false;
    }
}