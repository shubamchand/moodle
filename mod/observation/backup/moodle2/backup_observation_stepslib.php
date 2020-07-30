<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_observation
 * @copyright  2016 Mike Churchward (mike.churchward@poetgroup.org)
 * @author     Mike Churchward
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_observation_activity_task
 */

/**
 * Define the complete choice structure for backup, with file and id annotations
 */
class backup_observation_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        global $DB;
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $observation = new backup_nested_element('observation', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'qtype',
            'respondenttype', 'resp_eligible', 'resp_view', 'notifications', 'opendate',
            'closedate', 'resume', 'navigate', 'grade', 'sid', 'timemodified', 'completionsubmit', 'autonum'));

        $surveys = new backup_nested_element('surveys');

        $survey = new backup_nested_element('survey', array('id'), array(
            'name', 'courseid', 'realm', 'status', 'title', 'email', 'subtitle',
            'info', 'theme', 'thanks_page', 'thank_head', 'thank_body', 'feedbacksections',
            'feedbacknotes', 'feedbackscores', 'chart_type'));

        $questions = new backup_nested_element('questions');

        $question = new backup_nested_element('question', array('id'), array('surveyid', 'name', 'type_id', 'result_id',
            'length', 'precise', 'position', 'content', 'required', 'deleted', 'extradata'));

        $questchoices = new backup_nested_element('quest_choices');

        $questchoice = new backup_nested_element('quest_choice', array('id'), array('question_id', 'content', 'value'));

        $questdependencies = new backup_nested_element('quest_dependencies');

        $questdependency = new backup_nested_element('quest_dependency', array('id'), array(
            'dependquestionid', 'dependchoiceid', 'dependlogic', 'questionid', 'surveyid', 'dependandor'));

        $fbsections = new backup_nested_element('fb_sections');

        $fbsection = new backup_nested_element('fb_section', array('id'), array(
                'surveyid', 'section', 'scorecalculation', 'sectionlabel', 'sectionheading', 'sectionheadingformat'));

        $feedbacks = new backup_nested_element('feedbacks');

        $feedback = new backup_nested_element('feedback', array('id'), array(
                'sectionid', 'feedbacklabel', 'feedbacktext', 'feedbacktextformat', 'minscore', 'maxscore'));

        $responses = new backup_nested_element('responses');

        $response = new backup_nested_element('response', array('id'), array(
            'observationid', 'submitted', 'complete', 'grade', 'userid'));

        $responsebools = new backup_nested_element('response_bools');

        $responsebool = new backup_nested_element('response_bool', array('id'), array('response_id', 'question_id', 'choice_id'));

        $responsedates = new backup_nested_element('response_dates');

        $responsedate = new backup_nested_element('response_date', array('id'), array('response_id', 'question_id', 'response'));

        $responsemultiples = new backup_nested_element('response_multiples');

        $responsemultiple = new backup_nested_element('response_multiple', array('id'), array(
            'response_id', 'question_id', 'choice_id'));

        $responseothers = new backup_nested_element('response_others');

        $responseother = new backup_nested_element('response_other', array('id'), array(
            'response_id', 'question_id', 'choice_id', 'response'));

        $responseranks = new backup_nested_element('response_ranks');

        $responserank = new backup_nested_element('response_rank', array('id'), array(
            'response_id', 'question_id', 'choice_id', 'rankvalue'));

        $responsesingles = new backup_nested_element('response_singles');

        $responsesingle = new backup_nested_element('response_single', array('id'), array(
            'response_id', 'question_id', 'choice_id'));

        $responsetexts = new backup_nested_element('response_texts');

        $responsetext = new backup_nested_element('response_text', array('id'), array('response_id', 'question_id', 'response'));

        // Build the tree.
        $observation->add_child($surveys);
        $surveys->add_child($survey);

        $survey->add_child($questions);
        $questions->add_child($question);

        $question->add_child($questchoices);
        $questchoices->add_child($questchoice);

        $question->add_child($questdependencies);
        $questdependencies->add_child($questdependency);

        $survey->add_child($fbsections);
        $fbsections->add_child($fbsection);

        $fbsection->add_child($feedbacks);
        $feedbacks->add_child($feedback);

        $observation->add_child($responses);
        $responses->add_child($response);

        $response->add_child($responsebools);
        $responsebools->add_child($responsebool);

        $response->add_child($responsedates);
        $responsedates->add_child($responsedate);

        $response->add_child($responsemultiples);
        $responsemultiples->add_child($responsemultiple);

        $response->add_child($responseothers);
        $responseothers->add_child($responseother);

        $response->add_child($responseranks);
        $responseranks->add_child($responserank);

        $response->add_child($responsesingles);
        $responsesingles->add_child($responsesingle);

        $response->add_child($responsetexts);
        $responsetexts->add_child($responsetext);

        // Define sources.
        $observation->set_source_table('observation', array('id' => backup::VAR_ACTIVITYID));

        // Is current observation based on a public observation?
        $qid = $this->task->get_activityid();
        $currentobservation = $DB->get_record("observation", array ("id" => $qid));
        $currentsurvey = $DB->get_record("observation_survey", array ("id" => $currentobservation->sid));
        $haspublic = false;
        if ($currentsurvey->realm == 'public' && $currentsurvey->courseid != $currentobservation->course) {
            $haspublic = true;
        }

        // If current observation is based on a public one, do not include survey nor questions in backup.
        if (!$haspublic) {
            $survey->set_source_table('observation_survey', array('id' => '../../sid'));
            $question->set_source_table('observation_question', array('surveyid' => backup::VAR_PARENTID));
            $fbsection->set_source_table('observation_fb_sections', array('surveyid' => backup::VAR_PARENTID));
            $feedback->set_source_table('observation_feedback', array('sectionid' => backup::VAR_PARENTID));
            $questchoice->set_source_table('observation_quest_choice', array('question_id' => backup::VAR_PARENTID));
            $questdependency->set_source_table('observation_dependency', array('questionid' => backup::VAR_PARENTID));

            // All the rest of elements only happen if we are including user info.
            if ($userinfo) {
                $response->set_source_table('observation_response', array('observationid' => backup::VAR_PARENTID));
                $responsebool->set_source_table('observation_response_bool', array('response_id' => backup::VAR_PARENTID));
                $responsedate->set_source_table('observation_response_date', array('response_id' => backup::VAR_PARENTID));
                $responsemultiple->set_source_table('observation_resp_multiple', array('response_id' => backup::VAR_PARENTID));
                $responseother->set_source_table('observation_response_other', array('response_id' => backup::VAR_PARENTID));
                $responserank->set_source_table('observation_response_rank', array('response_id' => backup::VAR_PARENTID));
                $responsesingle->set_source_table('observation_resp_single', array('response_id' => backup::VAR_PARENTID));
                $responsetext->set_source_table('observation_response_text', array('response_id' => backup::VAR_PARENTID));
            }

            // Define id annotations.
            $response->annotate_ids('user', 'userid');
        }
        // Define file annotations
        $observation->annotate_files('mod_observation', 'intro', null); // This file area hasn't itemid.

        $survey->annotate_files('mod_observation', 'info', 'id'); // By survey->id
        $survey->annotate_files('mod_observation', 'thankbody', 'id'); // By survey->id.

        $question->annotate_files('mod_observation', 'question', 'id'); // By question->id.

        $fbsection->annotate_files('mod_observation', 'sectionheading', 'id'); // By feedback->id.
        $feedback->annotate_files('mod_observation', 'feedback', 'id'); // By feedback->id.

        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($observation);
    }
}