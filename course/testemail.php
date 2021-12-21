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
 * Toggles the manual completion flag for a particular activity or course completion
 * and the current user.
 *
 * If by student params: course=2
 * If by manager params: course=2&user=4&rolec=3&sesskey=ghfgsdf
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

require_once('../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot . '/theme/ausinet/locallib.php');


// Parameters
$courseid = optional_param('course', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);


// Check if we are marking a user complete via the completion report.
$user = optional_param('user', 0, PARAM_INT);
$rolec = optional_param('rolec', 0, PARAM_INT);

if (!$courseid && !$userid) {
    print_error('invalidarguments');
}

// Process self completion
if ($courseid) {
    $PAGE->set_url(new moodle_url('/course/testemail.php', array('course'=>$courseid)));

    // Check user is logged in
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($course->id);
    require_login($course);

    $completion = new completion_info($course);
    $trackeduser = ($user ? $user : $USER->id);

    if (!$completion->is_enabled()) {
        throw new moodle_exception('completionnotenabled', 'completion');
    } else if (!$completion->is_tracked_user($trackeduser)) {
        throw new moodle_exception('nottracked', 'completion');
    }
 	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);
    $csetup = new \course_completion_setup($userid, $courseid);
	echo 'You can check if email already been there or not in your inbox';
    exit;

   
// exit;

    // require_once($CFG->dirroot . '/enrol/locallib.php');
    // $manager = new \course_enrolment_manager($PAGE, $course);
    // $userenrolments = $manager->get_user_enrolments($userid );
    // $status = get_string('participationactive', 'enrol');
    // $statusval = '';
    // foreach ($userenrolments as $ue) {
    //     $timestart = $ue->timestart;
    //     $timeend = $ue->timeend;
    //     switch ($ue->status) {
    //         case ENROL_USER_ACTIVE:
    //             $statusval = 0;
    //             $currentdate = new DateTime();
    //             $now = $currentdate->getTimestamp();
    //             $isexpired = $timestart > $now || ($timeend > 0 && $timeend < $now);
    //             $enrolmentdisabled = $ue->enrolmentinstance->status == ENROL_INSTANCE_DISABLED;
    //             // If user enrolment status has not yet started/already ended or the enrolment instance is disabled.
    //             if ($isexpired || $enrolmentdisabled) {
    //                 // $status = get_string('participationnotcurrent', 'enrol');
    //                 $statusval = 1;
    //             }
    //             break;
    //         case ENROL_USER_SUSPENDED:
    //             // $status = get_string('participationsuspended', 'enrol');
    //             $statusval = 2;
    //             break;
    //     }
    // }
    var_dump($statusval);
    echo $statusval;
    echo 'I am here'; exit;

    
}
