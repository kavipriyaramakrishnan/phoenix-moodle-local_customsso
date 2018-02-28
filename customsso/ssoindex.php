<?php
/**
 * Phoenix SSO Webservice
 *
 *
 * @package   local_customsso
 * @copyright Pukunui
 * @author    Priya Ramakrishnan, Pukunui {@link http://pukunui.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require($CFG->dirroot.'/local/customsso/locallib.php');

// Parameters.
$hash      = required_param('hash', PARAM_RAW);
$timestamp = required_param('timestamp', PARAM_RAW);
$username  = required_param('username', PARAM_RAW);
$firstname = optional_param('firstname', '', PARAM_RAW);
$lastname  = optional_param('lastname', '', PARAM_RAW);
$email     = optional_param('email', '', PARAM_RAW);
$crsshortname = optional_param('course', 0, PARAM_INT);


// Timestamp Validation.
$lastrun = get_config('local_customsso', 'lasttimestamp');
if ($lastrun >= $timestamp) {
    echo get_string('error_invalidtimestamp', 'local_customsso');
    exit;
}

// Hash Validation.
local_customsso_hash_validation($hash, $timestamp, $username);

// Save the timestamp in the config.
set_config('lasttimestamp', $timestamp, 'local_customsso');

// Username Validation.
if ($user = local_customsso_user_exists($username, $firstname, $lastname, $email)) {
    // Log user in.
    complete_user_login($user);

    // Course shortname validation.
    if (!empty($crsshortname)) {
        local_customsso_course($crsshortname, $user);
    }
    redirect($CFG->wwwroot);
}
