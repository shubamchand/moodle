<?php



class restrict_users {

	public $data = array();

	function __construct($formdata) {
		$elements = array('method' => '', 'type' => '', 'action' => '', 'cmid' => '', 'students' => [] );
		$this->data = array_merge( $elements, $formdata);
		$this->action = $this->data['action'];
		$this->table = ($this->data['type'] == 'section') ? 'course_sections' : 'course_modules';
		$this->handle_request();
		// print_r($this->data);
	}

	function handle_request() {

		if (!empty($this->data['method']) ) {

			if ($this->data['method'] == 1) {
				// Add retriction for allusers.
				$this->restrict_allusers();

			} else if ($this->data['method'] = 2) {
				// Add restriction for the selected users.				
				$this->restrict_selected_users();
			}
		}		
	}

	function restrict_selected_users() {
		
		if (!empty($this->data['students']) && is_array($this->data['students'])) {
			$students = $this->data['students'];	
			$availability = $this->get_availablity($this->table);
			foreach ($students as $key => $userid) {
				if ($this->action == 'lock') {
					$availability = $this->add_restriction_email($availability, $userid);
				} else if ($this->action == 'unlock') {
					$availability = $this->remove_restriction_email($availability, $userid);
				}				
			}
			$this->update_availability($availability, $this->table);
		}
	}

	function remove_restriction_email($availability, $userid) {


		$user = core_user::get_user($userid);
		if ($result = $this->is_restriction_added($availability, 'role', 'id', 3)) {

			$availability = $this->add_access_user($availability, $user->email);		

		} else {

			if ($result = $this->is_restriction_added($availability, 'profile', 'v', $user->email)) {

				$key = $result['key'];				
				if (isset($availability->c[$key])) {
					unset($availability->c[$key]);
					$availability->c = array_values($availability->c);					
				}
				if (isset($availability->showc[$key])) {
					unset($availability->showc[$key]);
					$availability->showc = array_values($availability->showc);
				}

			}
		}

		return $availability;
	}

	function add_access_user($availability, $email) {

		if ($result = $this->is_restriction_added($availability, 'profile', 'v', $email)) {
			$key = $result['key'];
			$condition = $availability->c[$key];
			$condition->op = 'isequalto';
			$condition->sf = 'email';

			$availability->op = '|';			
			$availability->c[$key] = $condition;
			$availability->showc[$key] = true;
		} else {
			$condition = new stdclass();
			$condition->type = 'profile';
			$condition->sf = 'email';			
			$condition->op = 'isequalto';
			$condition->v = $email;

			if (empty($availability)) {
				$availability = new stdclass();
			} 			
			$availability->op = '|';
			$availability->c[] = $condition;
			$availability->showc[] = true;
		}
		$availability->show = true;

		return $availability;
	}

	function add_restriction_email($availability, $userid) {
		$user = core_user::get_user($userid);
		if ($result = $this->is_restriction_added($availability, 'profile', 'v', $user->email)) {
			
			$key = $result['key'];
				
			$condition = $availability->c[$key];
			$condition->op = 'doesnotcontain';
			$condition->sf = 'email';
			$availability->c[$key] = $condition;
		} else {

			$condition = new stdclass();
			$condition->type = 'profile';
			$condition->sf = 'email';			
			$condition->op = 'doesnotcontain';
			$condition->v = $user->email;

			if (empty($availability)) {
				$availability = new stdclass();
			} 
			
			$availability->op = '&';
			$availability->c[] = $condition;
		}

		$showc = array_map(function() {
			  return true;
			}, range(0, count($availability->c) - 1 ));
		$availability->showc = $showc;

		return $availability;
	}

	function remove_availability($availability, $type='profile') {

		if (isset($availability->c) && !empty($availability->c)) {

			foreach ($availability->c as $key => $condition) {

				if ($condition->type == $type) {
					if (isset($availability->c[$key])) {
						unset($availability->c[$key]);
					}

					if (isset($availability->showc[$key])) {
						unset($availability->showc[$key]);
					}
				}
			}			
		}

		return $availability;
	}

	function restrict_allusers() {

		if ( !empty($this->data['type']) ) {
			
			$availability = $this->get_availablity($this->table);

			if ($this->data['action'] == 'lock') {
				$availability = $this->add_restriction_role($availability);
			} else if ($this->data['action'] == 'unlock') {
				$availability = $this->remove_restriction_role($availability);			
			} 

			$availability = $this->remove_availability($availability, 'profile');

			$result = $this->update_availability($availability, $this->table);

			return true;
		}
	}

	function remove_restriction_role($availability) {

		if ($result = $this->is_restriction_added($availability, 'role', 'id', 3)) {
			
			$key = $result['key'];

			if (isset($availability->c[$key])) {
				unset($availability->c[$key]);
				unset($availability->showc[$key]);
			}
		}
		
		$availability = $this->remove_availability($availability, 'role');

		return $availability;
	}

