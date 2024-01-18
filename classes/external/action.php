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

namespace tool_bulkactiondemo\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use core_external\external_multiple_structure;
use moodle_exception;

/**
 * Implementation of web service tool_bulkactiondemo_action
 *
 * @package    tool_bulkactiondemo
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action extends external_api {

    /**
     * Describes the parameters for tool_bulkactiondemo_action
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(PARAM_ALPHANUMEXT, 'Action'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'user id'), 'Array of user ids'
            ),
        ]);
    }

    /**
     * Implementation of web service tool_bulkactiondemo_action
     *
     * @param string $action
     * @param array $userids
     */
    public static function execute($action, $userids) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        // Parameter validation.
        ['action' => $action, 'userids' => $userids] = self::validate_parameters(
            self::execute_parameters(),
            ['action' => $action, 'userids' => $userids]
        );

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:update', $context);

        list($in, $params) = $DB->get_in_or_equal($userids);
        $rs = $DB->get_recordset_select('user', "id $in", $params, '', 'id, suspended');
        foreach ($rs as $user) {
            if ($action === 'suspend') {
                if (!is_siteadmin($user) && $USER->id != $user->id && $user->suspended != 1) {
                    $user->suspended = 1;
                    // Force logout.
                    \core\session\manager::kill_user_sessions($user->id);
                    user_update_user($user, false);
                }
            } else if ($action === 'unsuspend') {
                if ($user->suspended != 0) {
                    $user->suspended = 0;
                    user_update_user($user, false);
                }
            } else {
                throw new moodle_exception(get_string('unrecognisedaction', 'tool_bulkactiondemo'));
            }
        }

        return [];
    }

    /**
     * Describe the return structure for tool_bulkactiondemo_action
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
