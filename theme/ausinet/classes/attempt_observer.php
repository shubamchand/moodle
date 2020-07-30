<?php 
namespace theme_ausinet;

class attempt_observer {

	public static function attempt_submitted($event) {
		global $CFG;

		require_once($CFG->dirroot.'/theme/ausinet/lib.php');
		$data['attemptid'] = $event->{'objectid'};
		$data['quizid'] = $event->{'other'}['quizid'];
		$data['cmid'] = $event->{'contextinstanceid'};
		theme_ausinet_remove_cloze_auto_grades($data);
		// exit;
	}

	public static function course_completed($event) {
		global $CFG;
		$courseid = $event->{'courseid'};
		$userid = $event->relateduserid;
		require_once($CFG->dirroot. '/theme/ausinet/locallib.php');
		$completionevent = new \course_completion_setup($userid, $courseid);

	}
}