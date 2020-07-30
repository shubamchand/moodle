<?php 

namespace theme_ausinet\output;

use html_writer;

require_once($CFG->dirroot.'/course/format/remuiformat/renderer.php');


class format_remuiformat_renderer extends \format_remuiformat_renderer {

	
    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    public function section_right_content($section, $course, $onsectionpage) {
        global $PAGE;
        // error_reporting(E_All);
        $o = $this->output->spacer();
        $controls = $this->section_edit_control_items($course, $section, $onsectionpage);

        if ($PAGE->user_is_editing()) {

            $o .= html_writer::link('javascript:void(0);', '<i class="fa fa-lock "></i>', ['class' => 'ra-popup', 'data-type' => 'section',  'data-cmid' => $section->id] );
        }
        $o .= $this->section_edit_control_menu($controls, $course, $section);
        return $o;
    }
}