<?php
/**
 * formNotificationHandler.php
 *
 * This file contains helper functions used to determine whether
 * a form submission has been modified and, if so, to trigger
 * notification logic for relevant recipients (e.g., administrators, users).
 *
 * Functions in this file are intended to support the Points Program
 * by detecting meaningful changes in form data and generating
 * appropriate email notifications.
 *
 */


/**
 * Check if two arrays differ in any way.
 *
 * @param array $newData  The new form submission
 * @param array $oldData  The previously stored data
 * @return bool           True if any values differ, false otherwise
 */
function hasFormChanged(array $newData, array $oldData): bool {
    return $newData != $oldData;
}
