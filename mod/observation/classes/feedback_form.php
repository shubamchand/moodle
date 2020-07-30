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
 * Print the form to manage feedback settings.
 *
 * @package mod_observation
 * @copyright  2016 onward Mike Churchward (mike.churchward@poetgroup.org)
 * @author Joseph Rezeau
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

namespace mod_observation;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/mod/observation/lib.php');

class feedback_form extends \moodleform {

    public function definition() {
        global $observation;

        $mform =& $this->_form;

        // Observation Feedback Sections and Messages.
        $feedbackoptions = [];
        $feedbackoptions[0] = get_string('feedbacknone', 'observation');
        $mform->addElement('header', 'submithdr', get_string('feedbackoptions', 'observation'));
        $feedbackoptions[1] = get_string('feedbackglobal', 'observation');
        $feedbackoptions[2] = get_string('feedbacksections', 'observation');

        $mform->addElement('select', 'feedbacksections', get_string('feedbackoptions', 'observation'), $feedbackoptions);
        $mform->setDefault('feedbacksections', $observation->survey->feedbacksections);
        $mform->addHelpButton('feedbacksections', 'feedbackoptions', 'observation');

        $options = ['0' => get_string('no'), '1' => get_string('yes')];
        $mform->addElement('select', 'feedbackscores', get_string('feedbackscores', 'observation'), $options);
        $mform->addHelpButton('feedbackscores', 'feedbackscores', 'observation');

        // Is the RGraph library enabled at level site?
        if (get_config('observation', 'usergraph')) {
            $chartgroup = [];
            $charttypes = [null => get_string('none'),
                'bipolar' => get_string('chart:bipolar', 'observation'),
                'vprogress' => get_string('chart:vprogress', 'observation')];
            $chartgroup[] = $mform->createElement('select', 'chart_type_global',
                get_string('chart:type', 'observation') . ' (' .
                get_string('feedbackglobal', 'observation') . ')', $charttypes);
            if ($observation->survey->feedbacksections == 1) {
                $mform->setDefault('chart_type_global', $observation->survey->chart_type);
            }
            $mform->disabledIf('chart_type_global', 'feedbacksections', 'eq', 0);
            $mform->disabledIf('chart_type_global', 'feedbacksections', 'neq', 1);

            $charttypes = [null => get_string('none'),
                'bipolar' => get_string('chart:bipolar', 'observation'),
                'hbar' => get_string('chart:hbar', 'observation'),
                'rose' => get_string('chart:rose', 'observation')];
            $chartgroup[] = $mform->createElement('select', 'chart_type_two_sections',
                get_string('chart:type', 'observation') . ' (' .
                get_string('feedbackbysection', 'observation') . ')', $charttypes);
            if ($observation->survey->feedbacksections > 1) {
                $mform->setDefault('chart_type_two_sections', $observation->survey->chart_type);
            }
            $mform->disabledIf('chart_type_two_sections', 'feedbacksections', 'neq', 2);

            $charttypes = [null => get_string('none'),
                'bipolar' => get_string('chart:bipolar', 'observation'),
                'hbar' => get_string('chart:hbar', 'observation'),
                'radar' => get_string('chart:radar', 'observation'),
                'rose' => get_string('chart:rose', 'observation')];
            $chartgroup[] = $mform->createElement('select', 'chart_type_sections',
                get_string('chart:type', 'observation') . ' (' .
                get_string('feedbackbysection', 'observation') . ')', $charttypes);
            if ($observation->survey->feedbacksections > 1) {
                $mform->setDefault('chart_type_sections', $observation->survey->chart_type);
            }
            $mform->disabledIf('chart_type_sections', 'feedbacksections', 'eq', 0);
            $mform->disabledIf('chart_type_sections', 'feedbacksections', 'eq', 1);
            $mform->disabledIf('chart_type_sections', 'feedbacksections', 'eq', 2);

            $mform->addGroup($chartgroup, 'chartgroup',
                get_string('chart:type', 'observation'), null, false);
            $mform->addHelpButton('chartgroup', 'chart:type', 'observation');
        }
        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true];
        $mform->addElement('editor', 'feedbacknotes', get_string('feedbacknotes', 'observation'), null, $editoroptions);
        $mform->setType('feedbacknotes', PARAM_RAW);
        $mform->setDefault('feedbacknotes', $observation->survey->feedbacknotes);
        $mform->addHelpButton('feedbacknotes', 'feedbacknotes', 'observation');

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sid', 0);
        $mform->setType('sid', PARAM_INT);
        $mform->addElement('hidden', 'courseid', '');
        $mform->setType('courseid', PARAM_RAW);

        // Can't seem to disable or hide one button in the group, so create two different button sets and hide one.
        $buttongroup = [];
        $buttongroup[] = $mform->createElement('submit', 'feedbacksettingsbutton1', get_string('savesettings', 'observation'));
        $buttongroup[] = $mform->createElement('submit', 'feedbackeditbutton', get_string('feedbackeditsections', 'observation'));
        $mform->addGroup($buttongroup, 'buttongroup');
        if (moodle_major_version() == '3.3') {
            $mform->disabledIf('buttongroup', 'feedbacksections', 'eq', 0);
        } else {
            $mform->hideIf('buttongroup', 'feedbacksections', 'eq', 0);
        }

        $mform->addElement('submit', 'feedbacksettingsbutton2', get_string('savesettings', 'observation'));
        if (moodle_major_version() == '3.3') {
            $mform->disabledIf('feedbacksettingsbutton2', 'feedbacksections', 'neq', 0);
        } else {
            $mform->hideIf('feedbacksettingsbutton2', 'feedbacksections', 'neq', 0);
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}