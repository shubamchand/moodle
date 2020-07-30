<?php 

namespace report_ausinet;


class course_report extends \report_ausinet\report {

	public $course;

	public $userid;

	public $type;
	
	public $assignmentlist = array();

	public $enrolments = array();

	// This is available for the single course list report.
	public $quizlist = array();

	public function __construct( $course=null, $userid=null, $type=null )	{

		parent::__construct();

		if (is_numeric($course)) {
			$course = new \core_course_list_element($course);
		}		
		$this->course = $course;
		$this->userid = $userid;
		$this->type = $type;
		$this->temp = 'course_report';

	}	

	public function course_user_progress() {
		$course_progress = \core_completion\progress::get_course_progress_percentage($this->course, $this->userid);
		return $course_progress;
	}

	public static function course_count() {
		global $DB;
		return $DB->count_records('course');
	}

	public function coursecounter() {
		global $DB;
		$modules = $this->count_modules();
		$users = $this->count_enrollments();
		$report['students'] = $users['student'];
		$report['teachers'] = $users['teacher'];
		$report['groups'] = $users['groups'];
		$report['activities'] = $modules['activities'];
		$report['resources'] = $modules['resources'];
		//$report['instance'] = $this->get_count('course_modules');
		$report['toggle_coursecounter'] = 1;
		return $this->render_block($this->temp, $report);
	}

	public function count_enrollments() {
		global $PAGE, $DB;

		if (!$this->course) return '';

		$enrols = enrol_get_course_users($this->course->id);
		$student = $teacher = 0;
		$coursecontext = \context_course::instance($this->course->id);
		foreach ($enrols as $key => $user) {
			$userrole = current(get_user_roles($coursecontext))->shortname;
			if ($userrole == 'student') {
				$student += 1; 
			} elseif ($userrole =='teacher' || $userrole == 'non-editing') {
				$teacher += 1;
			}
		}
		$role = $DB->get_record('role', array('shortname' => 'editingteacher'));		
		$teachers = get_role_users($role->id, $coursecontext);

		$role = $DB->get_record('role', array('shortname' => 'student'));	
		$student = get_role_users($role->id, $coursecontext);

		$groups = groups_get_all_groups($this->course->id);
		// print_object($groups);exit;
		$group = !($groups) ? [] : $groups;
		return ['teacher' => count($teacher), 'student' => count($student), 'groups' => count($group) ];
	}

	public function count_modules() {
		global $CFG;
		if (!$this->course) return '';

		require_once($CFG->dirroot.'/course/lib.php');
	    $list = get_array_of_activities($this->course->id);
	    $mods = array_unique(array_column($list, 'mod'));    
	    // $meta = get_module_metadata($this->course, array_flip($mods) );
	    // print_object($meta);exit;
	    $res = $act = 0;
	    foreach ($list as $key => $val) {
	    	$archtype = plugin_supports('mod', $val->mod, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);	    	
	        if (!$archtype) {
	            $res = $res + 1;
	        } else {
	            $act = $act + 1;
	        }
	    }	    
		return ['activities' => $act, 'resources' => $res];
	}

	public function get_course_completion_users($courseid) {
		$records = enrol_get_course_users($courseid);
		$context = \context_course::instance($courseid);
		$students = [];
		foreach ($records as $key => $user) {
			if (!empty(get_user_roles($context, $user->id)) ) {
				if ( current(get_user_roles($context, $user->id))->shortname == 'student' ) {
					$students[$user->id] = fullname($user);
				}
			}
		}
		return $students;
	}		


	public function course_completion($daterange, $courseid='', $count='user' ) {
		global $DB;

		$sql = 'SELECT userid, count(*) AS count FROM {course_completions} ';
		
		if ($daterange) {
			$sql .=	' WHERE (timecompleted BETWEEN :timestart AND :timeend ) ';
		} else {
			$sql .= ' WHERE timecompleted > 0 '; 
		}

		if ($courseid) {
			// $sql .= (empty($daterange)) ? ' WHERE ' : ' AND ';	
			$sql .=  ' AND course = :courseid ';
			$daterange['courseid'] = $courseid;
		}

		$sql .= ($count == 'user') ? ' GROUP BY userid ' : ' GROUP BY course ';
		$records = $DB->get_records_sql($sql, $daterange);

		if ($records) {
			$completions = [];
			foreach ($records as $key => $value) {
				$completions[$value->userid] = $value->count;
			}
			return $completions;
		}
		return [];
	}	


