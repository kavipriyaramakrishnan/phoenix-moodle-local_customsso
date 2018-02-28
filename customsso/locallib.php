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
/**
 * Pre-Shared Key for generating the security hash
 */
define('CUSTOMSSO_PSK', 'Helloworld.');

/**
 * Generate the security hash
 *
 * @param string $timestamp
 * @param string $username
 * @return string  sha1 hash
 */
function local_customsso_get_hash($timestamp, $username) {
    return md5($username.$timestamp.CUSTOMSSO_PSK);
}

/**
 * Authenticate a request
 *
 * @param string $hash authentication token passed as part of the request
 * @param string $timestamp when the request is made
 * @param string $username for whom the request is made
 * @return text
 */
function local_customsso_hash_validation($hash, $timestamp, $username) {
    if (strcmp($hash, local_customsso_get_hash($timestamp, $username))) {
        echo get_string('error_invalidhash', 'local_customsso');
        exit;
    }
}

/**
 * Username Validation
 *
 * @param $username for whom the request is made
 * @return array/boolean
 */
function local_customsso_user_exists($username, $firstname, $lastname, $email, $password) {
   global $DB, $CFG;

   $userrec = $DB->get_record('user', array('username' => $username));
   if ($userrec) {
       // Update user fields and return.
       $extuser = new stdClass();
       $extuser->id           = $userrec->id;
       $extuser->username     = $username;
       if (!empty($firstname)) {
           $extuser->firstname = $firstname;
       }
       if (!empty($lastname)) {
           $extuser->lastname  = $lastname;  
       }
       if (!empty($email)) {
           $extuser->email = $email;
       }
       if (!empty($password)) {
           $extuser->password = hash_internal_user_password($password);
       }
       $extuser->timemodified = time();
       $userupd = $DB->update_record('user', $extuser);
       return $userrec;
   } else {
       // Validate user fields and create user.
       if ((!empty($email)) && (!empty($lastname)) && (!empty($firstname)) && (!empty($password))) {
           if ($useremail = $DB->get_record('user', array('email' => $email))) {
               // Duplicate Email.
               echo get_string('error_duplicateemails', 'local_customsso');
               exit;
           } else {
               // Create user.
               if (empty($password)) {
                   $password = generate_password();
               }
               $newuser = new stdClass();
               $newuser->username     = $username;
               $newuser->firstname    = $firstname;
               $newuser->lastname     = $lastname;
               $newuser->email        = $email;
               $newuser->password     = hash_internal_user_password($password);
               $newuser->mnethostid   = $CFG->mnet_localhost_id;
               $newuser->maildisplay  = $CFG->defaultpreference_maildisplay;
               $newuser->mailformat   = $CFG->defaultpreference_mailformat;
               $newuser->maildigest   = $CFG->defaultpreference_maildigest;
               $newuser->lang         = $CFG->lang;
               $newuser->timecreated  = time();
               $newuser->timemodified = $newuser->timecreated;
               $newuser->confirmed    = 1;
               // Insert the user into the database.
               $userid  = $DB->insert_record('user', $newuser);
               $userrec = $DB->get_record('user', array('id' => $userid));
               return $userrec;
           }
       } else {
           if (empty($firstname)) {
               echo get_string('error_firstnamemissing', 'local_customsso');
               exit;
           }
           if (empty($lastname)) {
               echo get_string('error_lastnamemissing', 'local_customsso');
               exit;
           }
           if (empty($email)) {
               echo get_string('error_emailmissing', 'local_customsso');
               exit;
           }
           if (empty($password)) {
               echo get_string('error_passwordmissing', 'local_customsso');
               exit;
           }
       }
   }
}

/**
 * Course validation and user enrolment
 *
 * @param $courseid id of the course to enrol the user
 * @param $user for whom the request is made
 * @return array/boolean
 */
function local_customsso_course($crsshortname, $user) {
    global $DB, $CFG;
    $course = $DB->get_record('course', array('shortname' => $crsshortname));
    if ($course) {
        // Is user enrolled in the course.
        if (is_enrolled(context_course::instance($course->id), $user->id)) {
            redirect($CFG->wwwroot."/course/view.php?id=".$course->id);
        } else {
            // Get the student role id.
            $studentrole = $DB->get_record('role', array('shortname'=>'student'));
            // Get the enrolment instances.
            $instance = new stdClass();
            $instance->id = $DB->get_field('enrol', 'id', array('enrol' => 'manual', 'courseid' => $course->id));
            $instance->courseid = $course->id;
            $instance->enrol = 'manual';
            $timestart = time();
            // Enrol user.
            $plugin = enrol_get_plugin('manual');
            $plugin->enrol_user($instance, $user->id, $studentrole->id, $timestart);
            redirect($CFG->wwwroot."/course/view.php?id=".$course->id);
        }
    } else {
        // Course does not exists.
        echo get_string('error_invalidshortname', 'local_customsso');
        exit;
    }
}
