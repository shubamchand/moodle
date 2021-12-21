<?php 

namespace theme_ausinet\output\core;

use html_writer;
use moodle_url;

class question_renderer extends \core_question_renderer {

    /**
     * Generate the display of a question in a particular state, and with certain
     * display options. Normally you do not call this method directly. Intsead
     * you call {@link question_usage_by_activity::render_question()} which will
     * call this method with appropriate arguments.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param string|null $number The question number to display. 'i' is a special
     *      value that gets displayed as Information. Null means no number is displayed.
     * @return string HTML representation of the question.
     */
    public function question(\question_attempt $qa, \qbehaviour_renderer $behaviouroutput,
            \qtype_renderer $qtoutput, \question_display_options $options, $number) {
        global $DB;
        $tags = \core_tag_tag::get_tags_by_area_in_contexts('core_question', 'question', [ (object)['id' => $qa->get_question()->contextid ]]);
        $tagstrings = [];
        foreach ($tags as $tag) {
            $tagstrings[$tag->name] = 'tag-'.$tag->name;
        }

        $output = '';
        $output .= html_writer::start_tag('div', array(
            'id' => $qa->get_outer_question_div_unique_id(),
            'class' => implode(' ', array(
                'que',
                $qa->get_question()->qtype->name(),
                $qa->get_behaviour_name(),
                $qa->get_state_class($options->correctness && $qa->has_marks()),
               // implode(' ', $tagstrings)
            ))
        ));
		
		$cmid = $options->editquestionparams['cmid'];
		//$attempt = required_param('attempt',PARAM_INT);
        $attempt = optional_param('attempt',null, PARAM_INT);
		if(empty($cmid)){
	        //$cmid= required_param('cmid',PARAM_INT);
            $cmid= optional_param('cmid',null, PARAM_INT);
		}
        $cm= get_coursemodule_from_id('quiz', $cmid);
        $siteenablehelp= get_config('quiz','enablehelp');

        $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
       
        $quiz_attempts = $DB->get_record('quiz_attempts', array('id' => $attempt));

        $questioninfo = $this->info($qa, $behaviouroutput, $qtoutput, $options, $number);
		$questioninfo .= html_writer::start_tag('div', array('class' => 'report-question'));
		$questioninfo .= '<a id="'.$number.'" class="reportquestion" title="Report this question" href="javascript:void(0);"> '.("Report").'</a>';
		$questioninfo .= html_writer::end_tag('div');
		
		//if($number == 1){
			$questioninfo .= html_writer::start_tag('div', array('class' => 'report-question-popup','style'=>'display:none'));
			$questioninfo .= '
				<textarea class="rq_msg" rows="5" placeholder="write your message here" required></textarea>
				<input type="hidden" name="cmid" value="' . $cmid . '">
				<input type="hidden" name="attempt" value="' . $attempt . '">
				<div style="margin-top:10px">
					<input type="submit" class="btn btn-primary" name="reportthis" value="Submit">
					<button type="button" class="btn btn-primary grade-close">Cancel</button>
				</div>
			';
			$questioninfo .= html_writer::end_tag('div');
		//}

		// Edited by Rudra
		$typeobj=$qa->get_question();
		if($qa->get_question()->qtype->name()=='essay'){
		    $info_text=  $typeobj->format_text(
		        $typeobj->graderinfo, $typeobj->graderinfo, $qa, 'qtype_essay',
		        'graderinfo', $typeobj->id);
		}else{
		    $info_text=  $typeobj->format_generalfeedback($qa);
		}

        
       
		if(strip_tags($info_text) != ""){
		    $attributes = array(
		        'alt' => 'More Information ' .$qa->get_question()->qtype->name(),
		        'class' => 'infoquestion',
		        'id' => 'info'.$number,
		    );

		    $questioninfo .= html_writer::start_tag('div', array('class' => 'info-question'));
		    $questioninfo .= html_writer::start_tag('a', array('class' => 'infoquestion1','id'=>$number),
		        'title="Question". $number href="javascript:void(0);" ');
		    if(($siteenablehelp == 1 && $quiz->enablehelp == 1 &&  $quiz_attempts->attempt >= $quiz->helpafterattempt) || ($cm ? has_capability('mod/quiz:manage',\context_module::instance($cm->id)) : TRUE)){
              $questioninfo .= $this->pix_icon('docs','More information '.$qa->get_question()->qtype->name(), '',$attributes);
		   
            }
            $questioninfo .= html_writer::end_tag('a');
		    $questioninfo .= html_writer::end_tag('div');

            if(($siteenablehelp == 1 && $quiz->enablehelp == 1 &&  $quiz_attempts->attempt >= $quiz->helpafterattempt) || ($cm ? has_capability('mod/quiz:manage',\context_module::instance($cm->id)) : TRUE)){
		        $questioninfo .= html_writer::start_tag('div', array('class' => 'info-question-popup','style'=>'display:none','id'=>'info_'.$number));
		        $questioninfo .= '
				<input type="hidden" name="cmid" value="' . $cmid . '">
                <input type="hidden" name="ques_num" value="' . $number . '">
				<input type="hidden" name="attempt" value="' . $attempt . '">
				<div style="margin-top:10px">
					'.$info_text.'
				</div>';
		        $questioninfo .= html_writer::end_tag('div');
            }
		}
		// End by Rudra

		/* Edited by Nirmal for compliance */
		if($qa->get_question()->qtype->name()=='essay'){
		    $compliance_text=  $typeobj->format_text(
		        $typeobj->compliance, $typeobj->compliance, $qa, 'qtype_essay',
		        'compliance', $typeobj->id);
		}else{
		    $compliance_text=  $typeobj->format_compliance($qa);
		}
       
		if(strip_tags($compliance_text) != ""){
		    $attributes = array(
		        'alt' => 'Compliance',
		        'class' => 'compliancequestion',
		        'id' => 'compliance'.$number,
		    );

		    $questioninfo .= html_writer::start_tag('div', array('class' => 'compliance-question'));
		    $questioninfo .= html_writer::start_tag('a', array('class' => 'compliancequestion compliancequestion1','id'=>$number, 'title' => 'Compliance '.$number,'href' => 'javascript:void(0);'),
		        'title="Question". $number href="javascript:void(0);" ');
		    if(($cm ? has_capability('mod/quiz:manage',\context_module::instance($cm->id)) : TRUE)){
            //   $questioninfo .= $this->pix_icon('docs','More information', '',$attributes);
            //   $questioninfo .= '<a id="'.$number.'" class="compliancequestion" title="Compliance" href="javascript:void(0);"> '.("Compliance").'</a>';
              $questioninfo .= 'Compliance';
		   
            }
            $questioninfo .= html_writer::end_tag('a');
		    $questioninfo .= html_writer::end_tag('div');

            if(($siteenablehelp == 1 && $quiz->enablehelp == 1 &&  $quiz_attempts->attempt >= $quiz->helpafterattempt) || ($cm ? has_capability('mod/quiz:manage',\context_module::instance($cm->id)) : TRUE)){
		        $questioninfo .= html_writer::start_tag('div', array('class' => 'compliance-question-popup','style'=>'display:none','id'=>'compliance_'.$number));
		        $questioninfo .= '
				<input type="hidden" name="cmid" value="' . $cmid . '">
                <input type="hidden" name="ques_num" value="' . $number . '">
				<input type="hidden" name="attempt" value="' . $attempt . '">
				<div style="margin-top:10px">
					'.$compliance_text.'
				</div>';
		        $questioninfo .= html_writer::end_tag('div');
            }
		}
		/* End edited by Nirmal for compliance */



        
        $questioninfo .= html_writer::start_tag('div', array('class' => 'lms-comments '));
        if (!$this->is_participant()) {
            $questioninfo .= $this->manual_comment($qa, $behaviouroutput, $qtoutput, $options);
        } 
        
        $comment = '';
        if ($qa->has_manual_comment()) {
            $com = $qa->get_behaviour()->format_comment(null, null, $options->context);
            if ( strip_tags($com) != '')
                $comment = get_string('commentx', 'question', $qa->get_behaviour()->format_comment(null, null, $options->context));
        }
        
        $questioninfo .= html_writer::end_tag('div');
        $output .= html_writer::tag('div',
                $questioninfo,
                array('class' => 'info'));

        $output .= html_writer::start_tag('div', array('class' => 'content'));

        $output .= html_writer::tag('div',
                $this->add_part_heading($qtoutput->formulation_heading(),
                    $this->formulation($qa, $behaviouroutput, $qtoutput, $options)).
                    $comment,
                array('class' => 'formulation clearfix'));

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
		
        return $output;
    }