	public function course_inprogress($daterange=array(), $courseid='', $count='course') {
		global $DB;

		$sql = 'SELECT course, count(*) AS count FROM {course_completions} WHERE timecompleted IS NULL ';		
		$daterange = [];
		if ($courseid) {
			// $sql .= (empty($daterange)) ? ' WHERE ' : ' AND ';	
			$sql .=  ' AND course = :courseid ';
			$daterange['courseid'] = $courseid;
		}

		$sql .= ($count == 'user') ? ' GROUP BY userid ' : ' GROUP BY course ';
		// echo $sql;
		$records = $DB->get_records_sql($sql, $daterange);

		if ($records) {
			$inprogress = [];
			foreach ($records as $key => $value) {
				$inprogress[$value->course] = $value->count;
			}
		// print_object($inprogress);
			return $inprogress;
		}
		return [];
	}



	public function modules_completion($daterange) {
		global $DB;
		$sql = 'SELECT id, count(*) AS count, userid FROM {course_modules_completion} WHERE completionstate = 1 ';
		if ($daterange) {
			$sql .= ' AND timemodified BETWEEN :timestart AND :timeend ';
		}
		$sql .= ' GROUP BY userid ';


		$records = $DB->get_records_sql($sql, $daterange);

// print_object($records);exit;
		if ($records) {
			$modules = [];
			foreach ($records as $key => $value) {
				$modules[$value->userid] = $value->count;
			}

			return $modules;
		}
	}

	public function enrollments($daterange=array(), $onlydata=false) {
		global $DB;
		
		$sql = ' SELECT id, enrol, timecreated FROM {enrol} ';
		if ($daterange) {
			$sql .= ' WHERE timecreated BETWEEN :timestart AND :timeend ';
		}

		if (!empty($this->course)) {
			$sql .= ($daterange) ? ' AND ' : ' WHERE ';
			$sql .= ' courseid = :courseid ';
			$daterange['courseid'] = $this->course->id;
			$datelabels = $this->get_date_labels($this->course->timecreated);
		} else {
			$datelabels = $this->get_date_labels();
		}

		$records = $DB->get_records_sql($sql, $daterange); 
		if ($records) {
			$enrolments  = [];
			$report = [];
			foreach ( $records as $key => $value ) {
				if (in_array($value->enrol, array_keys($enrolments)) ) {
					$enrolments[$value->enrol] += 1;
				} else {
					$enrolments[$value->enrol] = 1;
				}
				$date = date('Y-m-d', $value->timecreated);

				if ( in_array($date, array_keys($report) ) ) {
					$report[$date] = $report[$date] + 1;
				} else {
					$report[$date] = 1;
				}

			}
		// print_object($report);exit;
			$this->enrolments = $enrolments;
			if ($onlydata) {
				return $this->enrolments;
			}

			// Set value 0 for the non defined values in the dataset, it should match the labels count to display the line without gap.
			$combine_labels = array_combine($datelabels, array_fill(0, count($datelabels), 0));
			foreach ($report as $key => $val) {
				$combine_labels[$key] = $val;
			}
		
			$convert_xy = $this->linechart_convert_xy($combine_labels);
			$reportdata[] = [				
				'data' => array_values($convert_xy), 
				'label' => 'Enrols', 
				'borderColor' => $this->randam_color(),
				'spanGaps'=> true,
				'borderWidth' => 1,
				'fill' => true,				
			];


			$data = [
				'labels' => array_values($datelabels), 
				'data' => $reportdata, 
				'counts' => count($report)
			];
			// print_object($data);

			return $this->line_chart('enrollments', $data, $this->temp, false);
		}

	}


