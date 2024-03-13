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

namespace tool_bulkactiondemo\local\hooks;

use action_link;
use moodle_url;

/**
 * Hook callbacks for tool_bulkactiondemo
 *
 * @package    tool_bulkactiondemo
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extend_bulk_user_actions {

    /**
     * Extend bulk user actions menu
     *
     * @param \core_user\hook\extend_bulk_user_actions $hook
     */
    public static function callback(\core_user\hook\extend_bulk_user_actions $hook): void {
        global $PAGE;
        $category = 'Added by plugin';

        $syscontext = \context_system::instance();
        if (has_capability('moodle/user:update', $syscontext)) {
            $hook->add_action('tool_bulkactiondemo_suspend', new action_link(
                new moodle_url('/admin/tool/bulkactiondemo/action.php', ['action' => 'suspend']),
                get_string('suspend', 'tool_bulkactiondemo')),
                $category);
            $hook->add_action('tool_bulkactiondemo_unsuspend', new action_link(
                new moodle_url('/admin/tool/bulkactiondemo/action.php', ['action' => 'unsuspend']),
                get_string('unsuspend', 'tool_bulkactiondemo')),
                $category);
            $PAGE->requires->js_call_amd('tool_bulkactiondemo/actions', 'init');
        }
    }
}
