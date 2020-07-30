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

        // print_object($options);

        $tags = \core_tag_tag::get_tags_by_area_in_contexts('core_question', 'question', [ (object)['id' => $qa->get_question()->contextid ]]);
        $tagstrings = [];
        foreach ($tags as $tag) {
            $tagstrings[$tag->name] = 'tag-'.$tag->name;
        }
// print_object($options);

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
        // print_object($options);

        $questioninfo = $this->info($qa, $behaviouroutput, $qtoutput, $options, $number);
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
        /*$output .= html_writer::nonempty_tag('div',
                $this->add_part_heading(get_string('feedback', 'question'),
                    $this->outcome($qa, $behaviouroutput, $qtoutput, $options)),
                array('class' => 'outcome clearfix'));*/
        // $participant = $this->is_participant();
        // if (\question_display_options::VISIBLE) {
            /*$output .= html_writer::nonempty_tag('div',
                $this->add_part_heading(get_string('comments', 'question'),
                    $this->manual_comment($qa, $behaviouroutput, $qtoutput, $options, $participant)),
                array('class' => 'comment clearfix'));*/
        // }
     /*   $output .= html_writer::nonempty_tag('div',
                $this->response_history($qa, $behaviouroutput, $qtoutput, $options),
                array('class' => 'history clearfix'));*/

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
        // print_object($qa);exit;
        // echo 
        // moodle_url()
        // if (isset($options->manualcommentlink)) {
            // $attempt = $options->manualcommentlink->get_param('attempt');
        // }
        $attempt = optional_param('attempt', null, PARAM_INT);
        
        // $attempt = get_attemptid
        
        if ($options->manualcommentlink) {
            $url = new moodle_url('/mod/quiz/comment.php', array('slot' => $qa->get_slot(), 'attempt' => $attempt));
            $gradePass = ($qa->get_mark() == 1) ? 'checked' : '';
            $gradeFail = ($qa->get_mark() == 0) ? 'checked' : '';
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
                    </div>  
                                     
                </form>
            </div>';
            /*$link = $this->output->action_link($url, get_string('commentormark', 'question'), new popup_action('click', $url, 'commentquestion',                   array('width' => 600, 'height' => 800)));
            $output .= html_writer::tag('div', $link, array('class' => 'commentlink'));*/
        }
        return $output;
    }

   
}