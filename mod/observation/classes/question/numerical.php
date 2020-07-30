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
 * This file contains the parent class for numeric question types.
 *
 * @author Mike Churchward
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questiontypes
 */

namespace mod_observation\question;
defined('MOODLE_INTERNAL') || die();

class numerical extends question {

    /**
     * Constructor. Use to set any default properties.
     *
     */
    public function __construct($id = 0, $question = null, $context = null, $params = []) {
        $this->length = 10;
        return parent::__construct($id, $question, $context, $params);
    }

    protected function responseclass() {
        return '\\mod_observation\\responsetype\\text';
    }

    public function helpname() {
        return 'numeric';
    }

    /**
     * Override and return a form template if provided. Output of question_survey_display is iterpreted based on this.
     * @return boolean | string
     */
    public function question_template() {
        return 'mod_observation/question_numeric';
    }

    /**
     * Override and return a response template if provided. Output of response_survey_display is iterpreted based on this.
     * @return boolean | string
     */
    public function response_template() {
        return 'mod_observation/response_numeric';
    }

    /**
     * Return the context tags for the check question template.
     * @param \mod_observation\responsetype\response\response $response
     * @param $descendantsdata
     * @param boolean $blankobservation
     * @return object The check question context tags.
     * @throws \coding_exception
     */
    protected function question_survey_display($response, $descendantsdata, $blankobservation=false) {
        // Numeric.
        $questiontags = new \stdClass();
        $precision = $this->precise;
        $a = '';
        if (isset($response->answers[$this->id][0])) {
            $mynumber = $response->answers[$this->id][0]->value;
            if ($mynumber != '') {
                $mynumber0 = $mynumber;
                if (!is_numeric($mynumber) ) {
                    $msg = get_string('notanumber', 'observation', $mynumber);
                    $this->add_notification($msg);
                } else {
                    if ($precision) {
                        $pos = strpos($mynumber, '.');
                        if (!$pos) {
                            if (strlen($mynumber) > $this->length) {
                                $mynumber = substr($mynumber, 0 , $this->length);
                            }
                        }
                        $this->length += (1 + $precision); // To allow for n numbers after decimal point.
                    }
                    $mynumber = number_format($mynumber, $precision , '.', '');
                    if ( $mynumber != $mynumber0) {
                        $a->number = $mynumber0;
                        $a->precision = $precision;
                        $msg = get_string('numberfloat', 'observation', $a);
                        $this->add_notification($msg);
                    }
                }
            }
            if ($mynumber != '') {
                $response->answers[$this->id][0]->value = $mynumber;
            }
        }

        $choice = new \stdClass();
        $choice->onkeypress = 'return event.keyCode != 13;';
        $choice->size = $this->length;
        $choice->name = 'q'.$this->id;
        $choice->maxlength = $this->length;
        $choice->value = (isset($response->answers[$this->id][0]) ? $response->answers[$this->id][0]->value : '');
        $choice->id = self::qtypename($this->type_id) . $this->id;
        $questiontags->qelements = new \stdClass();
        $questiontags->qelements->choice = $choice;
        return $questiontags;
    }

    /**
     * Check question's form data for valid response. Override this is type has specific format requirements.
     *
     * @param object $responsedata The data entered into the response.
     * @return boolean
     */
    public function response_valid($responsedata) {
        $responseval = false;
        if (is_a($responsedata, 'mod_observation\responsetype\response\response')) {
            // If $responsedata is a response object, look through the answers.
            if (isset($responsedata->answers[$this->id]) && !empty($responsedata->answers[$this->id])) {
                $answer = $responsedata->answers[$this->id][0];
                $responseval = $answer->value;
            }
        } else if (isset($responsedata->{'q'.$this->id})) {
            $responseval = $responsedata->{'q' . $this->id};
        }
        if ($responseval !== false) {
            // If commas are present, replace them with periods, in case that was meant as the European decimal place.
            $responseval = str_replace(',', '.', $responseval);
            return (($responseval == '') || is_numeric($responseval));
        } else {
            return parent::response_valid($responsedata);
        }
    }

    /**
     * Return the context tags for the numeric response template.
     * @param object $data
     * @return object The numeric question response context tags.
     *
     */
    protected function response_survey_display($response) {
        $resptags = new \stdClass();
        if (isset($response->answers[$this->id])) {
            $answer = reset($response->answers[$this->id]);
            $resptags->content = $answer->value;
        }
        return $resptags;
    }

    protected function form_length(\MoodleQuickForm $mform, $helptext = '') {
        $this->length = isset($this->length) ? $this->length : 10;
        return parent::form_length($mform, 'maxdigitsallowed');
    }

    protected function form_precise(\MoodleQuickForm $mform, $helptext = '') {
        return parent::form_precise($mform, 'numberofdecimaldigits');
    }

    /**
     * True if question provides mobile support.
     *
     * @return bool
     */
    public function supports_mobile() {
        return true;
    }

    /**
     * @param $qnum
     * @param $fieldkey
     * @param bool $autonum
     * @return \stdClass
     * @throws \coding_exception
     */
    public function mobile_question_display($qnum, $autonum = false) {
        $mobiledata = parent::mobile_question_display($qnum, $autonum);
        $mobiledata->isnumeric = true;
        return $mobiledata;
    }

    /**
     * @return mixed
     */
    public function mobile_question_choices_display() {
        $choices = [];
        $choices[0] = new \stdClass();
        $choices[0]->id = 0;
        $choices[0]->choice_id = 0;
        $choices[0]->question_id = $this->id;
        $choices[0]->content = '';
        $choices[0]->value = null;
        return $choices;
    }
}