	function add_restriction_role($availability) {

		if ($result = $this->is_restriction_added($availability, 'role', 'id', 3)) {
			
			$key = $result['key'];

			if (isset($availability->showc[$key])) {
				$availability->showc[$key] = true;
			}

		} else {

			$condition = new stdclass();
			$condition->type = 'role';
			$condition->id = 3;

			if (empty($availability)) {
				$availability = new stdclass();
			} 
			
			$availability->op = '&';
			$availability->c[] = $condition;
			$availability->showc[] = true;			
		}

		return $availability;
	}

	function is_restriction_added($availability, $type, $valkey, $value) {

		if (!empty($availability) && isset($availability->c)) {
			
			foreach ($availability->c as $key => $condition) {

				if (isset($condition->c))  {
					return false;//$this->is_restriction_added($type, $value);
				} else if ($condition->type == $type && $condition->{$valkey} == $value	) {
					return ['key' => $key];
				}
			}
		}

		return false;
	}

	function get_availablity($table='course_modules') {
		global $DB;

		if (!empty($this->data['cmid'])) {

			$record = $DB->get_record($table, array('id' => $this->data['cmid'] ), '*', MUST_EXIST);

			$availability = json_decode($record->availability);
			if (isset($availability->c) && !is_array($availability->c) ) {
				$availability->c = [];
				if (isset($availability->showc) ) $availability->showc = [];

			}
			// print_r($availability);//exit;
			return $availability;
		}
	} 


	function update_availability($availability, $table="course_modules") {
		global $DB;

		if (!empty($this->data['cmid'])) {
			/*print_r($availability);			*/
			$record->id = $this->data['cmid'];
			$record->availability = json_encode($availability);
			$module = $DB->update_record($table, $record);
			// Purge data cache.
			// $DB->reset_caches();
			purge_other_caches();
		}
	}
}



class clear_auto_grades {

	function __construct($attemptid, $quizid, $cmid) {

		$this->attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
		$this->slots = $this->attemptobj->get_slots('all');	

		$this->datamapper = new question_engine_data_mapper();

		$this->process_slots();
		// exit;
	}

	public function process_slots() {
		global $DB, $PAGE;
		$quba = question_engine::load_questions_usage_by_activity($this->attemptobj->get_uniqueid());
		// echo $this->attemptobj->get_courseid();
		foreach ($this->slots as $slot) {
			$question = $quba->get_question($slot);
			if ((get_class($question) == 'qtype_multianswer_question') && $this->attemptobj->get_courseid() == 2) {
				$qa = $quba->get_question_attempt($slot);		
				$behave = $qa->get_last_step()->get_id();		
				$step = new stdclass();
				$step->id = $behave;
				$step->state = 'needsgrading';
				$step->fraction = null;
				$DB->update_record('question_attempt_steps', $step);
			}			
		}
		
	}
}

class course_completion_setup {

	function __construct($userid, $courseid) {

		$this->coursecontext = context_course::instance($courseid);
		$this->user = \core_user::get_user($userid);
		$this->course = get_course($courseid);
		$this->send_email_trainers();
		$this->send_email_student();
	}

	function send_email_trainers() {
		global $CFG;
		$trainers = $this->get_course_trainers($this->coursecontext);

		$student = html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$this->user->id, fullname($this->user));
		$course = html_writer::link($CFG->wwwroot.'/course/view.php?id='.$this->course->id, format_string($this->course->fullname));
		$bodyhtml = get_string('completionmail_teacher', 'theme_ausinet', ['student' => $student, 'course' => $course]);
		$subject = get_string('completionmailsubject_teacher', 'theme_ausinet');
		foreach($trainers as $key => $trainer) {
			// $userto = \core_user::get_user($userid);
			if (!empty($trainer)) {
				email_to_user($trainer, null, $subject, $bodyhtml, $bodyhtml);
			}
		}

	}

	function send_email_student() {
		global $CFG;
		// $trainers = $this->get_course_trainers($this->coursecontext);

		$student = html_writer::link($CFG->wwwroot.'/user/profile.php?id='.$this->user->id, fullname($this->user));
		$course = html_writer::link($CFG->wwwroot.'/course/view.php?id='.$this->course->id, format_string($this->course->fullname));
		$bodyhtml = get_string('completionmail_student', 'theme_ausinet', ['student' => $student, 'course' => $course]);
		$subject = get_string('completionmailsubject_student', 'theme_ausinet');
		
			// $userto = \core_user::get_user($userid);
		if (!empty($this->user)) {
			email_to_user($this->user, null, $subject, $bodyhtml, $bodyhtml);
		}
		

	}

	function get_course_trainers($coursecontext) {
		$users = get_enrolled_users($coursecontext, 'mod/quiz:emailnotifysubmission');
		return $users;
	}
}