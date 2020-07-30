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

// This page displays a non-completable instance of observation.

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/observation/observation.class.php');

$id     = optional_param('id', 0, PARAM_INT);
$sid    = optional_param('sid', 0, PARAM_INT);
$popup  = optional_param('popup', 0, PARAM_INT);
$qid    = optional_param('qid', 0, PARAM_INT);
$currentgroupid = optional_param('group', 0, PARAM_INT); // Groupid.

if ($id) {
    if (! $cm = get_coursemodule_from_id('observation', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $observation = $DB->get_record("observation", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else {
    if (! $survey = $DB->get_record("observation_survey", array("id" => $sid))) {
        print_error('surveynotexists', 'observation');
    }
    if (! $course = $DB->get_record("course", ["id" => $survey->courseid])) {
        print_error('coursemisconf');
    }
    // Dummy observation object.
    $observation = new stdClass();
    $observation->id = 0;
    $observation->course = $course->id;
    $observation->name = $survey->title;
    $observation->sid = $sid;
    $observation->resume = 0;
    // Dummy cm object.
    if (!empty($qid)) {
        $cm = get_coursemodule_from_instance('observation', $qid, $course->id);
    } else {
        $cm = false;
    }
}

// Check login and get context.
// Do not require login if this observation is viewed from the Add observation page
// to enable teachers to view template or public observations located in a course where they are not enroled.
if (!$popup) {
    require_login($course->id, false, $cm);
}
$context = $cm ? context_module::instance($cm->id) : false;

$url = new moodle_url('/mod/observation/preview.php');
if ($id !== 0) {
    $url->param('id', $id);
}
if ($sid) {
    $url->param('sid', $sid);
}
$PAGE->set_url($url);

$PAGE->set_context($context);
$PAGE->set_cm($cm);   // CONTRIB-5872 - I don't know why this is needed.

$observation = new observation($qid, $observation, $course, $cm);

// Add renderer and page objects to the observation object for display use.
$observation->add_renderer($PAGE->get_renderer('mod_observation'));
$observation->add_page(new \mod_observation\output\previewpage());

$canpreview = (!isset($observation->capabilities) &&
               has_capability('mod/observation:preview', context_course::instance($course->id))) ||
              (isset($observation->capabilities) && $observation->capabilities->preview);
if (!$canpreview && !$popup) {
    // Should never happen, unless called directly by a snoop...
    print_error('nopermissions', 'observation', $CFG->wwwroot.'/mod/observation/view.php?id='.$cm->id);
}

if (!isset($SESSION->observation)) {
    $SESSION->observation = new stdClass();
}
$SESSION->observation->current_tab = new stdClass();
$SESSION->observation->current_tab = 'preview';

$qp = get_string('preview_observation', 'observation');
$pq = get_string('previewing', 'observation');

// Print the page header.
if ($popup) {
    $PAGE->set_pagelayout('popup');
}
$PAGE->set_title(format_string($qp));
if (!$popup) {
    $PAGE->set_heading(format_string($course->fullname));
}

// Include the needed js.


$PAGE->requires->js('/mod/observation/module.js');
// Print the tabs.


echo $observation->renderer->header();
if (!$popup) {
    require('tabs.php');
}
$observation->page->add_to_page('heading', clean_text($pq));

if ($observation->capabilities->printblank) {
    // Open print friendly as popup window.

    $linkname = '&nbsp;'.get_string('printblank', 'observation');
    $title = get_string('printblanktooltip', 'observation');
    $url = '/mod/observation/print.php?qid='.$observation->id.'&amp;rid=0&amp;'.'courseid='.
            $observation->course->id.'&amp;sec=1';
    $options = array('menubar' => true, 'location' => false, 'scrollbars' => true, 'resizable' => true,
                    'height' => 600, 'width' => 800, 'title' => $title);
    $name = 'popup';
    $link = new moodle_url($url);
    $action = new popup_action('click', $link, $name, $options);
    $class = "floatprinticon";
    $observation->page->add_to_page('printblank',
        $observation->renderer->action_link($link, $linkname, $action, array('class' => $class, 'title' => $title),
            new pix_icon('t/print', $title)));
}
$observation->survey_print_render('', 'preview', $course->id, $rid = 0, $popup);
if ($popup) {
    $observation->page->add_to_page('closebutton', $observation->renderer->close_window_button());
}
echo $observation->renderer->render($observation->page);
echo $observation->renderer->footer($course);

// Log this observation preview.
$context = context_module::instance($observation->cm->id);
$anonymous = $observation->respondenttype == 'anonymous';

$event = \mod_observation\event\observation_previewed::create(array(
                'objectid' => $observation->id,
                'anonymous' => $anonymous,
                'context' => $context
));
$event->trigger();
