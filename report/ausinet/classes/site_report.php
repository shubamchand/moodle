<?php 

namespace report_ausinet;


class site_report extends \report_ausinet\report {

	public $user_registration;

	public function __construct() {
		parent::__construct();

		$this->temp = 'site_report';
		$this->set_log_table();
	}

	public function user_registration($daterange) {
		global $DB;

		$sql = 'SELECT COUNT(*) AS count, auth FROM {user} WHERE confirmed = 1 AND timecreated BETWEEN :timestart AND :timeend GROUP BY auth';
		$records = $DB->get_records_sql($sql, $daterange);

		if ($records) {
			$users = [];
			foreach ($records as $key => $value) {
				$users[$value->auth] = $value->count;
			}

			// $this->user_registration = $users;

			return $users;
		}

	}
	
	public function totalcount($dataset) {
		if (!empty($dataset) && is_array($dataset)) {			
			$result = array_sum(array_values($dataset));
			return $result;
		}
	}

	public function stats_dataset($time='year') {
		$course_report = new \report_ausinet\course_report;		
		$daterange = $this->get_date_range($time);
		$enrolments = $course_report->enrollments($daterange, true);
		// print_object($enrolments);
		$course_completion = $course_report->course_completion($daterange);
		$modules_completion = $course_report->modules_completion($daterange);
		$user_registration = $this->user_registration($daterange);
		$this->user_registration[$time] = $user_registration;

		$report['enrolments'] = $this->totalcount($enrolments);
		$report['course_completion'] = $this->totalcount($course_completion);
		$report['modules_completion'] = $this->totalcount($modules_completion);
		$report['user_registration'] = $this->totalcount($user_registration);
		return $report;		
	}

	public function userregistration_method() {
		
		if (!isset($this->user_registration)) {
			$this->user_registration = $this->user_registration($daterange);
		}
		$usermethod = array_values($this->user_registration);
		$final=[];
		// print_object($usermethod);
		array_walk($usermethod, function($value, $key) use (&$final) {
			if (!empty($value)) {
				foreach ($value as $auth => $count) {
					// print_r($final);
					$final[$auth] = isset($final[$auth]) ? $final[$auth] + $count : $count; 
				}
			}
		});
		// print_object($final);
		return $this->compare_chart('userregistration_method', $final, $this->temp, false);
		
	}

	public function sitestats() {
		
		foreach ($this->ranges as $value) {
			$reports[$value] = $this->stats_dataset($value);
		}

		
		$reports['sitestats'] = 1;
		return $this->render_block($this->temp, $reports);
	}

	public function sitecounter() {
		global $DB;
		$report['users'] = \report_ausinet\user_report::user_count();
		$report['courses'] = \report_ausinet\course_report::course_count();
		$report['groups'] = $this->get_count('groups');
		$report['cohorts'] = $this->get_count('cohort');
		$report['modules'] = $this->get_count('modules');
		$report['instance'] = $this->get_count('course_modules');
		$report['toggle_sitecounter'] = 1;
		return $this->render_block($this->temp, $report);
	}


	public function visits($courseid='', $userid='', $return='') {

		$this->set_log_table();
		$visits = $this->getvisits($courseid, $userid, $return);
		$labels = array_map(function($value) {
			return $value;
		}, $visits['keys'] );
		unset($visits['keys']);
		$report = array('data' => $visits,  'labels' => ($labels)  );

		// print_object($report);
		$this->add_js_data('visits', $report );

		$counts = $this->sum_report_array($visits, 'y');

		return $this->render_block($this->temp, array('visits' => 1, 'counts' => $counts));
	}

	public function getvisits($courseid ='', $userid='', $return='') {
		
		$total_visits = $this->visits_record('', $courseid, $userid, $return);
		$course_visits = $this->visits_record('course', $courseid, $userid, $return);
		$module_visits = $this->visits_record('course_module', $courseid, $userid, $return);

		return ['course' => array_values($course_visits), 'module' => array_values($module_visits), 'keys' => array_keys($total_visits), 'total' => array_values($total_visits)];
	}

	public function set_log_table() {
		$logmanager = get_log_manager();
	    $readers = $logmanager->get_readers();
	  	$reader = reset($readers);
		$this->logtable = $reader->get_internal_log_table_name();
		$this->maxseconds = 150 * 3600 * 24;
	}

