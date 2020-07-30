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
 * print the form to add or edit a observation-instance
 *
 * @author Mike Churchward
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package observation
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/observation/observation.class.php');
require_once($CFG->dirroot.'/mod/observation/locallib.php');

class mod_observation_mod_form extends moodleform_mod {

    protected function definition() {
        global $COURSE;
        global $observationtypes, $observationrespondents, $observationresponseviewers, $autonumbering;

        $observation = new observation($this->_instance, null, $COURSE, $this->_cm);

        $mform    =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'observation'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('description'));

        $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));

        $enableopengroup = array();
        $enableopengroup[] =& $mform->createElement('checkbox', 'useopendate', get_string('opendate', 'observation'));
        $enableopengroup[] =& $mform->createElement('date_time_selector', 'opendate', '');
        $mform->addGroup($enableopengroup, 'enableopengroup', get_string('opendate', 'observation'), ' ', false);
        $mform->addHelpButton('enableopengroup', 'opendate', 'observation');
        $mform->disabledIf('enableopengroup', 'useopendate', 'notchecked');

        $enableclosegroup = array();
        $enableclosegroup[] =& $mform->createElement('checkbox', 'useclosedate', get_string('closedate', 'observation'));
        $enableclosegroup[] =& $mform->createElement('date_time_selector', 'closedate', '');
        $mform->addGroup($enableclosegroup, 'enableclosegroup', get_string('closedate', 'observation'), ' ', false);
        $mform->addHelpButton('enableclosegroup', 'closedate', 'observation');
        $mform->disabledIf('enableclosegroup', 'useclosedate', 'notchecked');

        $mform->addElement('header', 'observationhdr', get_string('responseoptions', 'observation'));

        $mform->addElement('select', 'qtype', get_string('qtype', 'observation'), $observationtypes);
        $mform->addHelpButton('qtype', 'qtype', 'observation');

        $mform->addElement('hidden', 'cannotchangerespondenttype');
        $mform->setType('cannotchangerespondenttype', PARAM_INT);
        $mform->addElement('select', 'respondenttype', get_string('respondenttype', 'observation'), $observationrespondents);
        $mform->addHelpButton('respondenttype', 'respondenttype', 'observation');
        $mform->disabledIf('respondenttype', 'cannotchangerespondenttype', 'eq', 1);

        $mform->addElement('select', 'resp_view', get_string('responseview', 'observation'), $observationresponseviewers);
        $mform->addHelpButton('resp_view', 'responseview', 'observation');

        $notificationoptions = array(0 => get_string('no'), 1 => get_string('notificationsimple', 'observation'),
            2 => get_string('notificationfull', 'observation'));
        $mform->addElement('select', 'notifications', get_string('notifications', 'observation'), $notificationoptions);
        $mform->addHelpButton('notifications', 'notifications', 'observation');

        $options = array('0' => get_string('no'), '1' => get_string('yes'));
        $mform->addElement('select', 'resume', get_string('resume', 'observation'), $options);
        $mform->addHelpButton('resume', 'resume', 'observation');

        $options = array('0' => get_string('no'), '1' => get_string('yes'));
        $mform->addElement('select', 'navigate', get_string('navigate', 'observation'), $options);
        $mform->addHelpButton('navigate', 'navigate', 'observation');

        $mform->addElement('select', 'autonum', get_string('autonumbering', 'observation'), $autonumbering);
        $mform->addHelpButton('autonum', 'autonumbering', 'observation');
        // Default = autonumber both questions and pages.
        $mform->setDefault('autonum', 3);

        // Removed potential scales from list of grades. CONTRIB-3167.
        $grades[0] = get_string('nograde');
        for ($i = 100; $i >= 1; $i--) {
            $grades[$i] = $i;
        }
        $mform->addElement('select', 'grade', get_string('grade', 'observation'), $grades);

        if (empty($observation->sid)) {
            if (!isset($observation->id)) {
                $observation->id = 0;
            }

            $mform->addElement('header', 'contenthdr', get_string('contentoptions', 'observation'));
            $mform->addHelpButton('contenthdr', 'createcontent', 'observation');

            $mform->addElement('radio', 'create', get_string('createnew', 'observation'), '', 'new-0');

            // Retrieve existing private observations from current course.
            $surveys = observation_get_survey_select($COURSE->id, 'private');
            if (!empty($surveys)) {
                $prelabel = get_string('useprivate', 'observation');
                foreach ($surveys as $value => $label) {
                    $mform->addElement('radio', 'create', $prelabel, $label, $value);
                    $prelabel = '';
                }
            }
            // Retrieve existing template observations from this site.
            $surveys = observation_get_survey_select($COURSE->id, 'template');
            if (!empty($surveys)) {
                $prelabel = get_string('usetemplate', 'observation');
                foreach ($surveys as $value => $label) {
                    $mform->addElement('radio', 'create', $prelabel, $label, $value);
                    $prelabel = '';
                }
            } else {
                $mform->addElement('static', 'usetemplate', get_string('usetemplate', 'observation'),
                                '('.get_string('notemplatesurveys', 'observation').')');
            }

            // Retrieve existing public observations from this site.
            $surveys = observation_get_survey_select($COURSE->id, 'public');
            if (!empty($surveys)) {
                $prelabel = get_string('usepublic', 'observation');
                foreach ($surveys as $value => $label) {
                    $mform->addElement('radio', 'create', $prelabel, $label, $value);
                    $prelabel = '';
                }
            } else {
                $mform->addElement('static', 'usepublic', get_string('usepublic', 'observation'),
                                   '('.get_string('nopublicsurveys', 'observation').')');
            }

            $mform->setDefault('create', 'new-0');
        }

        $this->standard_coursemodule_elements();

        // Buttons.
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;
        if (empty($defaultvalues['opendate'])) {
            $defaultvalues['useopendate'] = 0;
        } else {
            $defaultvalues['useopendate'] = 1;
        }
        if (empty($defaultvalues['closedate'])) {
            $defaultvalues['useclosedate'] = 0;
        } else {
            $defaultvalues['useclosedate'] = 1;
        }
        // Prevent observation set to "anonymous" to be reverted to "full name".
        $defaultvalues['cannotchangerespondenttype'] = 0;
        if (!empty($defaultvalues['respondenttype']) && $defaultvalues['respondenttype'] == "anonymous") {
            // If this observation has responses.
            $numresp = $DB->count_records('observation_response',
                            array('observationid' => $defaultvalues['instance'], 'complete' => 'y'));
            if ($numresp) {
                $defaultvalues['cannotchangerespondenttype'] = 1;
            }
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    public function add_completion_rules() {
        $mform =& $this->_form;
        $mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'observation'));
        return array('completionsubmit');
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }

}