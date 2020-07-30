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

// Library of functions and constants for module observation.

/**
 * @package mod_observation
 * @copyright  2016 Mike Churchward (mike.churchward@poetgroup.org)
 * @author     Mike Churchward
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('OBSERVATION_RESETFORM_RESET', 'observation_reset_data_');
define('OBSERVATION_RESETFORM_DROP', 'observation_drop_observation_');

function observation_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * @return array all other caps used in module
 */
function observation_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

function observation_get_instance($observationid) {
    global $DB;
    return $DB->get_record('observation', array('id' => $observationid));
}

function observation_add_instance($observation) {
    // Given an object containing all the necessary data,
    // (defined by the form in mod.html) this function
    // will create a new instance and return the id number
    // of the new instance.
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/observation/observation.class.php');
    require_once($CFG->dirroot.'/mod/observation/locallib.php');

    // Check the realm and set it to the survey if it's set.

    if (empty($observation->sid)) {
        // Create a new survey.
        $course = get_course($observation->course);
        $cm = new stdClass();
        $qobject = new observation(0, $observation, $course, $cm);

        if ($observation->create == 'new-0') {
            $sdata = new stdClass();
            $sdata->name = $observation->name;
            $sdata->realm = 'private';
            $sdata->title = $observation->name;
            $sdata->subtitle = '';
            $sdata->info = '';
            $sdata->theme = ''; // Theme is deprecated.
            $sdata->thanks_page = '';
            $sdata->thank_head = '';
            $sdata->thank_body = '';
            $sdata->email = '';
            $sdata->feedbacknotes = '';
            $sdata->courseid = $course->id;
            if (!($sid = $qobject->survey_update($sdata))) {
                print_error('couldnotcreatenewsurvey', 'observation');
            }
        } else {
            $copyid = explode('-', $observation->create);
            $copyrealm = $copyid[0];
            $copyid = $copyid[1];
            if (empty($qobject->survey)) {
                $qobject->add_survey($copyid);
                $qobject->add_questions($copyid);
            }
            // New observations created as "use public" should not create a new survey instance.
            if ($copyrealm == 'public') {
                $sid = $copyid;
            } else {
                $sid = $qobject->sid = $qobject->survey_copy($course->id);
                // All new observations should be created as "private".
                // Even if they are *copies* of public or template observations.
                $DB->set_field('observation_survey', 'realm', 'private', array('id' => $sid));
            }
            // If the survey has dependency data, need to set the observation to allow dependencies.
            if ($DB->count_records('observation_dependency', ['surveyid' => $sid]) > 0) {
                $observation->navigate = 1;
            }
        }
        $observation->sid = $sid;
    }

    $observation->timemodified = time();

    // May have to add extra stuff in here.
    if (empty($observation->useopendate)) {
        $observation->opendate = 0;
    }
    if (empty($observation->useclosedate)) {
        $observation->closedate = 0;
    }

    if ($observation->resume == '1') {
        $observation->resume = 1;
    } else {
        $observation->resume = 0;
    }

    if (!$observation->id = $DB->insert_record("observation", $observation)) {
        return false;
    }

    observation_set_events($observation);

    $completiontimeexpected = !empty($observation->completionexpected) ? $observation->completionexpected : null;
    \core_completion\api::update_completion_date_event($observation->coursemodule, 'observation',
        $observation->id, $completiontimeexpected);

    return $observation->id;
}

// Given an object containing all the necessary data,
// (defined by the form in mod.html) this function
// will update an existing instance with new data.
function observation_update_instance($observation) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/observation/locallib.php');

    // Check the realm and set it to the survey if its set.
    if (!empty($observation->sid) && !empty($observation->realm)) {
        $DB->set_field('observation_survey', 'realm', $observation->realm, array('id' => $observation->sid));
    }

    $observation->timemodified = time();
    $observation->id = $observation->instance;

    // May have to add extra stuff in here.
    if (empty($observation->useopendate)) {
        $observation->opendate = 0;
    }
    if (empty($observation->useclosedate)) {
        $observation->closedate = 0;
    }

    if ($observation->resume == '1') {
        $observation->resume = 1;
    } else {
        $observation->resume = 0;
    }

    // Get existing grade item.
    observation_grade_item_update($observation);

    observation_set_events($observation);

    $completiontimeexpected = !empty($observation->completionexpected) ? $observation->completionexpected : null;
    \core_completion\api::update_completion_date_event($observation->coursemodule, 'observation',
        $observation->id, $completiontimeexpected);

    return $DB->update_record("observation", $observation);
}