	public function enrollment_percourse($type="") {
		global $USER, $PAGE;

		$courses = (!empty($this->course)) ? [$this->course->id => $this->course] : \core_course_category::get(0)->get_courses(array('recursive' => true ));
		foreach ($courses as $key => $course) {
			$completeusers = $this->course_completion([], $course->id, 'course');
			$enrolledusers = $this->get_course_completion_users($course->id);
			$report[$course->shortname] = ['enrols' => count($enrolledusers), 'completion' => count($completeusers), 'courseid' => $course->id];
		}

		$this->enrollment_percourse = $report;
		if ($type=='data') {
			return $report;
		}
		// print_object($report);
		$dataset[] = array( 'label' =>'Enrols', 'data' => array_column(array_values($report),'enrols'), 'backgroundColor' => 'blue' );
		$dataset[] = array('label' => 'Completion', 'data' => array_column(array_values($report),'completion'), 'backgroundColor' => 'red' );
		 
		$this->add_js_data('enrolment', ['data' => $dataset, 'labels' => array_keys($report) ]);
		return $this->render_block($this->temp ,array('enrollment_percourse' => true));

	}

	public function enrollment_method() {
		global $PAGE;
		$enrollments = $this->enrolments;
		foreach ($enrollments as $method => $count) {
			$dataset[] = $count;
		}
		$dataset = array_map(function($value) { return (int) $value; }, array_values($enrollments));
		$report = array('data' => $dataset,  'labels' => array_keys($enrollments) );
		$this->add_js_data('enrolment_method', $report );

		return $this->render_block($this->temp, array('enrollment_method' => 1));
		
	}

	public function course_completion_rate() {
		$enrol_percourse = $this->enrollment_percourse;
		if (empty($enrol_percourse)) {
			$enrol_percourse = $this->enrollment_percourse('data');
		}
		$inprogress = $this->course_inprogress();
		// print_object($enrol_percourse);
		foreach ($enrol_percourse as $coursename => $report) {
			// echo $coursename;
			$enrol_percourse[$coursename]['inprogress'] = isset($inprogress[$report['courseid']]) ? $inprogress[$report['courseid']] : 0;
		}

		$enrols = array_sum(array_column(array_values($enrol_percourse), 'enrols'));
		$completion = array_sum(array_column(array_values($enrol_percourse), 'completion'));
		$inprogress = array_sum(array_column(array_values($enrol_percourse), 'inprogress'));
		$not_started = $enrols - ($completion + $inprogress);

		// echo $enrols.'=>'.$completion.'=>'.$inprogress.'=>'.$not_started;

		$completionpercent =  $completion ; //( $completion / 100 ) * $enrols;
		$inprogresspercent = $inprogress ; //( $inprogress / 100 ) * $enrols;
		$not_startedpercent = $not_started ; //( $not_started / 100 ) * $enrols;

		$percent = ['Completion' => $completionpercent, 'In-Progress' => $inprogresspercent, 'Not-started' => $not_startedpercent];


		$dataset = array_map(function($value) { return (int) $value; }, array_values($percent));
		$report = array('data' => $dataset,  'labels' => array_keys($percent) );
		$this->add_js_data('completionrate', $report );

		return $this->render_block($this->temp, array('completionrate' => 1));
	}


	public function getquizattempts($daterange) {
		global $DB;
		$report = [];
		$sql = ' SELECT questionid, questionusageid, COUNT(*) AS count FROM {question_attempts} WHERE timemodified BETWEEN :timestart AND :timeend ';

		$sql .= ' GROUP BY questionid ';

		if ( !empty($this->course) ) {
			$sql = ' SELECT id, quiz, count(*) AS count FROM {quiz_attempts} 
			WHERE timemodified BETWEEN :timestart AND :timeend  AND quiz IN (select instance from {course_modules} WHERE course=:courseid AND module IN (select id from {modules} where name = "quiz") ) ';
			$daterange['courseid'] = $this->course->id;
			$records = $DB->get_records_sql($sql, $daterange);
			foreach ($records as $id => $record) {
				$report[$record->id] = $record->count;
				$this->quizlist[] = $record->quiz;
			}

		} else {

			$records = $DB->get_records_sql($sql, $daterange);
			foreach ($records as $id => $record) {
				$report[$record->questionusageid] = $record->count;

			}
		}

		return $report;
	}
	public function assignment_submissions($daterange) {
		global $DB;

		$sql = 'SELECT id, assignment, COUNT(*) AS count, userid FROM {assign_submission} ';

		if ($daterange) {
			$sql .= ' WHERE timemodified BETWEEN :timestart AND :timeend ';			
		}

		if ($this->course) {
			$sql .= ($daterange) ? ' AND ' : ' WHERE ';
			$sql .= ' assignment IN (select instance from {course_modules} WHERE course=:courseid AND module IN (select id from {modules} where name = "assign") ) ';			
			$daterange['courseid'] = $this->course->id;
		}

		$sql .= ' GROUP BY userid ';

		$records = $DB->get_records_sql($sql, $daterange);
		if ($records) {
			foreach ($records as $id => $record) {
				$report[$record->userid] = $record->count;
				$this->assignmentlist[] = $record->assignment;
			}
			return $report;
		}
		return [];
	}


