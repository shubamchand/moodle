<?php 

namespace theme_ausinet;

use moodle_url;

class custom_blocks extends \theme_ausinet\config {

	function homebanner() {
		global $CFG;
		$config = $this->get_homebanner_config();		
		return $this->render_custom_blocks($config);
	}

	function aboutus() {
		$config = $this->get_aboutus_config();
		return $this->render_custom_blocks($config);
	}

	function counter() {
		global $USER;
		
		/*if (is_siteadmin()) {
			$data = $this->loadadmin_counter();
		} else {
		}*/
		$data = $this->loaduser_counter();
		// $data['detailsblock'] = $this->loadcounter_blocks();

		$data['toggle_usercounter'] = true;
		$block = new \block_contents();
		$block->title = '';
		$block->content = $this->render_custom_blocks($data);
		return $block;
	}

	function loaduser_counter() {
		global $USER, $CFG, $PAGE;
		$output = $PAGE->get_renderer('core', 'badges');
		if ( class_exists('\report_ausinet\user_report') ) {
			$userreport = new \report_ausinet\user_report($USER);
			$courses  = $userreport->user_enrolled_courses();
			$completed_courses =  $userreport->user_completed_courses();
			$data['courses'] = count($courses);		
			$data['coursesblock'] = $this->load_user_courses_block($courses);
			$data['completed'] = count( $completed_courses );
			$data['completed_courses'] = $this->load_user_courses_block($completed_courses);
			$badges = $userreport->user_badges();
			$data['badges'] = count($badges);
			if (!empty($badges)) {
				$data['badges_block'] = $output->print_badges_list($badges, $USER->id, true);
			}
			$data['points'] = $userreport->user_points();
			$data['points_block'] = $this->get_points_table();
			return $data;	
		}
	}

	function get_points_table() {
		global $PAGE;
		if (class_exists('\block_xp\di')) {
			$config = \block_xp\di::get('config');
			if ($config->get('context') == CONTEXT_COURSE) {
				$env = ['context' => \context_course::instance($PAGE->course)];				
			} else {
				$env = ['context' => \context_system::instance()];
			}
			$table = \block_xp\local\shortcode\handler::xpladder('xpladder', [], '', $env, '');
			return $table;
		}
	}

	function load_user_courses_block($courses) {
		global $PAGE, $CFG, $OUTPUT;
		require_once($CFG->dirroot.'/course/renderer.php');
		require_once($CFG->dirroot.'/course/lib.php');	
		$formattedcourses = [];
		$chelper = new \coursecat_helper();		
		foreach ($courses as $key => $course) {
			if (!is_array($course) && !is_object($course)) {
				$course = get_course($course);
			}
			$course = new \core_course_list_element($course);
			$formattedcourses[] = $this->available_coursebox($chelper, $course, '');
		}
		// print_object($courses);exit;
		return (!empty($courses)) ?  $OUTPUT->render_from_template('theme_ausinet/course', ['courses' => $formattedcourses]) : '';
		// return $content;
	}

	function get_courseimage($course) {
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


	function available_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
		global $CFG, $USER;
      

        $category = \core_course_category::get($course->category,IGNORE_MISSING);
        $viewurl = new \moodle_url('/course/view.php', array('id' => $course->id));
        $course_progress = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
        $data = array(
        	'id' => $course->id,
            'fullname' => $chelper->get_course_formatted_name($course),
            'shortname' => $course->shortname,
            'idnumber' => '',
            'summary' => $chelper->get_course_formatted_summary($course),
            'summaryformat' => 1,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
            'visible' => $course->visible,
            'fullnamedisplay' => $chelper->get_course_formatted_name($course),
            'viewurl' => $viewurl->out(),
            'courseimage' => $this->get_courseimage($course),
            'progress' => (int)$course_progress,
            'hasprogress' => 1,
            'isfavourite' => '',
            'hidden' => '',
            'showshortname' => '',
            'showcoursecategory' => 1,
            'coursecategory' => format_string($category->name)
        );
        // $data['ladder_url'] = $CFG->wwwroot.'/blocks/xp/index.php/ladder/'.$course->id;

        $data['ladder_url'] = $CFG->wwwroot.'/blocks/xp/index.php/ladder/'.$course->id;
        if (is_siteadmin()) {
        	$data['reporturl'] = $CFG->wwwroot.'/report/ausinet/?course='.$course->id;
        } else {
            $data['reporturl'] = $CFG->wwwroot.'/course/user.php?mode=grade&id='.$course->id.'&user='.$USER->id;
        }

        // print_object($data);
        // $data
        return (object)$data;
	}
	function loadadmin_counter() {
	}

	function render_custom_blocks($data) {
		global $OUTPUT;
		return $OUTPUT->render_from_template('theme_ausinet/custom_blocks', $data);
	}

	function add_block($block, $region='side-pre') {
		global $PAGE;
		if (method_exists($this, $block)) {
			$block = $this->{$block}();
			$PAGE->blocks->add_fake_block($block, $region);
		}
	}


	public static function courselisting_blocks($details) {
		global $DB;
		$this->render_custom_blocks($details);
	}

	
}