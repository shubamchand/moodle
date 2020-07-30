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
 * This script lists all the instances of observation in a particular course
 *
 * @package    mod
 * @subpackage observation
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once($CFG->dirroot.'/mod/observation/locallib.php');

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/observation/index.php', array('id' => $id));
if (! $course = $DB->get_record('course', array('id' => $id))) {
    print_error('incorrectcourseid', 'observation');
}
$coursecontext = context_course::instance($id);
require_login($course->id);
$PAGE->set_pagelayout('incourse');

$event = \mod_observation\event\course_module_instance_list_viewed::create(array(
                'context' => context_course::instance($course->id)));
$event->trigger();

// Print the header.
$strobservations = get_string("modulenameplural", "observation");
$PAGE->navbar->add($strobservations);
$PAGE->set_title("$course->shortname: $strobservations");
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

// Get all the appropriate data.
if (!$observations = get_all_instances_in_course("observation", $course)) {
    notice(get_string('thereareno', 'moodle', $strobservations), "../../course/view.php?id=$course->id");
    die;
}

// Check if we need the closing date header.
$showclosingheader = false;
foreach ($observations as $observation) {
    if ($observation->closedate != 0) {
        $showclosingheader = true;
    }
    if ($showclosingheader) {
        break;
    }
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

if ($showclosingheader) {
    array_push($headings, get_string('observationcloses', 'observation'));
    array_push($align, 'left');
}

array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
array_unshift($align, 'left');

$showing = '';

// Current user role == admin or teacher.
if (has_capability('mod/observation:viewsingleresponse', $coursecontext)) {
    array_push($headings, get_string('responses', 'observation'));
    array_push($align, 'center');
    $showing = 'stats';
    array_push($headings, get_string('realm', 'observation'));
    array_push($align, 'left');
    // Current user role == student.
} else if (has_capability('mod/observation:submit', $coursecontext)) {
    array_push($headings, get_string('status'));
    array_push($align, 'left');
    $showing = 'responses';
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
foreach ($observations as $observation) {
    $cmid = $observation->coursemodule;
    $data = array();
    $realm = $DB->get_field('observation_survey', 'realm', array('id' => $observation->sid));
    // Template surveys should NOT be displayed as an activity to students.
    if (!($realm == 'template' && !has_capability('mod/observation:manage', context_module::instance($cmid)))) {
        // Section number if necessary.
        $strsection = '';
        if ($observation->section != $currentsection) {
            $strsection = get_section_name($course, $observation->section);
            $currentsection = $observation->section;
        }
        $data[] = $strsection;
        // Show normal if the mod is visible.
        $class = '';
        if (!$observation->visible) {
            $class = ' class="dimmed"';
        }
        $data[] = "<a$class href=\"view.php?id=$cmid\">$observation->name</a>";

        // Close date.
        if ($observation->closedate) {
            $data[] = userdate($observation->closedate);
        } else if ($showclosingheader) {
            $data[] = '';
        }

        if ($showing == 'responses') {
            $status = '';
            if ($responses = observation_get_user_responses($observation->id, $USER->id, $complete = false)) {
                foreach ($responses as $response) {
                    if ($response->complete == 'y') {
                        $status .= get_string('submitted', 'observation').' '.userdate($response->submitted).'<br />';
                    } else {
                        $status .= get_string('attemptstillinprogress', 'observation').' '.
                            userdate($response->submitted).'<br />';
                    }
                }
            }
            $data[] = $status;
        } else if ($showing == 'stats') {
            $data[] = $DB->count_records('observation_response', ['observationid' => $observation->id, 'complete' => 'y']);
            if ($survey = $DB->get_record('observation_survey', ['id' => $observation->sid])) {
                // For a public observation, look for the original public observation that it is based on.
                if ($survey->realm == 'public') {
                    $strpreview = get_string('preview_observation', 'observation');
                    if ($survey->courseid != $course->id) {
                        $publicoriginal = '';
                        $originalcourse = $DB->get_record('course', ['id' => $survey->courseid]);
                        $originalcoursecontext = context_course::instance($survey->courseid);
                        $originalobservation = $DB->get_record('observation',
                            ['sid' => $survey->id, 'course' => $survey->courseid]);
                        $cm = get_coursemodule_from_instance("observation", $originalobservation->id, $survey->courseid);
                        $context = context_course::instance($survey->courseid, MUST_EXIST);
                        $canvieworiginal = has_capability('mod/observation:preview', $context, $USER->id, true);
                        // If current user can view observations in original course,
                        // provide a link to the original public observation.
                        if ($canvieworiginal) {
                            $publicoriginal = '<br />'.get_string('publicoriginal', 'observation').'&nbsp;'.
                                '<a href="'.$CFG->wwwroot.'/mod/observation/preview.php?id='.
                                $cm->id.'" title="'.$strpreview.']">'.$originalobservation->name.' ['.
                                $originalcourse->fullname.']</a>';
                        } else {
                            // If current user is not enrolled as teacher in original course,
                            // only display the original public observation's name and course name.
                            $publicoriginal = '<br />'.get_string('publicoriginal', 'observation').'&nbsp;'.
                                $originalobservation->name.' ['.$originalcourse->fullname.']';
                        }
                        $data[] = get_string($realm, 'observation').' '.$publicoriginal;
                    } else {
                        // Original public observation was created in current course.
                        // Find which courses it is used in.
                        $publiccopy = '';
                        $select = 'course != '.$course->id.' AND sid = '.$observation->sid;
                        if ($copies = $DB->get_records_select('observation', $select, null,
                                $sort = 'course ASC', $fields = 'id, course, name')) {
                            foreach ($copies as $copy) {
                                $copycourse = $DB->get_record('course', array('id' => $copy->course));
                                $select = 'course = '.$copycourse->id.' AND sid = '.$observation->sid;
                                $copyobservation = $DB->get_record('observation',
                                    array('id' => $copy->id, 'sid' => $survey->id, 'course' => $copycourse->id));
                                $cm = get_coursemodule_from_instance("observation", $copyobservation->id, $copycourse->id);
                                $context = context_course::instance($copycourse->id, MUST_EXIST);
                                $canviewcopy = has_capability('mod/observation:view', $context, $USER->id, true);
                                if ($canviewcopy) {
                                    $publiccopy .= '<br />'.get_string('publiccopy', 'observation').'&nbsp;:&nbsp;'.
                                        '<a href = "'.$CFG->wwwroot.'/mod/observation/preview.php?id='.
                                        $cm->id.'" title = "'.$strpreview.'">'.
                                        $copyobservation->name.' ['.$copycourse->fullname.']</a>';
                                } else {
                                    // If current user does not have "view" capability in copy course,
                                    // only display the copied public observation's name and course name.
                                    $publiccopy .= '<br />'.get_string('publiccopy', 'observation').'&nbsp;:&nbsp;'.
                                        $copyobservation->name.' ['.$copycourse->fullname.']';
                                }
                            }
                        }
                        $data[] = get_string($realm, 'observation').' '.$publiccopy;
                    }
                } else {
                    $data[] = get_string($realm, 'observation');
                }
            } else {
                // If a observation is a copy of a public observation which has been deleted.
                $data[] = get_string('removenotinuse', 'observation');
            }
        }
    }
    $table->data[] = $data;
} // End of loop over observation instances.

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();