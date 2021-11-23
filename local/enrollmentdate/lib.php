<?php
// This file is part of Moodle - http://vidyamantra.com/
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
 * 
 *
 * 
 * @copyright  Padma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * course completed event handler
 *
 * @param \core\event\course_completed $event The event.
 * @return void
 * update enrollment end date on course completion event
 */
function local_enrollmentdate_enroll_enddate($event) {
	global $DB;
	$userid=$event->relateduserid;
	$courseid=$event->courseid;
	$id = $DB->get_field_sql("SELECT id FROM {enrol} where courseid=? AND enrol='manual'",array($courseid));
	//$sql="SELECT id FROM {user_enrolments} where enrolid=? AND userid=?";
	//$params = array($courseid);
	//$params[] = $userid;
	//$ueid=$DB->get_field_sql($sql,$params);
	$timeend=strtotime(date('Y-m-d h:i:s'));
	if($courseid!=16)
	{
	$DB->set_field('user_enrolments', 'timeend', $timeend, array('userid'=>$userid, 'enrolid'=>$id));
	}
}
