# Bulk user action example

This plugin is an example of how to add bulk user actions to Moodle 4.4

Commit 1: Implement hook and add actions "Suspend" and "Unsuspend" that work from
both "Bulk user actions" and "Browse user list" pages and work without any Javascript.

Commit 2: Add web service and JS module to listen to form submit event, intercept it
and perform action using AJAX requests (only for "Browse user list" page).
