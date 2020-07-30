<?php 

namespace report_ausinet;

use report_ausinet\site_report;

require_once ($CFG->libdir.'/completionlib.php');

class user_report extends \report_ausinet\report {

	public $user;

	public $type;

	public $user_enrolled_courses;
	
	public function __construct($user, $type=null )	{
		
		parent::__construct();

		if ( is_numeric($user) ) {
			$user = $this->get_user($user);
		}
		
		$this->user = $user;

		$this->user_enrolled_courses = $this->user_enrolled_courses();
		
		// $this->userid = $userid;		
		
		$this->type = $type;

		$this->temp = 'user_report'; // Template file
	}	

	public function get_user($user) {
		$user = \core_user::get_user($user);
		return $user;
	}

	public function userinfo() {
		global $OUTPUT;
		if (!empty($this->user)) {
			$report['username'] = fullname($this->user);
			$report['email'] = $this->user->email;
			$report['timecreated'] = $this->user->timecreated;
			$report['firstaccess'] = $this->user->firstaccess;
			$report['lastlogin'] = $this->user->lastlogin;
			$report['roles'] = get_profile_roles(\context_user::instance($this->user->id));
			$userpicture = new \user_picture($this->user);
			$report['userimage'] = $OUTPUT->user_picture($this->user);
			$report['userinfo'] = 1;
			// print_object($report);
			return $this->render_block($this->temp, $report);

		}
	}

	function loaduser_counter() {
		global $USER, $CFG, $PAGE;
		if ($this->user) {
			$courses  = $this->user_enrolled_courses;
			$completed_courses =  $this->user_completed_courses();
			$data['courses'] = count($courses);				
			$data['completed'] = count( $completed_courses );			
			$badges = $this->user_badges();
			$data['badges'] = count($badges);			
			$data['points'] = $this->user_points();	
			$data['toggle_usercounter'] = 1;
			return $this->render_block($this->temp, $data);	
		}		
	}

	public function user_activities() {
		foreach ($this->ranges as $range) {
			$daterange = $this->get_date_range($range);
			$logins = $this->get_user_logins($daterange, $range);
			// $completion = $this->get_user_completion($daterange);
			// $result = ['logins' => $logins];
			$report[$range] = $logins;
		}

		return $this->line_chart('user_activities', $report, $this->temp, false);
	}

	public function get_user_logins($daterange, $range='') {
		global $DB;
		// echo $range;
		switch ($range) {
		 	case 'year':
		 		$groupby = 'MONTH(FROM_UNIXTIME(l.timecreated))';
		 		$select = "DATE_FORMAT(FROM_UNIXTIME(l.timecreated), '%d-%m-%y')";
		 		break;
		 	case 'month':
		 		$groupby = 'WEEK(FROM_UNIXTIME(l.timecreated))';
		 		$select = "DATE_FORMAT(FROM_UNIXTIME(l.timecreated), '%d-%m-%y')";
		 		break;
		 	case 'week':
		 		$groupby = 'DAY(FROM_UNIXTIME(l.timecreated))';		
		 		$select = "DATE_FORMAT(FROM_UNIXTIME(l.timecreated), '%d-%m-%y')"; 
		 		break;
		 	default:
		 		$groupby = 'HOUR(FROM_UNIXTIME(l.timecreated))';
		 		$select = "DATE_FORMAT(FROM_UNIXTIME(l.timecreated), '%H:%i')";

		 	break;
		 } 
			
	
		$sql = "SELECT id, COUNT(id) AS 'count', $select AS 'Login' FROM {logstore_standard_log} l WHERE l.action = 'loggedin' AND l.userid > 2 
		AND (timecreated BETWEEN :timestart AND :timeend )
		GROUP BY $groupby  ORDER BY `timecreated` ASC ";	
		

		$records = $DB->get_records_sql($sql, $daterange);		

		foreach ($records as $key => $value ) {
			$report[$value->login] = $value->count;
		}

		$data = $this->generate_line_chartdata($records, ['login' => 'count' ]);

		return $data;
	}


	public function user_completed_courses() {
		$courses  = $this->user_enrolled_courses();
		if (!empty($courses)) {
			$completed = [];
			foreach ($courses as $k => $course) {
				$completion = new \completion_info($course);
				if ($completion->is_enabled()) {
					if ($completion->is_course_complete($this->user->id)) {
						$completed[] = $course->id;
					}
				}
			}
			return $completed;
		}
		return [];
	}

	public function user_enrolled_courses() {
		if (!empty($this->user->id) ) {
			$courses = enrol_get_users_courses($this->user->id);	
			return $courses;			
		}
		return [];
	}

	public function user_badges() {
		global $CFG;
		require_once($CFG->dirroot.'/lib/badgeslib.php');
		return badges_get_user_badges($this->user->id);
	}

	public function user_points() {
		global $PAGE;
		if (class_exists('\block_xp\di')) {
			$xp_world = \block_xp\di::get('course_world_factory')->get_world($PAGE->course->id);
			$state = $xp_world->get_store()->get_state($this->user->id);
			$points = $state->get_xp();		
			$xp_rend = $PAGE->get_renderer('block_xp');
			return $xp_rend->xp($points); 
		} 
		return '';
	}

	public function user_courses_table() {
		$courses  = $this->user_enrolled_courses;
		if ($this->user) {
			$report = report_ausinet_load_user_courses_block($courses, $this->user->id);			
			// $merge = array_merge_recursive($report, $ue);
			// print_object($report);/

			$dataset = ['course image', 'course name', 'category', 'score', 'role', 'visits', 'group name', 'cohort name', 'enrol method', 'status', 'time enrolled', 'last course access', ''];

			$headers = array_map('ucfirst', $dataset);
			$table = $this->table_report($headers, $report, 'enrolled_courses_table');
			
			return $this->render_block($this->temp, ['enrolled_courses_table' => 1, 'table' => $table]);
		}
	}


	public function visits() {
		if ($this->user) {
			$sitereport = new site_report();
			return $sitereport->visits('', $this->user->id);
		}
	}


	public static function get_blocks($user='') {
		$blocks = [ 'userinfo', 'loaduser_counter', 'user_activities', 'visits', 'user_courses_table'  ];		
		$self = new \report_ausinet\user_report($user);
		foreach ($blocks as $key => $block) {
			$reportcontent['userreport'][] = array('content' => $self->{$block}() );
		}	
		return (isset($reportcontent)) ? $reportcontent : [];
	}

	public static function user_count() {
		global $DB;
		return $DB->count_records('user');
	}


}