// Given an ID of an instance of this module,
// this function will permanently delete the instance
// and any data that depends on it.
function observation_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/observation/locallib.php');

    if (! $observation = $DB->get_record('observation', array('id' => $id))) {
        return false;
    }

    $result = true;

    if ($events = $DB->get_records('event', array("modulename" => 'observation', "instance" => $observation->id))) {
        foreach ($events as $event) {
            $event = calendar_event::load($event);
            $event->delete();
        }
    }

    if (! $DB->delete_records('observation', array('id' => $observation->id))) {
        $result = false;
    }

    if ($survey = $DB->get_record('observation_survey', array('id' => $observation->sid))) {
        // If this survey is owned by this course, delete all of the survey records and responses.
        if ($survey->courseid == $observation->course) {
            $result = $result && observation_delete_survey($observation->sid, $observation->id);
        }
    }

    return $result;
}

// Return a small object with summary information about what a
// user has done with a given particular instance of this module
// Used for user activity reports.
// $return->time = the time they did it
// $return->info = a short text description.
/**
 * $course and $mod are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_user_outline($course, $user, $mod, $observation) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/observation/locallib.php');

    $result = new stdClass();
    if ($responses = observation_get_user_responses($observation->id, $user->id, true)) {
        $n = count($responses);
        if ($n == 1) {
            $result->info = $n.' '.get_string("response", "observation");
        } else {
            $result->info = $n.' '.get_string("responses", "observation");
        }
        $lastresponse = array_pop($responses);
        $result->time = $lastresponse->submitted;
    } else {
        $result->info = get_string("noresponses", "observation");
    }
    return $result;
}

// Print a detailed representation of what a  user has done with
// a given particular instance of this module, for user activity reports.
/**
 * $course and $mod are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_user_complete($course, $user, $mod, $observation) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/observation/locallib.php');

    if ($responses = observation_get_user_responses($observation->id, $user->id, false)) {
        foreach ($responses as $response) {
            if ($response->complete == 'y') {
                echo get_string('submitted', 'observation').' '.userdate($response->submitted).'<br />';
            } else {
                echo get_string('attemptstillinprogress', 'observation').' '.userdate($response->submitted).'<br />';
            }
        }
    } else {
        print_string('noresponses', 'observation');
    }

    return true;
}

// Given a course and a time, this module should find recent activity
// that has occurred in observation activities and print it out.
// Return true if there was output, or false is there was none.
/**
 * $course, $isteacher and $timestart are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_print_recent_activity($course, $isteacher, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

// Must return an array of grades for a given instance of this module,
// indexed by user.  It also returns a maximum allowed grade.
/**
 * $observationid is unused, but API requires it. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_grades($observationid) {
    return null;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $observationid id of assignment
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function observation_get_user_grades($observation, $userid=0) {
    global $DB;
    $params = array();
    $usersql = '';
    if (!empty($userid)) {
        $usersql = "AND u.id = ?";
        $params[] = $userid;
    }

    $sql = "SELECT r.id, u.id AS userid, r.grade AS rawgrade, r.submitted AS dategraded, r.submitted AS datesubmitted
            FROM {user} u, {observation_response} r
            WHERE u.id = r.userid AND r.observationid = $observation->id AND r.complete = 'y' $usersql";
    return $DB->get_records_sql($sql, $params);
}

/**
 * Update grades by firing grade_updated event
 *
 * @param object $assignment null means all assignments
 * @param int $userid specific user only, 0 mean all
 *
 * $nullifnone is unused, but API requires it. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_update_grades($observation=null, $userid=0, $nullifnone=true) {
    global $CFG, $DB;

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($observation != null) {
        if ($graderecs = observation_get_user_grades($observation, $userid)) {
            $grades = array();
            foreach ($graderecs as $v) {
                if (!isset($grades[$v->userid])) {
                    $grades[$v->userid] = new stdClass();
                    if ($v->rawgrade == -1) {
                        $grades[$v->userid]->rawgrade = null;
                    } else {
                        $grades[$v->userid]->rawgrade = $v->rawgrade;
                    }
                    $grades[$v->userid]->userid = $v->userid;
                } else if (isset($grades[$v->userid]) && ($v->rawgrade > $grades[$v->userid]->rawgrade)) {
                    $grades[$v->userid]->rawgrade = $v->rawgrade;
                }
            }
            observation_grade_item_update($observation, $grades);
        } else {
            observation_grade_item_update($observation);
        }

    } else {
        $sql = "SELECT q.*, cm.idnumber as cmidnumber, q.course as courseid
                  FROM {observation} q, {course_modules} cm, {modules} m
                 WHERE m.name='observation' AND m.id=cm.module AND cm.instance=q.id";
        if ($rs = $DB->get_recordset_sql($sql)) {
            foreach ($rs as $observation) {
                if ($observation->grade != 0) {
                    observation_update_grades($observation);
                } else {
                    observation_grade_item_update($observation);
                }
            }
            $rs->close();
        }
    }
}

/**
 * Create grade item for given observation
 *
 * @param object $observation object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function observation_grade_item_update($observation, $grades = null) {
    global $CFG;
    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($observation->courseid)) {
        $observation->courseid = $observation->course;
    }

    if ($observation->cmidnumber != '') {
        $params = array('itemname' => $observation->name, 'idnumber' => $observation->cmidnumber);
    } else {
        $params = array('itemname' => $observation->name);
    }

    if ($observation->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $observation->grade;
        $params['grademin']  = 0;

    } else if ($observation->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$observation->grade;

    } else if ($observation->grade == 0) { // No Grade..be sure to delete the grade item if it exists.
        $grades = null;
        $params = array('deleted' => 1);

    } else {
        $params = null; // Allow text comments only.
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/observation', $observation->courseid, 'mod', 'observation',
                    $observation->id, 0, $grades, $params);
}

/**
 * This function returns if a scale is being used by one observation
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 * @param $observationid int
 * @param $scaleid int
 * @return boolean True if the scale is used by any observation
 *
 * Function parameters are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_scale_used ($observationid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of observation
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any observation
 *
 * Function parameters are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Serves the observation attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 *
 * $forcedownload is unused, but API requires it. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $fileareas = ['intro', 'info', 'thankbody', 'question', 'feedbacknotes', 'sectionheading', 'feedback'];
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $componentid = (int)array_shift($args);

    if ($filearea == 'question') {
        if (!$DB->record_exists('observation_question', ['id' => $componentid])) {
            return false;
        }
    } else if ($filearea == 'sectionheading') {
        if (!$DB->record_exists('observation_fb_sections', ['id' => $componentid])) {
            return false;
        }
    } else if ($filearea == 'feedback') {
        if (!$DB->record_exists('observation_feedback', ['id' => $componentid])) {
            return false;
        }
    } else {
        if (!$DB->record_exists('observation_survey', ['id' => $componentid])) {
            return false;
        }
    }

    if (!$DB->record_exists('observation', ['id' => $cm->instance])) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_observation/$filearea/$componentid/$relativepath";
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true); // Download MUST be forced - security!
}
/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $observationnode The node to add module settings to
 *
 * $settings is unused, but API requires it. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_extend_settings_navigation(settings_navigation $settings,
        navigation_node $observationnode) {

    global $PAGE, $DB, $USER, $CFG;
    $individualresponse = optional_param('individualresponse', false, PARAM_INT);
    $rid = optional_param('rid', false, PARAM_INT); // Response id.
    $currentgroupid = optional_param('group', 0, PARAM_INT); // Group id.

    require_once($CFG->dirroot.'/mod/observation/observation.class.php');

    $context = $PAGE->cm->context;
    $cmid = $PAGE->cm->id;
    $cm = $PAGE->cm;
    $course = $PAGE->course;

    if (! $observation = $DB->get_record("observation", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }

    $courseid = $course->id;
    $observation = new observation(0, $observation, $course, $cm);

    if ($owner = $DB->get_field('observation_survey', 'courseid', ['id' => $observation->sid])) {
        $owner = (trim($owner) == trim($courseid));
    } else {
        $owner = true;
    }

    // On view page, currentgroupid is not yet sent as an optional_param, so get it.
    $groupmode = groups_get_activity_groupmode($cm, $course);
    if ($groupmode > 0 && $currentgroupid == 0) {
        $currentgroupid = groups_get_activity_group($observation->cm);
        if (!groups_is_member($currentgroupid, $USER->id)) {
            $currentgroupid = 0;
        }
    }

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $observationnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if (($i === false) && array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/observation:manage', $context) && $owner) {
        $url = '/mod/observation/qsettings.php';
        $node = navigation_node::create(get_string('advancedsettings'),
            new moodle_url($url, array('id' => $cmid)),
            navigation_node::TYPE_SETTING, null, 'advancedsettings',
            new pix_icon('t/edit', ''));
        $observationnode->add_node($node, $beforekey);
    }

    if (has_capability('mod/observation:editquestions', $context) && $owner) {
        $url = '/mod/observation/questions.php';
        $node = navigation_node::create(get_string('questions', 'observation'),
            new moodle_url($url, array('id' => $cmid)),
            navigation_node::TYPE_SETTING, null, 'questions',
            new pix_icon('t/edit', ''));
        $observationnode->add_node($node, $beforekey);
    }

    if (has_capability('mod/observation:editquestions', $context) && $owner) {
        $url = '/mod/observation/feedback.php';
        $node = navigation_node::create(get_string('feedback', 'observation'),
            new moodle_url($url, array('id' => $cmid)),
            navigation_node::TYPE_SETTING, null, 'feedback',
            new pix_icon('t/edit', ''));
        $observationnode->add_node($node, $beforekey);
    }

    if (has_capability('mod/observation:preview', $context)) {
        $url = '/mod/observation/preview.php';
        $node = navigation_node::create(get_string('preview_label', 'observation'),
            new moodle_url($url, array('id' => $cmid)),
            navigation_node::TYPE_SETTING, null, 'preview',
            new pix_icon('t/preview', ''));
        $observationnode->add_node($node, $beforekey);
    }

    if ($observation->user_can_take($USER->id)) {
        $url = '/mod/observation/complete.php';
        if ($observation->user_has_saved_response($USER->id)) {
            $args = ['id' => $cmid, 'resume' => 1];
            $text = get_string('resumesurvey', 'observation');
        } else {
            $args = ['id' => $cmid];
            $text = get_string('answerquestions', 'observation');
        }
        $node = navigation_node::create($text, new moodle_url($url, $args),
            navigation_node::TYPE_SETTING, null, '', new pix_icon('i/info', 'answerquestions'));
        $observationnode->add_node($node, $beforekey);
    }
    $usernumresp = $observation->count_submissions($USER->id);

    if ($observation->capabilities->readownresponses && ($usernumresp > 0)) {
        $url = '/mod/observation/myreport.php';

        if ($usernumresp > 1) {
            $urlargs = array('instance' => $observation->id, 'userid' => $USER->id,
                'byresponse' => 0, 'action' => 'summary', 'group' => $currentgroupid);
            $node = navigation_node::create(get_string('yourresponses', 'observation'),
                new moodle_url($url, $urlargs), navigation_node::TYPE_SETTING, null, 'yourresponses');
            $myreportnode = $observationnode->add_node($node, $beforekey);

            $urlargs = array('instance' => $observation->id, 'userid' => $USER->id,
                'byresponse' => 0, 'action' => 'summary', 'group' => $currentgroupid);
            $myreportnode->add(get_string('summary', 'observation'), new moodle_url($url, $urlargs));

            $urlargs = array('instance' => $observation->id, 'userid' => $USER->id,
                'byresponse' => 1, 'action' => 'vresp', 'group' => $currentgroupid);
            $byresponsenode = $myreportnode->add(get_string('viewindividualresponse', 'observation'),
                new moodle_url($url, $urlargs));

            $urlargs = array('instance' => $observation->id, 'userid' => $USER->id,
                'byresponse' => 0, 'action' => 'vall', 'group' => $currentgroupid);
            $myreportnode->add(get_string('myresponses', 'observation'), new moodle_url($url, $urlargs));
            if ($observation->capabilities->downloadresponses) {
                $urlargs = array('instance' => $observation->id, 'user' => $USER->id,
                    'action' => 'dwnpg', 'group' => $currentgroupid);
                $myreportnode->add(get_string('downloadtextformat', 'observation'),
                    new moodle_url('/mod/observation/report.php', $urlargs));
            }
        } else {
            $urlargs = array('instance' => $observation->id, 'userid' => $USER->id,
                'byresponse' => 1, 'action' => 'vresp', 'group' => $currentgroupid);
            $node = navigation_node::create(get_string('yourresponse', 'observation'),
                new moodle_url($url, $urlargs), navigation_node::TYPE_SETTING, null, 'yourresponse');
            $myreportnode = $observationnode->add_node($node, $beforekey);
        }
    }

    // If observation is set to separate groups, prevent user who is not member of any group
    // and is not a non-editing teacher to view All responses.
    if ($observation->can_view_all_responses($usernumresp)) {

        $url = '/mod/observation/report.php';
        $node = navigation_node::create(get_string('viewallresponses', 'observation'),
            new moodle_url($url, array('instance' => $observation->id, 'action' => 'vall')),
            navigation_node::TYPE_SETTING, null, 'vall');
        $reportnode = $observationnode->add_node($node, $beforekey);

        if ($observation->capabilities->viewsingleresponse) {
            $summarynode = $reportnode->add(get_string('summary', 'observation'),
                new moodle_url('/mod/observation/report.php',
                    array('instance' => $observation->id, 'action' => 'vall')));
        } else {
            $summarynode = $reportnode;
        }
        $summarynode->add(get_string('order_default', 'observation'),
            new moodle_url('/mod/observation/report.php',
                array('instance' => $observation->id, 'action' => 'vall', 'group' => $currentgroupid)));
        $summarynode->add(get_string('order_ascending', 'observation'),
            new moodle_url('/mod/observation/report.php',
                array('instance' => $observation->id, 'action' => 'vallasort', 'group' => $currentgroupid)));
        $summarynode->add(get_string('order_descending', 'observation'),
            new moodle_url('/mod/observation/report.php',
                array('instance' => $observation->id, 'action' => 'vallarsort', 'group' => $currentgroupid)));

        if ($observation->capabilities->deleteresponses) {
            $summarynode->add(get_string('deleteallresponses', 'observation'),
                new moodle_url('/mod/observation/report.php',
                    array('instance' => $observation->id, 'action' => 'delallresp', 'group' => $currentgroupid)));
        }

        if ($observation->capabilities->downloadresponses) {
            $summarynode->add(get_string('downloadtextformat', 'observation'),
                new moodle_url('/mod/observation/report.php',
                    array('instance' => $observation->id, 'action' => 'dwnpg', 'group' => $currentgroupid)));
        }
        if ($observation->capabilities->viewsingleresponse) {
            $byresponsenode = $reportnode->add(get_string('viewbyresponse', 'observation'),
                new moodle_url('/mod/observation/report.php',
                    array('instance' => $observation->id, 'action' => 'vresp', 'byresponse' => 1, 'group' => $currentgroupid)));

            $byresponsenode->add(get_string('view', 'observation'),
                new moodle_url('/mod/observation/report.php',
                    array('instance' => $observation->id, 'action' => 'vresp', 'byresponse' => 1, 'group' => $currentgroupid)));

            if ($individualresponse) {
                $byresponsenode->add(get_string('deleteresp', 'observation'),
                    new moodle_url('/mod/observation/report.php',
                        array('instance' => $observation->id, 'action' => 'dresp', 'byresponse' => 1,
                            'rid' => $rid, 'group' => $currentgroupid, 'individualresponse' => 1)));
            }
        }
    }

    $canviewgroups = true;
    $groupmode = groups_get_activity_groupmode($cm, $course);
    if ($groupmode == 1) {
        $canviewgroups = groups_has_membership($cm, $USER->id);
    }
    $canviewallgroups = has_capability('moodle/site:accessallgroups', $context);
    if ($observation->capabilities->viewsingleresponse && ($canviewallgroups || $canviewgroups)) {
        $url = '/mod/observation/show_nonrespondents.php';
        $node = navigation_node::create(get_string('show_nonrespondents', 'observation'),
            new moodle_url($url, array('id' => $cmid)),
            navigation_node::TYPE_SETTING, null, 'nonrespondents');
        $observationnode->add_node($node, $beforekey);

    }
}

// Any other observation functions go here.  Each of them must have a name that
// starts with observation_.

function observation_get_view_actions() {
    return array('view', 'view all');
}

function observation_get_post_actions() {
    return array('submit', 'update');
}

function observation_get_recent_mod_activity(&$activities, &$index, $timestart,
                $courseid, $cmid, $userid = 0, $groupid = 0) {

    global $CFG, $COURSE, $USER, $DB;
    require_once($CFG->dirroot . '/mod/observation/locallib.php');
    require_once($CFG->dirroot.'/mod/observation/observation.class.php');

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', ['id' => $courseid]);
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];
    $observation = $DB->get_record('observation', ['id' => $cm->instance]);
    $observation = new observation(0, $observation, $course, $cm);

    $context = context_module::instance($cm->id);
    $grader = has_capability('mod/observation:viewsingleresponse', $context);

    // If this is a copy of a public observation whose original is located in another course,
    // current user (teacher) cannot view responses.
    if ($grader) {
        // For a public observation, look for the original public observation that it is based on.
        if (!$observation->survey_is_public_master()) {
            // For a public observation, look for the original public observation that it is based on.
            $originalobservation = $DB->get_record('observation',
                ['sid' => $observation->survey->id, 'course' => $observation->survey->courseid]);
            $cmoriginal = get_coursemodule_from_instance("observation", $originalobservation->id,
                $observation->survey->courseid);
            $contextoriginal = context_course::instance($observation->survey->courseid, MUST_EXIST);
            if (!has_capability('mod/observation:viewsingleresponse', $contextoriginal)) {
                $tmpactivity = new stdClass();
                $tmpactivity->type = 'observation';
                $tmpactivity->cmid = $cm->id;
                $tmpactivity->cannotview = true;
                $tmpactivity->anonymous = false;
                $activities[$index++] = $tmpactivity;
                return $activities;
            }
        }
    }

    if ($userid) {
        $userselect = "AND u.id = :userid";
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin   = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin   = '';
    }

    $params['timestart'] = $timestart;
    $params['observationid'] = $observation->id;

    $ufields = user_picture::fields('u', null, 'useridagain');
    if (!$attempts = $DB->get_records_sql("
                    SELECT qr.*,
                    {$ufields}
                    FROM {observation_response} qr
                    JOIN {user} u ON u.id = qr.userid
                    $groupjoin
                    WHERE qr.submitted > :timestart
                    AND qr.observationid = :observationid
                    $userselect
                    $groupselect
                    ORDER BY qr.submitted ASC", $params)) {
        return;
    }

    $accessallgroups = has_capability('moodle/site:accessallgroups', $context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $context);
    $groupmode       = groups_get_activity_groupmode($cm, $course);

    $usersgroups = null;
    $aname = format_string($cm->name, true);
    $userattempts = array();
    foreach ($attempts as $attempt) {
        if ($observation->respondenttype != 'anonymous') {
            if (!isset($userattempts[$attempt->lastname])) {
                $userattempts[$attempt->lastname] = 1;
            } else {
                $userattempts[$attempt->lastname]++;
            }
        }
        if ($attempt->userid != $USER->id) {
            if (!$grader) {
                // View complete individual responses permission required.
                continue;
            }

            if (($groupmode == SEPARATEGROUPS) && !$accessallgroups) {
                if ($usersgroups === null) {
                    $usersgroups = groups_get_all_groups($course->id,
                    $attempt->userid, $cm->groupingid);
                    if (is_array($usersgroups)) {
                        $usersgroups = array_keys($usersgroups);
                    } else {
                         $usersgroups = array();
                    }
                }
                if (!array_intersect($usersgroups, $modinfo->groups[$cm->id])) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();

        $tmpactivity->type       = 'observation';
        $tmpactivity->cmid       = $cm->id;
        $tmpactivity->cminstance = $cm->instance;
        // Current user is admin - or teacher enrolled in original public course.
        if (isset($cmoriginal)) {
            $tmpactivity->cminstance = $cmoriginal->instance;
        }
        $tmpactivity->cannotview = false;
        $tmpactivity->anonymous  = false;
        $tmpactivity->name       = $aname;
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp  = $attempt->submitted;
        $tmpactivity->groupid    = $groupid;
        if (isset($userattempts[$attempt->lastname])) {
            $tmpactivity->nbattempts = $userattempts[$attempt->lastname];
        }

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->attemptid = $attempt->id;

        $userfields = explode(',', user_picture::fields());
        $tmpactivity->user = new stdClass();
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                $tmpactivity->user->{$userfield} = $attempt->userid;
            } else {
                if (!empty($attempt->{$userfield})) {
                    $tmpactivity->user->{$userfield} = $attempt->{$userfield};
                } else {
                    $tmpactivity->user->{$userfield} = null;
                }
            }
        }
        if ($observation->respondenttype != 'anonymous') {
            $tmpactivity->user->fullname  = fullname($attempt, $viewfullnames);
        } else {
            $tmpactivity->user = '';
            unset ($tmpactivity->user);
            $tmpactivity->anonymous = true;
        }
        $activities[$index++] = $tmpactivity;
    }
}

/**
 * Prints all users who have completed a specified observation since a given time
 *
 * @global object
 * @param object $activity
 * @param int $courseid
 * @param string $detail not used but needed for compability
 * @param array $modnames
 * @return void Output is echo'd
 *
 * $details and $modenames are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $OUTPUT;

    // If the observation is "anonymous", then $activity->user won't have been set, so do not display respondent info.
    if ($activity->anonymous) {
        $stranonymous = ' ('.get_string('anonymous', 'observation').')';
        $activity->nbattempts = '';
    } else {
        $stranonymous = '';
    }
    // Current user cannot view responses to public observation.
    if ($activity->cannotview) {
        $strcannotview = get_string('cannotviewpublicresponses', 'observation');
    }
    echo html_writer::start_tag('div');
    echo html_writer::start_tag('span', array('class' => 'clearfix',
                    'style' => 'margin-top:0px; background-color: white; display: inline-block;'));

    if (!$activity->anonymous && !$activity->cannotview) {
        echo html_writer::tag('div', $OUTPUT->user_picture($activity->user, array('courseid' => $courseid)),
                        array('style' => 'float: left; padding-right: 10px;'));
    }
    if (!$activity->cannotview) {
        echo html_writer::start_tag('div');
        echo html_writer::start_tag('div');

        $urlparams = array('action' => 'vresp', 'instance' => $activity->cminstance,
                        'group' => $activity->groupid, 'rid' => $activity->content->attemptid, 'individualresponse' => 1);

        $context = context_module::instance($activity->cmid);
        if (has_capability('mod/observation:viewsingleresponse', $context)) {
            $report = 'report.php';
        } else {
            $report = 'myreport.php';
        }
        echo html_writer::tag('a', get_string('response', 'observation').' '.$activity->nbattempts.$stranonymous,
                        array('href' => new moodle_url('/mod/observation/'.$report, $urlparams)));
        echo html_writer::end_tag('div');
    } else {
        echo html_writer::start_tag('div');
        echo html_writer::start_tag('div');
        echo html_writer::tag('div', $strcannotview);
        echo html_writer::end_tag('div');
    }
    if (!$activity->anonymous  && !$activity->cannotview) {
        $url = new moodle_url('/user/view.php', array('course' => $courseid, 'id' => $activity->user->id));
        $name = $activity->user->fullname;
        $link = html_writer::link($url, $name);
        echo html_writer::start_tag('div', array('class' => 'user'));
        echo $link .' - '. userdate($activity->timestamp);
        echo html_writer::end_tag('div');
    }

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('span');
    echo html_writer::end_tag('div');

    return;
}

/**
 * Prints observation summaries on 'My home' page
 *
 * Prints observation name, due date and attempt information on
 * observations that have a deadline that has not already passed
 * and it is available for taking.
 *
 * @global object
 * @global stdClass
 * @global object
 * @uses CONTEXT_MODULE
 * @param array $courses An array of course objects to get observation instances from
 * @param array $htmlarray Store overview output array( course ID => 'observation' => HTML output )
 * @return void
 */
