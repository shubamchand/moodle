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

// This page prints a particular instance of observation.

require_once("../../config.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot.'/mod/observation/observation.class.php');

if (!isset($SESSION->observation)) {
    $SESSION->observation = new stdClass();
}
$SESSION->observation->current_tab = 'view';

$id = optional_param('id', null, PARAM_INT);    // Course Module ID.
$a = optional_param('a', null, PARAM_INT);      // observation ID.

$sid = optional_param('sid', null, PARAM_INT);  // Survey id.
$resume = optional_param('resume', null, PARAM_INT);    // Is this attempt a resume of a saved attempt?

list($cm, $course, $observation) = observation_get_standard_page_items($id, $a);

// Check login and get context.
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/observation:view', $context);

$url = new moodle_url($CFG->wwwroot.'/mod/observation/complete.php');
if (isset($id)) {
    $url->param('id', $id);
} else {
    $url->param('a', $a);
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$observation = new observation(0, $observation, $course, $cm);
// Add renderer and page objects to the observation object for display use.
$observation->add_renderer($PAGE->get_renderer('mod_observation'));
$observation->add_page(new \mod_observation\output\completepage());

$observation->strobservations = get_string("modulenameplural", "observation");
$observation->strobservation  = get_string("modulename", "observation");

// Mark as viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

if ($resume) {
    $context = context_module::instance($observation->cm->id);
    $anonymous = $observation->respondenttype == 'anonymous';

    $event = \mod_observation\event\attempt_resumed::create(array(
                    'objectid' => $observation->id,
                    'anonymous' => $anonymous,
                    'context' => $context
    ));
    $event->trigger();
}

// Generate the view HTML in the page.
$observation->view();

// Output the page.
echo $observation->renderer->header();
echo $observation->renderer->render($observation->page);
echo $observation->renderer->footer($course);