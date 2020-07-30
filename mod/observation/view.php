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
require_once($CFG->dirroot.'/mod/observation/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot.'/mod/observation/observation.class.php');

if (!isset($SESSION->observation)) {
    $SESSION->observation = new stdClass();
}
$SESSION->observation->current_tab = 'view';

$id = optional_param('id', null, PARAM_INT);    // Course Module ID.
$a = optional_param('a', null, PARAM_INT);      // Or observation ID.

$sid = optional_param('sid', null, PARAM_INT);  // Survey id.

list($cm, $course, $observation) = observation_get_standard_page_items($id, $a);

// Check login and get context.
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url($CFG->wwwroot.'/mod/observation/view.php');
if (isset($id)) {
    $url->param('id', $id);
} else {
    $url->param('a', $a);
}
if (isset($sid)) {
    $url->param('sid', $sid);
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$observation = new observation(0, $observation, $course, $cm);
// Add renderer and page objects to the observation object for display use.
$observation->add_renderer($PAGE->get_renderer('mod_observation'));
$observation->add_page(new \mod_observation\output\viewpage());

$PAGE->set_title(format_string($observation->name));
$PAGE->set_heading(format_string($course->fullname));

$userroles = get_user_roles($PAGE->context, $USER->id);

$userrole = (!empty(current($userroles))) ? current($userroles)->shortname : '';
// echo $userrole;
if ($userrole == 'student') {

    $usernumresp = $observation->count_submissions($USER->id);

    // echo $observation->capabilities->readownresponses;
    // exit;
    if ($observation->capabilities->readownresponses && ($usernumresp > 0)) {
        $argstr = 'instance='.$observation->id.'&user='.$USER->id;
        if ($usernumresp > 1) {
            $titletext = get_string('viewyourresponses', 'observation', $usernumresp);
        } else {
            $titletext = get_string('yourresponse', 'observation');
            $argstr .= '&byresponse=1&action=vresp';
        }
        $observation->page->add_to_page('yourresponse',
            '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/observation/myreport.php?'.$argstr).'">'.$titletext.'</a>');
        $url = $CFG->wwwroot.htmlspecialchars('/mod/observation/myreport.php?'.$argstr);
        redirect($url);
    } else {

        if ($observation->questions) { // Sanity check.
            if (!$observation->user_has_saved_response($USER->id)) {
                
                $link = $CFG->wwwroot.htmlspecialchars('/mod/observation/complete.php?' .
                    'id=' . $observation->cm->id);
            } else {
                $resumesurvey = get_string('resumesurvey', 'observation');
                
                $link = $CFG->wwwroot.htmlspecialchars('/mod/observation/complete.php?' .
                    'id='.$observation->cm->id.'&resume=1');
            }
            redirect($link);
        } 
    }
}
// exit;
echo $observation->renderer->header();
$observation->page->add_to_page('observationname', format_string($observation->name));

// Print the main part of the page.
if ($observation->intro) {
    $observation->page->add_to_page('intro', format_module_intro('observation', $observation, $cm->id));
}

$cm = $observation->cm;
$currentgroupid = groups_get_activity_group($cm);
if (!groups_is_member($currentgroupid, $USER->id)) {
    $currentgroupid = 0;
}

$message = $observation->user_access_messages($USER->id);
if ($message !== false) {
    $observation->page->add_to_page('message', $message);
} else if ($observation->user_can_take($USER->id)) {
    if ($observation->questions) { // Sanity check.
        if (!$observation->user_has_saved_response($USER->id)) {
            $observation->page->add_to_page('complete',
                '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/observation/complete.php?' .
                'id=' . $observation->cm->id) . '" class="btn btn-primary">' .
                get_string('answerquestions', 'observation') . '</a>');
        } else {
            $resumesurvey = get_string('resumesurvey', 'observation');
            $observation->page->add_to_page('complete',
                '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/observation/complete.php?' .
                'id='.$observation->cm->id.'&resume=1').'" title="'.$resumesurvey.'" class="btn btn-primary">'.$resumesurvey.'</a>');
        }
    } else {
        $observation->page->add_to_page('message', get_string('noneinuse', 'observation'));
    }
}

if ($observation->capabilities->editquestions && !$observation->questions && $observation->is_active()) {
    $observation->page->add_to_page('complete',
        '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/observation/questions.php?'.
        'id=' . $observation->cm->id) . '" class="btn btn-primary">' .
        get_string('addquestions', 'observation') . '</a>');
}

if (isguestuser()) {
    $guestno = html_writer::tag('p', get_string('noteligible', 'observation'));
    $liketologin = html_writer::tag('p', get_string('liketologin'));
    $observation->page->add_to_page('guestuser',
        $observation->renderer->confirm($guestno."\n\n".$liketologin."\n", get_login_url(), get_local_referer(false)));
}

// Log this course module view.
// Needed for the event logging.
$context = context_module::instance($observation->cm->id);
$anonymous = $observation->respondenttype == 'anonymous';

$event = \mod_observation\event\course_module_viewed::create(array(
                'objectid' => $observation->id,
                'anonymous' => $anonymous,
                'context' => $context
));
$event->trigger();

$usernumresp = $observation->count_submissions($USER->id);

// echo $observation->capabilities->readownresponses;
// exit;
if ($observation->capabilities->readownresponses && ($usernumresp > 0)) {
    $argstr = 'instance='.$observation->id.'&user='.$USER->id;
    if ($usernumresp > 1) {
        $titletext = get_string('viewyourresponses', 'observation', $usernumresp);
    } else {
        $titletext = get_string('yourresponse', 'observation');
        $argstr .= '&byresponse=1&action=vresp';
    }
    $observation->page->add_to_page('yourresponse',
        '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/observation/myreport.php?'.$argstr).'">'.$titletext.'</a>');
    $url = $CFG->wwwroot.htmlspecialchars('/mod/observation/myreport.php?'.$argstr);
    redirect($url);
}

if ($observation->can_view_all_responses($usernumresp)) {
    $argstr = 'instance='.$observation->id.'&group='.$currentgroupid;
    $observation->page->add_to_page('allresponses',
        '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/observation/report.php?'.$argstr).'">'.
        get_string('viewallresponses', 'observation').'</a>');
}

echo $observation->renderer->render($observation->page);
echo $observation->renderer->footer();
