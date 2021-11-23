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
 * Moodle Course Status block.  Displays Visibility status of a course.
 *
 * Allows users with appropriate permissions to publish / unpublish the course (make it visible / non-visible).
 *
 * @package block_course_status
 * @copyright 2018 Manoj Solanki (Coventry University)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot .'/course/lib.php');

/**
 * Course status block implementation class.
 *
 * @package block_course_status
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_status extends block_base {

    /** @var int Display Mode tabs */
    const DISPLAY_MODE_TABS = 1;

    /**
     * Adds title to block instance.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_course_status');
    }

    /**
     * Set up any configuration data.
     *
     * The is called immediatly after init().
     */
    public function specialization() {
        $config = get_config("block_course_status");

        // Use the title as defined in plugin settings, if one exists.
        if (!empty($config->title)) {
            $this->title = $config->title;
        } else {
            $this->title = 'Course status';
        }
    }

    /**
     * Which page types this block may appear on.
     */
    public function applicable_formats() {
        return array('site-index' => true, 'course-view-*' => true);
    }

    /**
     * Get block instance content.
     */
    public function get_content() {

        global $COURSE, $USER, $OUTPUT, $ME;

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config("block_course_status");

        $this->content = new stdClass();
        $this->content->text = '';

        $displayblock = false;

        $content = '';

        // Check if this is a course page.  Allow display on any course page and section pages (but not activities).
        // Check if $PAGE->url is set.  It should be, but also using a fallback.
        $url = null;
        if ($this->page->has_set_url()) {
            $url = $this->page->url;
        } else if ($ME !== null) {
            $url = new moodle_url(str_ireplace('/index.php', '/', $ME));
        }

        // Check if on a course page and if the URL contains course/view.php to be safe.
        if ($COURSE->id != SITEID) {
            // In practice, $url should always be valid.
            if ($url !== null) {
                // Check if this is the course view page.
                if (($url !== null) && (strstr ($url->raw_out(), 'course/view.php'))) {
                    $displayblock = true;
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }

        // Check the user has update or visibility setting capability within their role.
        $capabilities = array(
                'moodle/course:update',
                'moodle/course:visibility',
                'moodle/course:viewhiddencourses'
        );
        $context = context_course::instance($COURSE->id);
        if (has_any_capability($capabilities, $context)) {
            $displayblock = true;
        } else {
            return null;
        }

        // Get course and URL details for action buttons.
        $baseurl = new moodle_url(
                '/blocks/course_status/management.php',
                array('courseid' => $COURSE->id , 'categoryid' => $COURSE->category, 'sesskey' => sesskey())
                );

        if (!empty($config->publishedicon)) {
            $publishedicon = '<i class="fa fa-' . $config->publishedicon . '"></i> ';
        } else {
            $publishedicon = '<i class="fa fa-' . get_string('publishedicon', 'block_course_status') . '"></i> ';
        }

        if (!empty($config->unpublishedicon)) {
            $unpublishedicon = '<i class="fa fa-' . $config->unpublishedicon . '"></i> ';
        } else {
            $unpublishedicon = '<i class="fa fa-' . get_string('unpublishedicon', 'block_course_status') . '"></i> ';
        }

        if ($COURSE->visible == 1) {
            $unpublishedclass = 'btn-unpublish';
            $unpublishedlabel = get_string('unpublish', 'block_course_status');
            $publishedclass = 'btn-published';
            $publishedlabel = $publishedicon . get_string('published', 'block_course_status');

            $content .= html_writer::link(new moodle_url($baseurl,
                        array('action' => 'hidecourse', 'redirect' => $this->page->url)), '<button class="btn-course-status ' .
                        $unpublishedclass . '" title="' . $unpublishedlabel . '">' . $unpublishedlabel . '</button>');
            $content .= html_writer::tag('button', $publishedlabel, array('class' => 'btn-course-status ' . $publishedclass));

        } else {
            $unpublishedclass = 'btn-unpublished';
            $unpublishedlabel = $unpublishedicon . get_string('unpublished', 'block_course_status');
            $publishedclass = 'btn-publish';
            $publishedlabel = get_string('publish', 'block_course_status');

            $content .= html_writer::tag('button', $unpublishedlabel, array('class' => 'btn-course-status ' . $unpublishedclass));
            $content .= html_writer::link(new moodle_url($baseurl,
                        array('action' => 'showcourse', 'redirect' => $this->page->url)), '<button class="btn-course-status ' .
                        $publishedclass . '" title="' . $publishedlabel . '">' . $publishedlabel . '</button>');

        }

        $this->content->text = $content;

        return $this->content;
    }

    /**
     * Allows multiple instances of the block.
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets block header to be hidden or visible
     *
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        $config = get_config("block_course_status");

        // If title in settings is empty, hide header.
        if (!empty($config->title)) {
            return false;
        } else {
            return true;
        }
    }

}
