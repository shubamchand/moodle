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
 * Completion Progress block definition
 *
 * @package    block_course_observation_notification
 * @copyright  2016 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Completion Progress block class
 *
 * @copyright 2016 Michael de Raadt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_observation_notification extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_course_observation_notification');
    }

    /**
     *  we have global config/settings data
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    
    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => false,
            'site'           => true,
            'mod'            => false,
            'my'             => true
        );
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        global $CFG, $USER, $COURSE, $DB, $OUTPUT, $PAGE;
        require_once($CFG->dirroot.'/blocks/course_observation_notification/lib.php');
        $PAGE->requires->jquery();
        $PAGE->requires->js('/blocks/course_observation_notification/javascript/course_observation_notification.js');

        // If content has already been generated, don't waste time generating it again.
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $blockinstancesonpage = array();

        // Guests do not have any progress. Don't show them the block.
        if (!isloggedin() or isguestuser()) {
            return $this->content;
        }

        // get course module observation
        $records = $DB->get_records('course_modules_completion_observation',
                    array(
                        'completionstate' => '0'
                    )
        );
        if(!empty($records)){
            foreach($records as $cmco){
                $formated_data[$cmco->courseid][$cmco->coursemoduleid][] = [
                    'userid' =>$cmco->userid,
                    'section_id' =>$cmco->section_id,
                ];
            }
            $this->content->text .= block_course_observation_notification_tree($formated_data);
            // echo '<pre>';
            // print_r($formated_data);
            // exit;
            // foreach($formated_data as $cmid => $value){
            //     $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
            //     $modinfo = get_fast_modinfo($cm->course);
            //     $sections = $modinfo->get_section_info_all();
            //     $course = $modinfo->get_course();
            //     $section_data = [];
            //     foreach($sections as $section){
            //         $section_data[(int)$section->id]= $section->name;
            //     }
            //     $sected_sections_user = [];
            //     foreach($value as $section_user){
            //         $sected_sections_user[$section_user['section_id']][] = $section_user['userid'];
            //     }
              
            //     // echo $course->name; exit;
            //     // $this->content->text = $course->fullname;

            //     $this->content->text .= block_course_observation_notification_tree($course,$sected_sections_user,$section_data,$cmid);
                
            // }

            if (!empty($this->content->text)) {
                // Expand/Collapse button.
                $expand = '<button class="btn btn-mini btn-primary" type="button" onclick="togglecollapseallObservation();">' .
                   get_string('expand', 'block_course_observation_notification') . '</button>';
   
               $this->content->text = '<dl>'.$expand.$this->content->text.'</dl>';
           } else if (empty($this->content->text) && $showempty) {
               $this->content->text .= '<div class="empty">'.
                   $OUTPUT->pix_icon('s/smiley', get_string('alt_smiley', 'block_course_observation_notification')).' '.
                   get_string('nothing', 'block_course_observation_notification').'</div>'."\n";
           }
        }

       



        return $this->content;
    }
}
