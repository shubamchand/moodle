<?php 

	require_once($CFG->dirroot.'/cohort/lib.php');
	require_once($CFG->dirroot.'/user/lib.php');


	function report_ausinet_load_user_courses_block($courses, $userid) {
		global $PAGE, $CFG, $OUTPUT;
		require_once($CFG->dirroot.'/course/renderer.php');
		require_once($CFG->dirroot.'/course/lib.php');	
		$formattedcourses = [];
		$chelper = new \coursecat_helper();		
		// $table = html_writer::table();
		foreach ($courses as $key => $course) {
			if (!is_array($course) && !is_object($course)) {
				$course = get_course($course);
			}
			$course = new \core_course_list_element($course);
			$formattedcourses[] = report_ausinet_available_coursebox($chelper, $course, $userid);
		}
		// print_object($formattedcourses);
		
		return $formattedcourses;		
	}

	function report_ausinet_get_courseimage($course) {
		global $CFG, $OUTPUT;
		if (!empty($course)) {
			// $course = \core_course_category::get_course($course->id);
            $data['imgurl']  = $OUTPUT->image_url('no-image', 'theme');
			foreach ($course->get_course_overviewfiles() as $file) {
	            $isimage = $file->is_valid_image();
	            if (!$isimage) {
	                $data['imgurl'] = $noimgurl;
                } else {
	               $data['imgurl'] = file_encode_url("$CFG->wwwroot/pluginfile.php",
	                '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
	                $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
	            }
	        }
	    	return $data['imgurl'];
	    }
	}

	function report_ausinet_available_coursebox(\coursecat_helper $chelper, $course, $userid = '') {
		global $CFG, $USER;

		$ue = report_ausinet_user_enrolment($course, $userid);
		$category = \core_course_category::get($course->category);
		$viewurl = new \moodle_url('/course/view.php', array('id' => $course->id));
		$course_progress = \core_completion\progress::get_course_progress_percentage($course, $userid);
		$site_report = new report_ausinet\site_report();
		$visits = $site_report->visits_record('', $course->id, $userid, 'count');
		$groupname = current(groups_get_user_groups($course->id, $userid));
		$cohorts = cohort_get_user_cohorts($userid);
		$cohortname = [];
		foreach ($cohorts as $key => $cohort) {
			$cohortname[] = $cohort->name;
		}
		// $enrollmethod = '';
		$lastcourseaccess = get_user_course_lastaccess($userid, $course->id);		
		$courseimage = report_ausinet_get_courseimage($course);
		// print_object($cohortname);
		$data = array(
		    'courseimage' => '<div class="courseimage"><img src="'.$courseimage.'" style="width:70px;height:70px;"> </div>',
		    'coursename' => $chelper->get_course_formatted_name($course),
		    'category' => format_string($category->name),
		    'progress' => (int)$course_progress,
		    'role' => get_user_roles_in_course($userid, $course->id),
		    'visits' => $visits,
		    'groupname' => groups_get_group_name(current($groupname)),
		    'cohortname' => implode(',', $cohortname),
		    'enrolmethod' => $ue['enrolmethod'],
		    'status' => $ue['status'],
		 //   'coursestartdate' => userdate($course->startdate),
		    'timeenrolled' => $ue['timeenrolled'],
		    'lastcourseaccess' => $lastcourseaccess,
		);
		// $data['ladder_url'] = $CFG->wwwroot.'/blocks/xp/index.php/ladder/'.$course->id;
		$data['reporturl'] = '<a href="'.$CFG->wwwroot.'/report/ausinet/?course='.$course->id.'"> <i class="fa fa-bar-chart"></i></a>';
		// print_object($data);
		// $data
		return (object)$data;
	}

	function report_ausinet_user_enrolment($course, $userid) {
		global $PAGE, $CFG;
		// echo $userid;exit;
		require_once($CFG->dirroot . '/enrol/locallib.php');
    	$manager = new \course_enrolment_manager($PAGE, $course);
    	$userenrolments = $manager->get_user_enrolments($userid);

    	foreach ($userenrolments as $ue) {
    		if (!empty($ue)) {
    			// print_object($ue);
    			$status = enrol_status($ue);
    			$courseid = $ue->enrolmentinstance->courseid;
    			$data = [
    				'timeenrolled' => userdate($ue->enrolmentinstance->timecreated),
    				'enrolmethod' => $ue->enrolmentinstance->enrol,
    				'role' => $ue->enrolmentinstance->roleid,
    				'status' => $status,
    			];
    		}
    	}
    	return $data;
	}

	function get_user_course_lastaccess($userid, $courseid) {
		global $DB;
		$lastaccess = $DB->get_record('user_lastaccess', ['userid' => $userid, 'courseid' => $courseid ]);
		return ($lastaccess) ? userdate($lastaccess->timeaccess) : '-';
	}

	function enrol_status($ue) {
		global $OUTPUT;
		$status = get_string('participationactive', 'enrol');
        $statusval = core_user\output\status_field::STATUS_ACTIVE;
        $timestart = $ue->timestart;
        $timeend = $ue->timeend;
        $timeenrolled = $ue->timecreated;
        switch ($ue->status) {
            case ENROL_USER_ACTIVE:
                $currentdate = new DateTime();
                $now = $currentdate->getTimestamp();
                $isexpired = $timestart > $now || ($timeend > 0 && $timeend < $now);
                $enrolmentdisabled = $ue->enrolmentinstance->status == ENROL_INSTANCE_DISABLED;
                // If user enrolment status has not yet started/already ended or the enrolment instance is disabled.
                if ($isexpired || $enrolmentdisabled) {
                    $status = get_string('participationnotcurrent', 'enrol');
                    $statusval = core_user\output\status_field::STATUS_NOT_CURRENT;
                }
                break;
            case ENROL_USER_SUSPENDED:
                $status = get_string('participationsuspended', 'enrol');
                $statusval = core_user\output\status_field::STATUS_SUSPENDED;
                break;
        }

        return $status;
	}