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

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/observation/observation.class.php');

$qid = required_param('qid', PARAM_INT);
$rid = required_param('rid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$sec = required_param('sec', PARAM_INT);
$null = null;
$referer = $CFG->wwwroot.'/mod/observation/report.php';

if (! $observation = $DB->get_record("observation", array("id" => $qid))) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id" => $observation->course))) {
    print_error('coursemisconf');
}
if (! $cm = get_coursemodule_from_instance("observation", $observation->id, $course->id)) {
    print_error('invalidcoursemodule');
}

// Check login and get context.
require_login($courseid);

$observation = new observation(0, $observation, $course, $cm);

// Add renderer and page objects to the observation object for display use.
$observation->add_renderer($PAGE->get_renderer('mod_observation'));
if (!empty($rid)) {
    $observation->add_page(new \mod_observation\output\reportpage());
} else {
    $observation->add_page(new \mod_observation\output\previewpage());
}

// If you can't view the observation, or can't view a specified response, error out.
if (!($observation->capabilities->view && (($rid == 0) || $observation->can_view_response($rid)))) {
    // Should never happen, unless called directly by a snoop...
    print_error('nopermissions', 'moodle', $CFG->wwwroot.'/mod/observation/view.php?id='.$cm->id);
}
$blankobservation = true;
if ($rid != 0) {
    $blankobservation = false;
}
$url = new moodle_url($CFG->wwwroot.'/mod/observation/print.php');
$url->param('qid', $qid);
$url->param('rid', $rid);
$url->param('courseid', $courseid);
$url->param('sec', $sec);
$PAGE->set_url($url);
$PAGE->set_title($observation->survey->title);
$PAGE->set_pagelayout('popup');
echo $observation->renderer->header();
$observation->page->add_to_page('closebutton', $observation->renderer->close_window_button());
$observation->survey_print_render('', 'print', $courseid, $rid, $blankobservation);
echo $observation->renderer->render($observation->page);
echo $observation->renderer->footer();