function observation_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB, $OUTPUT;

    require_once($CFG->dirroot . '/mod/observation/locallib.php');

    if (!$observations = get_all_instances_in_courses('observation', $courses)) {
        return;
    }

    // Get Necessary Strings.
    $strobservation       = get_string('modulename', 'observation');
    $strnotattempted = get_string('noattempts', 'observation');
    $strattempted    = get_string('attempted', 'observation');
    $strsavedbutnotsubmitted = get_string('savedbutnotsubmitted', 'observation');

    $now = time();
    foreach ($observations as $observation) {

        // The observation has a deadline.
        if (($observation->closedate != 0)
                        // And it is before the deadline has been met.
                        && ($observation->closedate >= $now)
                        // And the observation is available.
                        && (($observation->opendate == 0) || ($observation->opendate <= $now))) {
            if (!$observation->visible) {
                $class = ' class="dimmed"';
            } else {
                $class = '';
            }
            $str = $OUTPUT->box("$strobservation:
                            <a$class href=\"$CFG->wwwroot/mod/observation/view.php?id=$observation->coursemodule\">".
                            format_string($observation->name).'</a>', 'name');

            // Deadline.
            $str .= $OUTPUT->box(get_string('closeson', 'observation', userdate($observation->closedate)), 'info');
            $attempts = $DB->get_records('observation_response',
                ['observationid' => $observation->id, 'userid' => $USER->id, 'complete' => 'y']);
            $nbattempts = count($attempts);

            // Do not display a observation as due if it can only be sumbitted once and it has already been submitted!
            if ($nbattempts != 0 && $observation->qtype == OBSERVATIONONCE) {
                continue;
            }

            // Attempt information.
            if (has_capability('mod/observation:manage', context_module::instance($observation->coursemodule))) {
                // Number of user attempts.
                $attempts = $DB->count_records('observation_response',
                    ['observationid' => $observation->id, 'complete' => 'y']);
                $str .= $OUTPUT->box(get_string('numattemptsmade', 'observation', $attempts), 'info');
            } else {
                if ($responses = observation_get_user_responses($observation->id, $USER->id, false)) {
                    foreach ($responses as $response) {
                        if ($response->complete == 'y') {
                            $str .= $OUTPUT->box($strattempted, 'info');
                            break;
                        } else {
                            $str .= $OUTPUT->box($strsavedbutnotsubmitted, 'info');
                        }
                    }
                } else {
                    $str .= $OUTPUT->box($strnotattempted, 'info');
                }
            }
            $str = $OUTPUT->box($str, 'observation overview');

            if (empty($htmlarray[$observation->course]['observation'])) {
                $htmlarray[$observation->course]['observation'] = $str;
            } else {
                $htmlarray[$observation->course]['observation'] .= $str;
            }
        }
    }
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the observation.
 *
 * @param $mform the course reset form that is being built.
 */