	public function grades($daterange) {
		$assign_grades = $this->get_assign_grades($daterange);
		$quiz_grades = $this->get_quiz_grades($daterange);
		return ($assign_grades + $quiz_grades);
	}

	public function get_assign_grades($daterange) {
		global $DB;

		$sql = ' SELECT assignment, COUNT(*) AS count FROM {assign_grades} WHERE grader >= "1" AND ( timemodified BETWEEN :timestart AND :timeend ) ';

		if (!empty($this->course)) {
			$sql .= ' AND assignment IN (:assignments) ';

			$daterange['assignments'] = implode(',', $this->assignmentlist);
		}

		$sql .= ' GROUP BY assignment ';
		
		$records = $DB->get_records_sql($sql, $daterange);
		if ($records) {
			foreach ($records as $id => $record) {
				$report[$record->assignment] = $record->count;
			}
			return $report;
		}
		return [];
	}

	public function get_quiz_grades($daterange) {
		global $DB;

		$sql = ' SELECT quiz, COUNT(*) AS count FROM {quiz_grades} WHERE timemodified BETWEEN :timestart AND :timeend ';


		if (!empty($this->course)) {
			$sql .= ' AND quiz IN (:quizlist) ';

			$daterange['quizlist'] = implode(',', $this->quizlist);
		}

		$sql .= ' GROUP BY quiz ';

		
		$records = $DB->get_records_sql($sql, $daterange);
		
		return $this->get_count_records($records, 'quiz');
		
	}

	public function module_activities() {

		$report = [];
		foreach ($this->ranges as $range) {
			$daterange = $this->get_date_range($range);
			$quizattempts =  $this->getquizattempts($daterange) ;
			$submission = $this->assignment_submissions($daterange) ;
			$grades = $this->grades($daterange);
			$result = ['attempts' => $quizattempts, 'submission' => $submission, 'grades' => $grades ];
			$report[$range] = $this->sum_report_array($result);
		}			   	

		// print_object($report);
		return $this->compare_chart('module_activities', $report, $this->temp, true);
	}

	public function module_progress() {
		global $DB;

		$sql = 'SELECT cm.id, COUNT(*) AS count, cm.module, md.name FROM {course_modules} cm LEFT JOIN {modules} md ON cm.module = md.id ';
		
		$param = [];

		if (!empty($this->course)) {
			$sql .= ' WHERE cm.course = :courseid ';
			$param = ['courseid' => $this->course->id ];
		}

		$sql .= ' GROUP BY cm.module ';

		$records = $DB->get_records_sql($sql, $param);

		$data = $this->get_count_records($records, 'name');

		return $this->compare_chart('module_progress', $data, $this->temp, false);
	}

