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

namespace theme_ausinet\output;

use moodle_url;
use html_writer;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_boost
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends \core_renderer {

    /**
     * We don't like these...
     *
     */
/*    public function edit_button(moodle_url $url) {
        return '';
    }*/


    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $CFG, $SITE, $SESSION;

        $context = $form->export_for_template($this);

        $context->username = isset($SESSION->formuser) ? $SESSION->formuser : $context->username;
        // Override because rendering is not supported in template yet.
        if ($CFG->rememberusername == 0) {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabledonlysession');
        } else {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        }
        $context->errorformatted = $this->error_text($context->error);
        
        $config = new \theme_ausinet\config;
        $logourl = $config->get_logo_url();


        // if ($url) {
        //     $url = $url->out(false);
        // }
        $context->siteurl = $CFG->wwwroot;
        $context->logo_url = $logourl;
        $context->sitename = format_string($SITE->fullname, true,
                ['context' => \context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('theme_ausinet/loginform', $context);
    }  


    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        global $PAGE, $OUTPUT, $USER;

        if ($PAGE->include_region_main_settings_in_header_actions() && !$PAGE->blocks->is_block_present('settings')) {
            // Only include the region main settings if the page has requested it and it doesn't already have
            // the settings block on it. The region main settings are included in the settings block and
            // duplicating the content causes behat failures.
            $PAGE->add_header_action(html_writer::div(
                $this->region_main_settings_menu(),
                'd-print-none',
                ['id' => 'region-main-settings-menu']
            ));
        }

        $header = new \stdClass();
        $header->settingsmenu = $this->context_header_settings_menu();
        $header->contextheader = $this->context_header();
        $header->hasnavbar = empty($PAGE->layout_options['nonavbar']);
        $header->navbar = $this->navbar();
        $header->pageheadingbutton = $this->page_heading_button();
/*
        if (strpos($PAGE->pagetype, 'course-view') !== false) {

            // $PAGE->add_body_class($PAGE->course->shortname);
            $managebutton = $OUTPUT->single_button(new \moodle_url('/report/progress/index.php',
                    array('course' => $PAGE->course->id)), get_string('completion', 'theme_ausinet'), 'get');
            $userroles = get_user_roles($PAGE->context, $USER->id);
            $userrole = current($userroles)->shortname;
            print_r($userroles);
            if ( $userrole == 'teacher' || $userrole == 'non-editingteacher') {
                $header->pageheadingbutton .= $managebutton;
            // }
        }*/

        $header->courseheader = $this->course_header();
        $header->headeractions = $PAGE->get_header_actions();
        return $this->render_from_template('core/full_header', $header);
    } 

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * @param mform $form
     * @return string
     */
    /*public function render_login_signup_form($form) {
        global $SITE, $USER, $PAGE;

        $user = optional_param('email', null, PARAM_RAW);

        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context['logourl'] = $url;
        $context['sitename'] = format_string($SITE->fullname, true,
                ['context' => \context_course::instance(SITEID), "escape" => false]);

        $PAGE->requires->data_for_js('signup_user', $USER->id, true);
        return $this->render_from_template('core/signup_form_layout', $context);
    }*/

    /**
     * Returns HTML attributes to use within the body tag. This includes an ID and classes.
     *
     * @since Moodle 2.5.1 2.6
     * @param string|array $additionalclasses Any additional classes to give the body tag,
     * @return string
     */
   /* public function body_attributes($additionalclasses = array()) {
        if (!is_array($additionalclasses)) {
            $additionalclasses = explode(' ', $additionalclasses);
        }
        return ' id="'. $this->body_id().'" class="'.$this->body_css_classes($additionalclasses).'" onmousedown="return false" onselectstart="return false"  ';
    }*/
}