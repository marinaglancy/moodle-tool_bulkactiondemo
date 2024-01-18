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
 * Perform bulk user action in AJAX request
 *
 * @module     tool_bulkactiondemo/actions
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {getStrings} from 'core/str';
import Ajax from 'core/ajax';
import * as reportEvents from 'core_reportbuilder/local/events';
import {dispatchEvent} from 'core/event_dispatcher';
import * as reportSelectors from 'core_reportbuilder/local/selectors';

const SELECTORS = {
    bulkActionForm: 'body#page-admin-user form#user-bulk-action-form',
};

/**
 * Initialise module
 */
export const init = () => {
    const form = document.querySelector(SELECTORS.bulkActionForm);

    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        let action = form.querySelector('select[name="action"]')?.value;
        if (!action.startsWith('tool_bulkactiondemo_')) {
            return;
        }

        e.preventDefault();

        action = action.replace('tool_bulkactiondemo_', '');
        const userlist = e.target.data.usernames.join(', ');
        const userids = e.target.data.userids;

        let key;
        if (action === 'suspend') {
            key = 'confirmsuspend';
        } else if (action === 'unsuspend') {
            key = 'confirmunsuspend';
        } else {
            return;
        }

        getStrings([
            {'key': 'confirm'},
            {'key': key, component: 'tool_bulkactiondemo', param: userlist},
            {'key': 'yes'},
            {'key': 'no'},
            {'key': 'changessaved', component: 'moodle'}
        ])
        .then(strings => {
            form.querySelector('select[name="action"]').value = '0';
            return Notification.confirm(strings[0], strings[1], strings[2], strings[3],
                () => performAction(action, userids, strings[4]));
        })
        .catch(Notification.exception);
    });
};

/**
 * Perform action
 *
 * @param {String} action
 * @param {Array} userids
 * @param {String} successMessage
 */
function performAction(action, userids, successMessage) {
    const reportElement = document.querySelector('[data-region="report-user-list-wrapper"] ' + reportSelectors.regions.report);

    Ajax.call([{
        methodname: 'tool_bulkactiondemo_action',
        args: {
            action: action,
            userids: userids,
        },
        done: () => {
            Notification.addNotification({message: successMessage, type: "success"});
            if (reportElement) {
                dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
            }
        },
        fail: (error) => Notification.exception(error),
    }]);
}