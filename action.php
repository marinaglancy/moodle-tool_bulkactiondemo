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
 * Perform a bulk user action
 *
 * @package    tool_bulkactiondemo
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_login();

$url = new moodle_url('/admin/tool/bulkactiondemo/action.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
require_capability('moodle/user:update', context_system::instance());

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$return = new moodle_url($returnurl ?: '/admin/user/user_bulk.php');

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

if ($confirm && confirm_sesskey()) {
    $notifications = '';
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
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
    $rs->close();
    echo $OUTPUT->box_start('generalbox', 'notice');
    if (!empty($notifications)) {
        echo $notifications;
    } else {
        echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
    }
    $continue = new single_button($return, get_string('continue'), 'post');
    echo $OUTPUT->render($continue);
    echo $OUTPUT->box_end();
} else {
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    $formcontinue = new single_button(new moodle_url('/admin/tool/bulkactiondemo/action.php',
        ['action' => $action, 'confirm' => 1, 'returnurl' => $returnurl]), get_string('yes'));
    $formcancel = new single_button($return, get_string('no'), 'get');
    if ($action === 'suspend') {
        echo $OUTPUT->confirm(get_string('confirmsuspend', 'tool_bulkactiondemo', $usernames), $formcontinue, $formcancel);
    } else if ($action === 'unsuspend') {
        echo $OUTPUT->confirm(get_string('confirmunsuspend', 'tool_bulkactiondemo', $usernames), $formcontinue, $formcancel);
    } else if ($action === 'unlock') {
        throw new moodle_exception(get_string('unrecognisedaction', 'tool_bulkactiondemo'));
    }
}

echo $OUTPUT->footer();
