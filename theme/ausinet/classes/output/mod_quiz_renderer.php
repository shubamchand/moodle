<?php

namespace theme_ausinet\output;

use single_button;
use moodle_url;
use quiz_attempt;
use confirm_action;
use html_writer;

class mod_quiz_renderer extends \mod_quiz_renderer {
    /*
     * Creates any controls a the page should have.
     *
     * @param quiz_attempt $attemptobj
     */
    public function summary_page_controls($attemptobj) {
        global $PAGE, $USER;

        $output = '';
        $nosubmit = false;
        // print_object($attemptobj);
        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            $attemptstate =  $attemptobj->get_question_state($slot);            
            $attemptstatus = $attemptobj->get_question_state_class($slot, false);
/*if ($USER->id == '4') {
            echo  $attemptstatus.'<br>';
        }*/
            if ($attemptobj->is_real_question($slot) && ( $attemptstatus == 'notyetanswered' || $attemptstate == 'invalid') ) {
                $nosubmit = true;
            }
        }
        // Return to place button.
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button = new single_button(
                    new moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())),
                    get_string('returnattempt', 'quiz'));
            $output .= $this->container($this->container($this->render($button),
                    'controls'), 'submitbtns btattempt mdl-align');
        }

        // Finish attempt button.
        $options = array(
            'attempt' => $attemptobj->get_attemptid(),
            'finishattempt' => 1,
            'timeup' => 0,
            'slots' => '',
            'cmid' => $attemptobj->get_cmid(),
            'sesskey' => sesskey(),
        );

        $button = new single_button(
                new moodle_url($attemptobj->processattempt_url(), $options),
                get_string('submitallandfinish', 'quiz'));
        $button->id = 'responseform';
		
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button->add_action(new confirm_action(get_string('confirmclose', 'quiz'), null,
                    get_string('submitallandfinish', 'quiz')));
        }

        $duedate = $attemptobj->get_due_date();
        $output .= '<div class="submit-actions">';
        /*$message = '        
            <div class="quiz-declaration" >
                <input type="checkbox" name="declaration" id="declaration" value="true" >
                <label for="declaration"> I declare that this is my own work </label>
            </div>';
        $PAGE->requires->js_amd_inline('
            require(["jquery", "core/modal_factory"], function($, modal) {
				var copybtn = $(".submit-actions").html();
				$(".quizsummaryofattempt").before(copybtn);
				
			})' );*/
        
       $message = '';
        if ($attemptobj->get_state() == quiz_attempt::OVERDUE) {
            $message = get_string('overduemustbesubmittedby', 'quiz', userdate($duedate));

        } else if ($duedate) {
            $message = get_string('mustbesubmittedby', 'quiz', userdate($duedate));
        }

        $output .= $this->countdown_timer($attemptobj, time());        

        if ($nosubmit) {
            $button = \html_writer::tag('button', get_string('submitallandfinish', 'quiz'), array('href' => 'javascript:void(0)', 'id' => 'notansweredmodal'));

            $output .= $this->container($message . $this->container(
                $button, 'controls'), 'submitbtns mdl-align notallanswered');
            $PAGE->requires->js_amd_inline('
                require(["jquery", "core/modal_factory"], function($, modal) {
                    var trigger = $("#notansweredmodal");
                    modal.create({
                        title: "Not Answered",
                        body: "<p> Please answer all the questions. </p>",
                        footer: "",
                    }, trigger);
                    /*$(".notallanswered button[type=submit]").click(function() {
                        alert();
                        return false;
                    })*/
                })
            ');
        } else {
             $output .= $this->container($message . $this->container(
                $this->render($button), 'controls'), 'submitbtns mdl-align');
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * Output the page information
     *
     * @param object $quiz the quiz settings.
     * @param object $cm the course_module object.
     * @param object $context the quiz context.
     * @param array $messages any access messages that should be described.
     * @return string HTML to output.
     */
    public function view_information($quiz, $cm, $context, $messages) {
        global $CFG;

        $output = '';

        // Print quiz name and description.
        $output .= $this->heading(format_string($quiz->name));
        $output .= $this->quiz_intro($quiz, $cm);

        // Output any access messages.
        if ($messages) {
            // $output .= $this->box($this->access_messages($messages), 'quizinfo');
        }

        // Show number of attempts summary to those who can view reports.
        if (has_capability('mod/quiz:viewreports', $context)) {
            if ($strattemptnum = $this->quiz_attempt_summary_link_to_reports($quiz, $cm,
                    $context)) {
                $output .= html_writer::tag('div', $strattemptnum,
                        array('class' => 'quizattemptcounts'));
            }
        }
		
        return $output;
    }

    public function question_marking_field($attemptid) {
        global $PAGE;

        $output = html_writer::tag('a', 'Finish Marking', array('id' => 'trigger_finish_popup', 'href' => 'javascript:void(0);' ));
        $output .= html_writer::start_tag('div', array('class' => 'finish-mark-popup hide' ) );

        $output .= html_writer::start_tag('form', array('class' => 'ausinet-grade-all'));

        $output .= html_writer::start_tag('div', array('class' => 'body-part' ) );

        $output .= '<p class="confirm-text"> Are you sure! you want to finish marking </p>';
        $output .= '<div class="form-label form-check-inline">Select Grade :</div>';
        $output .= '<div class="form-check form-check-inline">';
        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'gradeall', 'value'=>'pass', 'id' => 'grade-pass', 'class'=>'form-check-input'));
        $output .= html_writer::tag('label', 'Pass', array('for' => 'grade-pass', 'class' => 'form-check-label' ));
        $output .= '</div>';

        $output .= '<div class="form-check form-check-inline">';        
        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'gradeall', 'value'=>'fail', 'id' => 'grade-fail', 'class'=>'form-check-input' ));
        $output .= html_writer::tag('label', 'Fail', array('for' => 'grade-fail', 'class' => 'form-check-label'));
        $output .= '</div>';

        // Notify students.
        $output .= '<div class="form-label notify-parent">';
        $output .= html_writer::tag('label', 'Notify Student: ', array('for' => 'notify-student', 'class' => 'form-check-label' ));
        $output .= '<div class="form-check form-check-inline">';
        $output .= html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'notifystudent', 'value'=>'yes', 'class'=>'form-check-input', 'id' => 'notify-student', 'checked' => 'checked'));
        $output .= html_writer::tag('label', 'Send notification mail to student', array('for' => 'notify-student', 'class' => 'form-check-label' ));        
        $output .= '</div></div>';

        // Overall feedback.
        $output .= '<div class="form-inline overall-feedback">
                <label> Feedback: </label>
                <textarea name="overallfeedback" rows="4" ></textarea>
            </div>
        ';

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'attempt', 'value' => $attemptid ) );
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'cmid', 'value' => $PAGE->cm->id ) );

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'method', 'value' => 'gradeall' ) );
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'footer-part' ) );
        $output .= html_writer::tag('button', 'Update Marks', array('class' => 'btn btn-primary update-all-grade', 'type' => 'submit') );

        $output .= html_writer::tag('button', 'Cancel', array('class' => 'btn btn-secondary grade-close', 'type' => 'button') );
        $output .= html_writer::end_tag('div');


        $output .= html_writer::end_tag('form');

        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function getattempt_feedback($attemptid) {
        global $DB;
       
        $field = $DB->get_record('quiz_attempts', array('id' => $attemptid)) ;
           
        if ( $field ) {
            return isset($field->feedback) ? $field->feedback : '';
        }
        return '';
    }

    public function review_page(\quiz_attempt $attemptobj, $slots, $page, $showall,
                                $lastpage, \mod_quiz_display_options $displayoptions,
                                $summarydata) {
        
        $attemptid = optional_param('attempt', null, PARAM_INT);

        $feedback = $this->getattempt_feedback($attemptid);        
        if ($feedback) { 
            // $summarydata['comment']['content'] = $feedback;
            $summarydata['feedback'] = array(
                'title'   => get_string('feedback', 'quiz'),
                'content' => $feedback,
            );
        }

        if ($attemptobj->has_capability('mod/quiz:viewreports') && ($attemptobj->get_state() == quiz_attempt::FINISHED)) {
            $summarydata['gradeall'] = array(
                'title' => 'Marking',
                'content' => $this->question_marking_field($attemptid)
            );
        }
		
        $output = '';
        $output .= $this->header();
        $output .= $this->review_summary_table($summarydata, $page);
		
        // Select all graded.
        if ($attemptobj->has_capability('mod/quiz:viewreports') && ($attemptobj->get_state() == quiz_attempt::FINISHED)) {
            $output .= $this->selectallgrade();
        }
        $output .= $this->review_form($page, $showall, $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj);

        $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
        $output .= $this->footer();
        return $output;
    }


    public function selectallgrade() {
        $output = html_writer::start_tag('div', array('id' => 'selectallgrade'));
        $output .= '
        <div class="selectall-label"> Select All: </div>
        <div class="grade-section">
            <input id="allpass" type="radio" value="pass" name="grade" > <label for="allpass"> Pass </label>
            <input id="allfail" type="radio" value="fail" name="grade"> <label for="allfail"> Incorrect </label>
        </div>';
        $output .= html_writer::end_tag('div');
        return $output;
    }


    /**
     * Outputs the navigation block panel
     *
     * @param quiz_nav_panel_base $panel instance of quiz_nav_panel_base
     */
    public function navigation_panel(\quiz_nav_panel_base $panel) {
        global $PAGE, $USER;
        $output = '';
        $userpicture = $panel->user_picture();
        if ($userpicture) {
            $fullname = fullname($userpicture->user);
            if ($userpicture->size === true) {
                $fullname = html_writer::div($fullname);
            }
            $output .= html_writer::tag('div', $this->render($userpicture) . $fullname,
                    array('id' => 'user-picture', 'class' => 'clearfix'));
        }
        $output .= $panel->render_before_button_bits($this);

        $bcc = $panel->get_button_container_class();
        $output .= html_writer::start_tag('div', array('class' => "qn_buttons clearfix $bcc"));

       
        $attemptid = optional_param('attempt', null, PARAM_RAW);
        if ($attemptid) {
            $attemptobj = quiz_create_attempt_handling_errors($attemptid, $PAGE->cm->id);

        }
        foreach ($panel->get_question_buttons() as $button) {
            // print_object($button);
            $button->comment = '';
            if ($attemptobj && isset($button->id)) {
                $slot = str_replace('quiznavbutton', '', $button->id);
                $qa = $attemptobj->get_question_attempt($slot); 
                $com = $qa->get_behaviour()->format_comment(null, null, null);
                $com = strip_tags($com);
                $button->comment = (!empty($com) && $com != ' ') ? 'commented' : '';
            } 
            // print_r($list);
            $output .= $this->render($button);
        }
        $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
                array('class' => 'othernav'));

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
                quiz_get_js_module());

        return $output;
    }


    /**
     * Display a quiz navigation button.
     *
     * @param quiz_nav_question_button $button
     * @return string HTML fragment.
     */
    protected function render_quiz_nav_question_button(\quiz_nav_question_button $button, $attemptobj=null) {

        // print_object($button);

        $classes = array('qnbutton', $button->stateclass, $button->navmethod, 'btn', 'btn-secondary', $button->comment) ;
        $extrainfo = array();

        if ($button->currentpage) {
            $classes[] = 'thispage';
            $extrainfo[] = get_string('onthispage', 'quiz');
        }

        // Flagged?
        if ($button->flagged) {
            $classes[] = 'flagged';
            $flaglabel = get_string('flagged', 'question');
        } else {
            $flaglabel = '';
        }
        $extrainfo[] = html_writer::tag('span', $flaglabel, array('class' => 'flagstate'));

        if (is_numeric($button->number)) {
            $qnostring = 'questionnonav';
        } else {
            $qnostring = 'questionnonavinfo';
        }

        $a = new \stdClass();
        $a->number = $button->number;
        $a->attributes = implode(' ', $extrainfo);
        $tagcontents = html_writer::tag('span', '', array('class' => 'thispageholder')) .
                        html_writer::tag('span', '', array('class' => 'trafficlight')) .
                        get_string($qnostring, 'quiz', $a);
        $tagattributes = array('class' => implode(' ', $classes), 'id' => $button->id,
                                  'title' => $button->statestring, 'data-quiz-page' => $button->page);

        if ($button->url) {
            return html_writer::link($button->url, $tagcontents, $tagattributes);
        } else {
            return html_writer::tag('span', $tagcontents, $tagattributes);
        }
    }
}