	public function visits_record($type='', $courseid ='', $userid='', $return='') {

		global $DB;
		$param = [];
		$param['timestart'] = time() - $this->maxseconds;
		$sql = 'SELECT id, target, DATE_FORMAT(FROM_UNIXTIME(`timecreated`), "%d-%b-%y") AS timecreated, COUNT(*) AS count  FROM {'.$this->logtable.'} WHERE timecreated >= :timestart AND action="viewed" ';
		if ($type) {
			$sql .= ' AND target=:type ';
			$param['type'] = $type;
		}

		if ($userid) {
			$sql .= ' AND userid=:userid ';
			$param['userid'] = $userid;
		}

		if ($courseid) {
			$sql .= ' AND courseid=:courseid ';
			$param['courseid'] = $courseid;
		}

		$sql .= '  GROUP BY DATE_FORMAT(FROM_UNIXTIME(`timecreated`), "%d %m %Y") ORDER BY `timecreated` ASC';

		$records = $DB->get_records_sql($sql, $param);


		$timecreated = array_column($records, 'timecreated');
		$count = array_column($records, 'count');

		if ($return == 'count') {
			return array_sum($count);
		}

		$visits = array_combine($timecreated, $count);
		uksort($visits, array($this, 'sort_dates'));

		array_walk($visits, function(&$value, $key) {
			$value = ['x' => $key, 'y' => $value];
		});

		return $visits;

	}



	public function users_list() {
		global $DB;
	}

	public function registration_chart() {
		global $DB;

		$sql = 'SELECT id, COUNT(*) AS count,  DATE_FORMAT( FROM_UNIXTIME(`timecreated`), "%d-%b-%y" )  AS timecreated, 
		SUM( CASE WHEN `confirmed`=1 then 1 else 0 end ) AS confirmed, 
		SUM( CASE WHEN (`confirmed`=0 and `deleted`=0 and `suspended`=0 ) then 1 else 0 end  ) AS unconfirmed,

		SUM( CASE WHEN `deleted`=1 then 1 else 0 end  ) AS deleted, 
		SUM( CASE WHEN `suspended`=1 then 1 else 0 end  ) AS suspended FROM {user} ';

		$sql .= ' WHERE timecreated > 0 ';

		$sql .= ' GROUP BY DATE_FORMAT( FROM_UNIXTIME(`timecreated`), "%Y %m %d" ) ';

		$records = $DB->get_records_sql($sql);


		$dataset = $this->barchart_data(array_values($records), ['confirmed', 'deleted', 'suspended']);
		$labels = array_column(array_values($records), 'timecreated');

		$counts = $this->sum_report_array($records, ['confirmed', 'deleted', 'suspended', 'count', 'unconfirmed'] );		
		
		$this->add_js_data('registration_chart', ['data' => $dataset, 'labels' => $labels ]);

		return $this->render_block($this->temp ,array('registration_chart' => true, 'counts' => $counts ));

		// $lineseries = $this->generate_line_chart($records, ['confirmed', 'deleted', 'suspended']);

		// print_object($report);
		// $this->add_js_data('registration_chart', $lineseries );		

		// return $this->render_block($this->temp, array('registration_chart' => 1, 'counts' => $lineseries['counts'] ));
		
	}

	public function unique_userlogin() {
		global $DB;

		$sql = "SELECT id, COUNT(DISTINCT l.userid) AS 'count', DATE_FORMAT(FROM_UNIXTIME(l.timecreated), '%M %Y') AS 'Login' FROM {logstore_standard_log} l WHERE l.action = 'loggedin' AND YEAR(FROM_UNIXTIME(l.timecreated)) > '2017' AND l.userid > 2 GROUP BY MONTH(FROM_UNIXTIME(l.timecreated))  ORDER BY `timecreated` ASC ";

		$records = $DB->get_records_sql($sql);		

		$data = $this->generate_line_chartdata($records, ['login' => 'count' ]);

		return $this->line_chart('unique_userlogin', $data, $this->temp, false);

	}




	public static function get_blocks() {
		$blocks = [ 'sitecounter', 'sitestats', 'userregistration_method', 'visits', 'users_list', 'registration_chart', 'unique_userlogin' ];		
		$self = new \report_ausinet\site_report;
		foreach ($blocks as $key => $block) {
			$reportcontent['sitereport'][] = array('content' => $self->{$block}() );
		}
		return (isset($reportcontent)) ? $reportcontent : [];
	}

	public function render_template($data) {
		global $OUTPUT;	
		return $OUTPUT->render_from_template('report_ausinet/blocks', $data);
	}
}