function observation_reset_course_form_definition($mform) {
    $mform->addElement('header', 'observationheader', get_string('modulenameplural', 'observation'));
    $mform->addElement('advcheckbox', 'reset_observation',
                    get_string('removeallobservationattempts', 'observation'));
}

/**
 * Course reset form defaults.
 * @return array the defaults.
 *
 * Function parameters are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_reset_course_form_defaults($course) {
    return array('reset_observation' => 1);
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * observation responses for course $data->courseid.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function observation_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot.'/mod/observation/locallib.php');

    $componentstr = get_string('modulenameplural', 'observation');
    $status = array();

    if (!empty($data->reset_observation)) {
        $surveys = observation_get_survey_list($data->courseid, '');

        // Delete responses.
        foreach ($surveys as $survey) {
            // Get all responses for this observation.
            $sql = "SELECT qr.id, qr.observationid, qr.submitted, qr.userid, q.sid
                 FROM {observation} q
                 INNER JOIN {observation_response} qr ON q.id = qr.observationid
                 WHERE q.sid = ?
                 ORDER BY qr.id";
            $resps = $DB->get_records_sql($sql, [$survey->id]);
            if (!empty($resps)) {
                $observation = $DB->get_record("observation", ["sid" => $survey->id, "course" => $survey->courseid]);
                $observation->course = $DB->get_record("course", array("id" => $observation->course));
                foreach ($resps as $response) {
                    observation_delete_response($response, $observation);
                }
            }
            // Remove this observation's grades (and feedback) from gradebook (if any).
            $select = "itemmodule = 'observation' AND iteminstance = ".$survey->qid;
            $fields = 'id';
            if ($itemid = $DB->get_record_select('grade_items', $select, null, $fields)) {
                $itemid = $itemid->id;
                $DB->delete_records_select('grade_grades', 'itemid = '.$itemid);

            }
        }
        $status[] = array(
                        'component' => $componentstr,
                        'item' => get_string('deletedallresp', 'observation'),
                        'error' => false);

        $status[] = array(
                        'component' => $componentstr,
                        'item' => get_string('gradesdeleted', 'observation'),
                        'error' => false);
    }
    return $status;
}

/**
 * Obtains the automatic completion state for this observation based on the condition
 * in observation settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 *
 * $course is unused, but API requires it. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function observation_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get observation details.
    $observation = $DB->get_record('observation', array('id' => $cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false.
    if ($observation->completionsubmit) {
        $params = ['userid' => $userid, 'observationid' => $observation->id, 'complete' => 'y'];
        return $DB->record_exists('observation_response', $params);
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_observation_core_calendar_provide_event_action(calendar_event $event,
                                                            \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['observation'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
            get_string('view'),
            new \moodle_url('/mod/observation/view.php', ['id' => $cm->id]),
            1,
            true
    );
}