    public function is_participant() {
        global $PAGE, $USER;
        $attemptid = optional_param('attempt', null, PARAM_RAW);
        if ($attemptid) {
            $attemptobj = quiz_create_attempt_handling_errors($attemptid, $PAGE->cm->id);
            return $attemptobj->is_own_attempt();
        }
    }

    protected function manual_comment(\question_attempt $qa, \qbehaviour_renderer $behaviouroutput,
            \qtype_renderer $qtoutput, \question_display_options $options, $participant=false) { 
        if ($participant) {
            return parent::manual_comment($qa, $behaviouroutput, $qtoutput, $options);
        }       
        $fields = $behaviouroutput->manual_comment_fields($qa, $options);
        return $this->manual_comment_view($qa, $options, $fields);
    } 

    public function manual_comment_view(\question_attempt $qa, \question_display_options $options, $fields) {
        global $PAGE;
        $output = '';

        $attempt = optional_param('attempt', null, PARAM_INT);
        
        if ($options->manualcommentlink) {
            $url = new moodle_url('/mod/quiz/comment.php', array('slot' => $qa->get_slot(), 'attempt' => $attempt));
            $gradePass = ($qa->get_mark() == 1) ? 'checked' : '';
            $gradeFail = ($qa->get_mark() == 0) ? 'checked' : '';
            $question = $qa->get_question();
            $output .= '<div class="ausinet-grade" >
                <form action="'.$url.'" class="ausinet-essay-grade" data-cmid="'.$PAGE->cm->id.'">
                    <div class="comment-grade-parent right">
						<div class="comment-question">
                            <a href="javascript:void(0);"> '.("Make comment").'</a>
                        </div>
                        <div class="grade-section">
                            <input id="gradepass" type="radio" value="pass" name="grade" '.$gradePass.' required> <label for="gradepass" > Pass </label>
                            <input id="gradefail" type="radio" value="fail" name="grade" '.$gradeFail.' required> <label for="gradefail">Incorrect</label>
                            
                            <button type="submit" id="id_submitbutton" class="btn btn-primary"> Save </a>
                            
                        </div>
                    </div>
                    <input type="hidden" value="'.$attempt.'" name="attempt">
                    <input type="hidden" value="'.$qa->get_slot().'" name="slot">
                    <div class="comment-fields hide">'.$fields.'
						<div class="graderinfo"><h6>'. get_string('graderinfo','qtype_essay').'</h6>'.
						html_writer::nonempty_tag('div', $question->format_text(
							$question->graderinfo, $question->graderinfo, $qa, 'qtype_essay',
							'graderinfo', $question->id), array('class' => 'graderinfocontent')).'
						</div>					
                    </div>
                </form>
            </div>';
        }
        return $output;
    } 
}