	public function assignment_table() {
		global $DB;

		

        $sql = 'SELECT a.id, a.name, cm.id AS cmid, 
        (SELECT count(*) FROM {course_modules_completion} mc WHERE mc.completionstate = 1 AND mc.coursemoduleid = cm.id ) AS completions, ag.grades AS graded, s.submission AS submitted, n.needgrade AS needgrade, 
        (SELECT count(*) FROM {user_enrolments} ue JOIN {enrol} e ON e.id= ue.enrolid WHERE e.courseid = a.course  ) AS participants     

        FROM {assign} a 
        LEFT JOIN {course_modules} cm ON cm.module = :assignmoduleid AND cm.instance = a.id
        LEFT JOIN ( SELECT g.assignment AS assignment, count(*) as grades, g.timemodified as timemodified, g.grade AS grade FROM {assign_grades} g GROUP BY g.assignment ) ag ON ag.assignment = a.id
        LEFT JOIN ( SELECT sm.assignment AS assignment, COUNT(*) AS submission, sm.status as status, sm.timemodified as timemodified FROM {assign_submission} sm GROUP BY assignment ) AS s ON s.assignment = a.id
        LEFT JOIN ( 
        	SELECT COUNT(*) AS needgrade, s.assignment as assignment FROM {assign_submission} s LEFT JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid AND g.attemptnumber = s.attemptnumber  WHERE s.latest = 1 AND s.timemodified IS NOT NULL AND s.status = "submitted" AND (s.timemodified >= g.timemodified OR g.timemodified IS NULL OR g.grade IS NULL  ) GROUP BY assignment
        	) AS n ON n.assignment = a.id

        ';

        $param = ['assignmoduleid' => 1];
		if (!empty($this->course)) {
			$sql .= ' WHERE cm.course = :courseid ';
			$param['courseid'] = $this->course->id;
			// $daterange['assignments'] = implode(',', $this->assignmentlist);
		}
        
		$records = $DB->get_records_sql($sql, $param);
		$dataset = ['name', 'participants', 'completions', 'submitted', 'graded', 'needgrade'];
		$report = [];
		foreach ($records as $k => $record) {
			foreach ($dataset as $field ) {
				if ($field == 'name') {
					$data[$field] = \html_writer::link(new \moodle_url('/mod/assign/view.php', array('id' => $record->cmid)) , format_string($record->name) );
				} else {
					$data[$field] = ($record->{$field}) ? $record->{$field} : '-';
				}
			}
			$report[] = $data;			
		}
		$dataset = ['name', 'users', 'finished', 'submitted', 'graded', 'needgrade'];
		$headers = array_map('ucfirst', $dataset);
		$table = $this->table_report( $headers, $report, 'assignment_table');

		return $this->render_block($this->temp, ['assignment_table' => 1, 'table' => $table]);
		
	}


	public function quiz_table() {
		global $DB;
		global $DB;
		$sql = 'SELECT qa.id, quiz.id, quiz.name, 
		(  CASE WHEN (quiz.grademethod = 1) then "Highest Grade"
			   	WHEN quiz.grademethod = 2 then "Average grade"
				WHEN quiz.grademethod = 3 then "First attempt"
				WHEN quiz.grademethod = 4 then "Last attempt"
			END
		) as grademethod,
		(select count(*) FROM {quiz_slots} WHERE quizid = quiz.id ) AS questions,
		 (qa.quiz) AS attempts FROM {quiz} AS quiz
		LEFT JOIN ( SELECT qa.id as id, qa.quiz as quiz, count(qa.quiz) AS attempts FROM {quiz_attempts} qa  GROUP BY qa.quiz ) AS qa ON qa.quiz = quiz.id	
		WHERE quiz.id IN (select id from {quiz} )
				
		';

		$param = [];
		if (!empty($this->course)) {
			$sql .= ' AND quiz.course = :courseid ';
			$param['courseid'] = $this->course->id;
			// $daterange['assignments'] = implode(',', $this->assignmentlist);
		}

		$records = $DB->get_records_sql($sql, $param);
		// print_object($records);
		// exit;
		$dataset = ['name', 'questions', 'attempts', 'grademethod'];

		
		$report = [];
		foreach ($records as $k => $record ) {
			foreach ($dataset as $field) {
				$data[$field] = ($record->{$field}) ? $record->{$field} : '-';
			}
			$report[] = $data;
		}
		// $dataset = ['name'];
		$headers = array_map('ucfirst', $dataset);
		$table = $this->table_report( $headers, $report, 'quiz_table');

		return $this->render_block($this->temp, ['quiz_table' => 1, 'table' => $table]);
	}

	public static function get_blocks($courseid='', $userid='', $userrole='') {

		$blocks = ['enrollments', 'enrollment_method', 'enrollment_percourse',  'course_completion_rate', 'module_activities', 'module_progress', 'assignment_table', 'quiz_table'];

		$course = [];
		if ($courseid) {
			$course = get_course($courseid);
			array_unshift($blocks, 'coursecounter');
		//	$blocks = ['coursecounter', 'enrollment_method', 'course_completion_rate'];
		}

		$self = new \report_ausinet\course_report($course, $userid);
		foreach ($blocks as $key => $block) {
			$reportcontent['coursereport'][] = array('content' => $self->{$block}() );
		}
		return (isset($reportcontent)) ? $reportcontent : [];
	}

}