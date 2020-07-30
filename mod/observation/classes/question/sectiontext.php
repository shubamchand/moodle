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
 * This file contains the parent class for sectiontext question types.
 *
 * @author Mike Churchward
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questiontypes
 */

namespace mod_observation\question;
defined('MOODLE_INTERNAL') || die();

class sectiontext extends question {

    /**
     * @return object|string
     */
    protected function responseclass() {
        return '';
    }

    /**
     * @return string
     */
    public function helpname() {
        return 'sectiontext';
    }

    /**
     * Return true if this question has been marked as required.
     * @return boolean
     */
    public function required() {
        return true;
    }

    /**
     * True if question type supports feedback options. False by default.
     */
    public function supports_feedback() {
        return true;
    }

    /**
     * True if question type supports feedback scores and weights. Same as supports_feedback() by default.
     */
    public function supports_feedback_scores() {
        return false;
    }

    /**
     * True if the question supports feedback and has valid settings for feedback. Override if the default logic is not enough.
     */
    public function valid_feedback() {
        return true;
    }

    /**
     * Override and return a form template if provided. Output of question_survey_display is iterpreted based on this.
     * @return boolean | string
     */
    public function question_template() {
        return 'mod_observation/question_sectionfb';
    }

    /**
     * Return the context tags for the check question template.
     * @param \mod_observation\responsetype\response\response $response
     * @param array $dependants Array of all questions/choices depending on this question.
     * @param boolean $blankobservation
     * @return object The check question context tags.
     *
     */
    protected function question_survey_display($response, $descendantsdata, $blankobservation=false) {
        global $DB, $CFG, $PAGE;
        require_once($CFG->dirroot.'/mod/observation/observation.class.php');

        // If !isset then normal behavior as sectiontext question.
        if (!isset($response->observationid)) {
            return '';
        }

        $fbsections = $DB->get_records('observation_fb_sections', ['surveyid' => $this->surveyid]);
        $filteredsections = [];

        // In which section(s) is this question?
        foreach ($fbsections as $key => $fbsection) {
            $scorecalculation = unserialize($fbsection->scorecalculation);
            if (array_key_exists($this->id, $scorecalculation)) {
                array_push($filteredsections, $fbsection->section);
            }
        }

        // If empty then normal behavior as sectiontext question.
        if (empty($filteredsections)) {
            return '';
        }

        list($cm, $course, $observation) = observation_get_standard_page_items(null, $response->observationid);
        $observation = new \observation(0, $observation, $course, $cm);
        $observation->add_renderer($PAGE->get_renderer('mod_observation'));
        $observation->add_page(new \mod_observation\output\reportpage());

        $compare = false;
        $allresponses = false;
        $currentgroupid = 0;
        $isgroupmember = false;
        $rid = (isset($response->id) && !empty($response->id)) ? $response->id : 0;
        $resps = [$rid => null];
        // For $filteredsections -> get the feedback messages only for this sections!
        $feedbackmessages = $observation->response_analysis($rid, $resps, $compare, $isgroupmember, $allresponses,
            $currentgroupid, $filteredsections);

        // Output.
        $questiontags = new \stdClass();
        $questiontags->qelements = new \stdClass();
        $choice = new \stdClass();

        $choice->fb = implode($feedbackmessages);

        $questiontags->qelements->choice = $choice;
        return $questiontags;
    }

    /**
     * @param object $data
     * @return string
     */
    protected function response_survey_display($data) {
        return '';
    }

    /**
     * Check question's form data for complete response.
     *
     * @param object $responsedata The data entered into the response.
     * @return boolean
     */
    public function response_complete($responsedata) {
        return true;
    }

    /**
     * @param \MoodleQuickForm $mform
     * @return \MoodleQuickForm
     */
    protected function form_required(\MoodleQuickForm $mform) {
        return $mform;
    }

    /**
     * @param \MoodleQuickForm $mform
     * @param string $helpname
     * @return \MoodleQuickForm|void
     */
    protected function form_length(\MoodleQuickForm $mform, $helpname = '') {
        return question::form_length_hidden($mform);
    }

    /**
     * @param \MoodleQuickForm $mform
     * @param string $helpname
     * @return \MoodleQuickForm|void
     */
    protected function form_precise(\MoodleQuickForm $mform, $helpname = '') {
        return question::form_precise_hidden($mform);
    }
}