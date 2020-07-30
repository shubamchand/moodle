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

namespace mod_observation;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class settings_form extends \moodleform {

    public function definition() {
        global $observation, $observationrealms;

        $mform    =& $this->_form;

        $mform->addElement('header', 'contenthdr', get_string('contentoptions', 'observation'));

        $capabilities = observation_load_capabilities($observation->cm->id);
        if (!$capabilities->createtemplates) {
            unset($observationrealms['template']);
        }
        if (!$capabilities->createpublic) {
            unset($observationrealms['public']);
        }
        if (isset($observationrealms['public']) || isset($observationrealms['template'])) {
            $mform->addElement('select', 'realm', get_string('realm', 'observation'), $observationrealms);
            $mform->setDefault('realm', $observation->survey->realm);
            $mform->addHelpButton('realm', 'realm', 'observation');
        } else {
            $mform->addElement('hidden', 'realm', 'private');
        }
        $mform->setType('realm', PARAM_RAW);

        $mform->addElement('text', 'title', get_string('title', 'observation'), array('size' => '60'));
        $mform->setDefault('title', $observation->survey->title);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addHelpButton('title', 'title', 'observation');

        $mform->addElement('text', 'subtitle', get_string('subtitle', 'observation'), array('size' => '60'));
        $mform->setDefault('subtitle', $observation->survey->subtitle);
        $mform->setType('subtitle', PARAM_TEXT);
        $mform->addHelpButton('subtitle', 'subtitle', 'observation');

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true);
        $mform->addElement('editor', 'info', get_string('additionalinfo', 'observation'), null, $editoroptions);
        $mform->setDefault('info', $observation->survey->info);
        $mform->setType('info', PARAM_RAW);
        $mform->addHelpButton('info', 'additionalinfo', 'observation');

        $mform->addElement('header', 'submithdr', get_string('submitoptions', 'observation'));

        $mform->addElement('text', 'thanks_page', get_string('url', 'observation'), array('size' => '60'));
        $mform->setType('thanks_page', PARAM_TEXT);
        $mform->setDefault('thanks_page', $observation->survey->thanks_page);
        $mform->addHelpButton('thanks_page', 'url', 'observation');

        $mform->addElement('static', 'confmes', get_string('confalts', 'observation'));
        $mform->addHelpButton('confmes', 'confpage', 'observation');

        $mform->addElement('text', 'thank_head', get_string('headingtext', 'observation'), array('size' => '30'));
        $mform->setType('thank_head', PARAM_TEXT);
        $mform->setDefault('thank_head', $observation->survey->thank_head);

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true);
        $mform->addElement('editor', 'thank_body', get_string('bodytext', 'observation'), null, $editoroptions);
        $mform->setType('thank_body', PARAM_RAW);
        $mform->setDefault('thank_body', $observation->survey->thank_body);

        $mform->addElement('text', 'email', get_string('email', 'observation'), array('size' => '75'));
        $mform->setType('email', PARAM_TEXT);
        $mform->setDefault('email', $observation->survey->email);
        $mform->addHelpButton('email', 'sendemail', 'observation');

        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sid', 0);
        $mform->setType('sid', PARAM_INT);
        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('hidden', 'courseid', '');
        $mform->setType('courseid', PARAM_RAW);

        // Buttons.

        $submitlabel = get_string('savechangesanddisplay');
        $submit2label = get_string('savechangesandreturntocourse');
        $mform = $this->_form;

        // Elements in a row need a group.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', $submit2label);
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}