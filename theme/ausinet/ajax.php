<?php 

// $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// define('AJAX_SCRIPT', true);

header("Content-Type: application/json");

require_once('../../config.php');

if ($_POST['grade_essay'] ) {

	require_once($CFG->dirroot.'/mod/quiz/locallib.php');
	require_once($CFG->dirroot.'/question/engine/lib.php');

	// print_r($_POST);
	// exit;
	error_reporting(E_ALL);
	ini_set('display_errors', true);


	$method = optional_param('method', null, PARAM_RAW);	
	$overallfeedback = optional_param('overallfeedback', null, PARAM_RAW);
	$notifystudent = optional_param('notifystudent', null, PARAM_RAW);

	// print_object($notifystudent);exit;

	$cmid = required_param('cmid', PARAM_INT);	
	$grade = optional_param('grade', null, PARAM_RAW);
	$attemptid = optional_param('attempt', null, PARAM_RAW);
	$slots = optional_param_array('slots', null, PARAM_RAW);

	if (! $cm = get_coursemodule_from_id('quiz', $cmid)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
	
	$PAGE->set_context(context_module::instance($cmid));
	/*$modinfo = get_fast_modinfo(); 
	$cm = $modinfo->cms[$cmid];*/
	$PAGE->set_course($course);
	$PAGE->set_cm($cm);
	$PAGE->set_pagelayout('incourse');
	$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);


	if ($method == 'gradeall') {
		$slots = $attemptobj->get_slots('all');		
	} else {
		$comment = optional_param_array('comment', null, PARAM_RAW);
	}

	// $attemptid = $newdata['attempt'];
	$studentid = $attemptobj->get_userid();
	$recipient = core_user::get_user($studentid);
	$a = new stdClass();
	$a->courseid = $attemptobj->get_courseid();
	$a->quizcmid = $attemptobj->get_cmid();
	$a->quizid = $attemptobj->get_quizid();
	$a->attemptid = $attemptobj->get_attemptid();	
	$a->quizname = $attemptobj->get_quiz()->name;
	$a->quizurl = $CFG->wwwroot . '/mod/quiz/view.php?id=' . $a->quizcmid;
	$a->coursename = format_string(get_course($a->courseid)->fullname);
	$a->quizreviewurl = $CFG->wwwroot.'/mod/quiz/review.php?attempt=' . $a->attemptid;

	if ( $method != 'gradeall' || $grade != '' ) { 

		foreach ($slots as $slot) {
			$_POST['slot'] = $slot;
			$_POST['slots'] = $slot;
			$_POST['sesskey'] = sesskey();
			$_POST['attempt'] = $attemptid;
			$_POST['submit'] = 'Save';

			$qa = $attemptobj->get_question_attempt($slot);
			$quba = question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
			
            $com = $qa->get_behaviour()->format_comment(null, null, null);
			$prevcomment = strip_tags($com);
			
			$maxmark = $qa->get_max_mark();
			$sequencecount = $qa->get_sequence_check_count();

			$markname = $qa->get_behaviour_field_name('mark');
			$maxmarkname = $qa->get_behaviour_field_name('maxmark');
			$minfraction = $qa->get_control_field_name('minfraction');
			$maxfraction = $qa->get_control_field_name('maxfraction');
			$sequencecheck = $qa->get_control_field_name('sequencecheck');
			$inputname = $qa->get_behaviour_field_name('comment');
			// $commentformat = $qa->get_behaviour_field_name('commentformat');
			$draftitemareainputname = $qa->get_behaviour_field_name('comment:itemid');

			$maxmark = $qa->get_max_mark();
			$_POST[$draftitemareainputname] = isset($comment['itemid']) ? $comment['itemid'] :file_get_unused_draft_itemid();
			$_POST[$inputname.'format'] = '1';
			$_POST[$inputname] = isset($comment['comment']) ? $comment['comment'] : '' ;
			$_POST[$maxmarkname] = $maxmark;
			$_POST[$maxfraction] = $qa->get_max_fraction();
		 	$_POST[$minfraction] = $qa->get_min_fraction();
			$_POST[$markname] = ($grade == 'pass') ? $maxmark : 0;
			$_POST[$sequencecheck] = $sequencecount;
			
			if ($method == 'gradeall') {
				$_POST[$inputname] = $prevcomment;
			}
			$_REQUEST = $_POST; // Set up the request global variable to get the file draft itemid.
					
			// exit;
			if (data_submitted() && question_engine::is_manual_grade_in_range($attemptobj->get_uniqueid(), $slot)) {

		       	$transaction = $DB->start_delegated_transaction();
		        $attemptobj->process_submitted_actions(time());
		        $transaction->allow_commit();

		        // Log this action.
		        $params = array(
		            'objectid' => $attemptobj->get_question_attempt($slot)->get_question()->id,
		            'courseid' => $attemptobj->get_courseid(),
		            'context' => context_module::instance($attemptobj->get_cmid()),
		            'other' => array(
		                'quizid' => $attemptobj->get_quizid(),
		                'attemptid' => $attemptobj->get_attemptid(),
		                'slot' => $slot
		            )
		        );
		        $event = \mod_quiz\event\question_manually_graded::create($params);
		        $event->trigger();

		    }
		}
	}

	if ($method == 'gradeall') {		
		$records = $DB->get_record_sql('SELECT * FROM {quiz_attempts} LIMIT 1');		


		if (!(property_exists($records, 'feedback'))) {		
			$DB->execute('ALTER TABLE `{quiz_attempts}` ADD `feedback` TEXT NULL ');
		}

		$DB->set_field('quiz_attempts', 'feedback', $overallfeedback, array('id' => $attemptobj->get_attemptid()));
		if ($notifystudent == "true") {
			send_graded_notification($recipient, $a);	
			// echo 'Send notifystudent';
		}
		$navpanel = '';		
	} else {
		$quizrenderer = $PAGE->get_renderer('mod_quiz');

		$page = $attemptobj->get_question_page($slot);
		$page = $attemptobj->force_page_number_into_range($page);
		// $attemptobj->set_currentpage($page);
		$navigationpanel = $attemptobj->get_navigation_panel($quizrenderer, 'quiz_review_nav_panel', $page);
		$navpanel = $navigationpanel->content;

		// print_r($navigationpanel);
	}

// exit;
    echo json_encode(['error' => false, 'navpanel' => $navpanel]);



	
    
    if (count($slots) > 1 ) {
     	\core\notification::success('Assessment marks updated');
    }
    // close_window(2, true);
    die;
} else if (optional_param('resend', null, PARAM_RAW) ) {
	$username = required_param('user', PARAM_RAW);
	$PAGE->set_context(context_system::instance());
	$user = core_user::get_user_by_username($username);
	if (send_confirmation_email($user)) {
		echo json_encode(['error' => false ]);
	} else {
		echo json_encode(['error' => true ]);		
	}
	exit;
} 


function send_graded_notification($recipient, $a) {

    // Add information about the recipient to $a.
    // Don't do idnumber. we want idnumber to be the submitter's idnumber.
    $a->username     = fullname($recipient);
    $a->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->courseid          = $a->courseid;
    $eventdata->component         = 'theme_ausinet';
    $eventdata->name              = 'quiz_graded';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = core_user::get_noreply_user();
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('emailquizgradesubject', 'theme_ausinet', $a);
    $eventdata->fullmessage       = get_string('emailquizgradebody', 'theme_ausinet', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = get_string('emailquizgradesmall', 'theme_ausinet', $a);
    $eventdata->contexturl        = $a->quizurl;
    $eventdata->contexturlname    = $a->quizname;
    $eventdata->customdata        = [
        'cmid' => $a->quizcmid,
        'instance' => $a->quizid,
        'attemptid' => $a->attemptid,
    ];

    // ... and send it.
   $output = message_send($eventdata);

   return $output;

}	

// print_r($json);
// echo "Asdfasdf";
// exit;

?>