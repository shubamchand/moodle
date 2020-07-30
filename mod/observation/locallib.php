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
 * This library replaces the phpESP application with Moodle specific code. It will eventually
 * replace all of the phpESP application, removing the dependency on that.
 */

/**
 * Updates the contents of the survey with the provided data. If no data is provided,
 * it checks for posted data.
 *
 * @param int $surveyid The id of the survey to update.
 * @param string $old_tab The function that was being executed.
 * @param object $sdata The data to update the survey with.
 *
 * @return string|boolean The function to go to, or false on error.
 *
 */

/**
 * @package mod_observation
 * @copyright  2016 Mike Churchward (mike.churchward@poetgroup.org)
 * @author     Mike Churchward
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/calendar/lib.php');
// Constants.

define ('OBSERVATIONUNLIMITED', 0);
define ('OBSERVATIONONCE', 1);
define ('OBSERVATIONDAILY', 2);
define ('OBSERVATIONWEEKLY', 3);
define ('OBSERVATIONMONTHLY', 4);

define ('OBSERVATION_STUDENTVIEWRESPONSES_NEVER', 0);
define ('OBSERVATION_STUDENTVIEWRESPONSES_WHENANSWERED', 1);
define ('OBSERVATION_STUDENTVIEWRESPONSES_WHENCLOSED', 2);
define ('OBSERVATION_STUDENTVIEWRESPONSES_ALWAYS', 3);

define('OBSERVATION_MAX_EVENT_LENGTH', 5 * 24 * 60 * 60);   // 5 days maximum.

define('OBSERVATION_DEFAULT_PAGE_COUNT', 20);

global $observationtypes;
$observationtypes = array (OBSERVATIONUNLIMITED => get_string('qtypeunlimited', 'observation'),
                              OBSERVATIONONCE => get_string('qtypeonce', 'observation'),
                              OBSERVATIONDAILY => get_string('qtypedaily', 'observation'),
                              OBSERVATIONWEEKLY => get_string('qtypeweekly', 'observation'),
                              OBSERVATIONMONTHLY => get_string('qtypemonthly', 'observation'));

global $observationrespondents;
$observationrespondents = array ('fullname' => get_string('respondenttypefullname', 'observation'),
                                    'anonymous' => get_string('respondenttypeanonymous', 'observation'));

global $observationrealms;
$observationrealms = array ('private' => get_string('private', 'observation'),
                               'public' => get_string('public', 'observation'),
                               'template' => get_string('template', 'observation'));

global $observationresponseviewers;
$observationresponseviewers = array (
            OBSERVATION_STUDENTVIEWRESPONSES_WHENANSWERED => get_string('responseviewstudentswhenanswered', 'observation'),
            OBSERVATION_STUDENTVIEWRESPONSES_WHENCLOSED => get_string('responseviewstudentswhenclosed', 'observation'),
            OBSERVATION_STUDENTVIEWRESPONSES_ALWAYS => get_string('responseviewstudentsalways', 'observation'),
            OBSERVATION_STUDENTVIEWRESPONSES_NEVER => get_string('responseviewstudentsnever', 'observation'));

global $autonumbering;
$autonumbering = array (0 => get_string('autonumberno', 'observation'),
        1 => get_string('autonumberquestions', 'observation'),
        2 => get_string('autonumberpages', 'observation'),
        3 => get_string('autonumberpagesandquestions', 'observation'));

function observation_choice_values($content) {

    // If we run the content through format_text first, any filters we want to use (e.g. multilanguage) should work.
    // examines the content of a possible answer from radio button, check boxes or rate question
    // returns ->text to be displayed, ->image if present, ->modname name of modality, image ->title.
    $contents = new stdClass();
    $contents->text = '';
    $contents->image = '';
    $contents->modname = '';
    $contents->title = '';
    // Has image.
    if (preg_match('/(<img)\s .*(src="(.[^"]{1,})")/isxmU', $content, $matches)) {
        $contents->image = $matches[0];
        $imageurl = $matches[3];
        // Image has a title or alt text: use one of them.
        if (preg_match('/(title=.)([^"]{1,})/', $content, $matches)
             || preg_match('/(alt=.)([^"]{1,})/', $content, $matches) ) {
            $contents->title = $matches[2];
        } else {
            // Image has no title nor alt text: use its filename (without the extension).
            preg_match("/.*\/(.*)\..*$/", $imageurl, $matches);
            $contents->title = $matches[1];
        }
        // Content has text or named modality plus an image.
        if (preg_match('/(.*)(<img.*)/', $content, $matches)) {
            $content = $matches[1];
        } else {
            // Just an image.
            return $contents;
        }
    }

    // Check for score value first (used e.g. by personality test feature).
    $r = preg_match_all("/^(\d{1,2}=)(.*)$/", $content, $matches);
    if ($r) {
        $content = $matches[2][0];
    }

    // Look for named modalities.
    $contents->text = $content;
    // DEV JR from version 2.5, a double colon :: must be used here instead of the equal sign.
    if ($pos = strpos($content, '::')) {
        $contents->text = substr($content, $pos + 2);
        $contents->modname = substr($content, 0, $pos);
    }
    return $contents;
}

/**
 * Get the information about the standard observation JavaScript module.
 * @return array a standard jsmodule structure.
 */
function observation_get_js_module() {
    return array(
            'name' => 'mod_observation',
            'fullpath' => '/mod/observation/module.js',
            'requires' => array('base', 'dom', 'event-delegate', 'event-key',
                    'core_question_engine', 'moodle-core-formchangechecker'),
            'strings' => array(
                    array('cancel', 'moodle'),
                    array('flagged', 'question'),
                    array('functiondisabledbysecuremode', 'quiz'),
                    array('startattempt', 'quiz'),
                    array('timesup', 'quiz'),
                    array('changesmadereallygoaway', 'moodle'),
            ),
    );
}

/**
 * Get all the observation responses for a user
 */
function observation_get_user_responses($observationid, $userid, $complete=true) {
    global $DB;
    $andcomplete = '';
    if ($complete) {
        $andcomplete = " AND complete = 'y' ";
    }
    return $DB->get_records_sql ("SELECT *
        FROM {observation_response}
        WHERE observationid = ?
        AND userid = ?
        ".$andcomplete."
        ORDER BY submitted ASC ", array($observationid, $userid));
}

/**
 * get the capabilities for the observation
 * @param int $cmid
 * @return object the available capabilities from current user
 */
function observation_load_capabilities($cmid) {
    static $cb;

    if (isset($cb)) {
        return $cb;
    }

    $context = observation_get_context($cmid);

    $cb = new stdClass();
    $cb->view                   = has_capability('mod/observation:view', $context);
    $cb->submit                 = has_capability('mod/observation:submit', $context);
    $cb->viewsingleresponse     = has_capability('mod/observation:viewsingleresponse', $context);
    $cb->submissionnotification = has_capability('mod/observation:submissionnotification', $context);
    $cb->downloadresponses      = has_capability('mod/observation:downloadresponses', $context);
    $cb->deleteresponses        = has_capability('mod/observation:deleteresponses', $context);
    $cb->manage                 = has_capability('mod/observation:manage', $context);
    $cb->editquestions          = has_capability('mod/observation:editquestions', $context);
    $cb->createtemplates        = has_capability('mod/observation:createtemplates', $context);
    $cb->createpublic           = has_capability('mod/observation:createpublic', $context);
    $cb->readownresponses       = has_capability('mod/observation:readownresponses', $context);
    $cb->readallresponses       = has_capability('mod/observation:readallresponses', $context);
    $cb->readallresponseanytime = has_capability('mod/observation:readallresponseanytime', $context);
    $cb->printblank             = has_capability('mod/observation:printblank', $context);
    $cb->preview                = has_capability('mod/observation:preview', $context);

    $cb->viewhiddenactivities   = has_capability('moodle/course:viewhiddenactivities', $context, null, false);

    return $cb;
}

/**
 * returns the context-id related to the given coursemodule-id
 * @param int $cmid the coursemodule-id
 * @return object $context
 */
function observation_get_context($cmid) {
    static $context;

    if (isset($context)) {
        return $context;
    }

    if (!$context = context_module::instance($cmid)) {
            print_error('badcontext');
    }
    return $context;
}

// This function *really* shouldn't be needed, but since sometimes we can end up with
// orphaned surveys, this will clean them up.
function observation_cleanup() {
    global $DB;

    // Find surveys that don't have observations associated with them.
    $sql = 'SELECT qs.* FROM {observation_survey} qs '.
           'LEFT JOIN {observation} q ON q.sid = qs.id '.
           'WHERE q.sid IS NULL';

    if ($surveys = $DB->get_records_sql($sql)) {
        foreach ($surveys as $survey) {
            observation_delete_survey($survey->id, 0);
        }
    }
    // Find deleted questions and remove them from database (with their associated choices, etc.).
    return true;
}

function observation_delete_survey($sid, $observationid) {
    global $DB;
    $status = true;
    // Delete all survey attempts and responses.
    if ($responses = $DB->get_records('observation_response', ['observationid' => $observationid], 'id')) {
        foreach ($responses as $response) {
            $status = $status && observation_delete_response($response);
        }
    }

    // There really shouldn't be any more, but just to make sure...
    $DB->delete_records('observation_response', ['observationid' => $observationid]);

    // Delete all question data for the survey.
    if ($questions = $DB->get_records('observation_question', ['surveyid' => $sid], 'id')) {
        foreach ($questions as $question) {
            $DB->delete_records('observation_quest_choice', ['question_id' => $question->id]);
            observation_delete_dependencies($question->id);
        }
        $status = $status && $DB->delete_records('observation_question', ['surveyid' => $sid]);
        // Just to make sure.
        $status = $status && $DB->delete_records('observation_dependency', ['surveyid' => $sid]);
    }

    // Delete all feedback sections and feedback messages for the survey.
    if ($fbsections = $DB->get_records('observation_fb_sections', ['surveyid' => $sid], 'id')) {
        foreach ($fbsections as $fbsection) {
            $DB->delete_records('observation_feedback', ['sectionid' => $fbsection->id]);
        }
        $status = $status && $DB->delete_records('observation_fb_sections', ['surveyid' => $sid]);
    }

    $status = $status && $DB->delete_records('observation_survey', ['id' => $sid]);

    return $status;
}

function observation_delete_response($response, $observation='') {
    global $DB;
    $status = true;
    $cm = '';
    $rid = $response->id;
    // The observation_delete_survey function does not send the observation array.
    if ($observation != '') {
        $cm = get_coursemodule_from_instance("observation", $observation->id, $observation->course->id);
    }

    // Delete all of the response data for a response.
    $DB->delete_records('observation_response_bool', array('response_id' => $rid));
    $DB->delete_records('observation_response_date', array('response_id' => $rid));
    $DB->delete_records('observation_resp_multiple', array('response_id' => $rid));
    $DB->delete_records('observation_response_other', array('response_id' => $rid));
    $DB->delete_records('observation_response_rank', array('response_id' => $rid));
    $DB->delete_records('observation_resp_single', array('response_id' => $rid));
    $DB->delete_records('observation_response_text', array('response_id' => $rid));

    $status = $status && $DB->delete_records('observation_response', array('id' => $rid));

    if ($status && $cm) {
        // Update completion state if necessary.
        $completion = new completion_info($observation->course);
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $observation->completionsubmit) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $response->userid);
        }
    }

    return $status;
}

function observation_delete_responses($qid) {
    global $DB;

    // Delete all of the response data for a question.
    $DB->delete_records('observation_response_bool', ['question_id' => $qid]);
    $DB->delete_records('observation_response_date', ['question_id' => $qid]);
    $DB->delete_records('observation_resp_multiple', ['question_id' => $qid]);
    $DB->delete_records('observation_response_other', ['question_id' => $qid]);
    $DB->delete_records('observation_response_rank', ['question_id' => $qid]);
    $DB->delete_records('observation_resp_single', ['question_id' => $qid]);
    $DB->delete_records('observation_response_text', ['question_id' => $qid]);

    return true;
}

function observation_delete_dependencies($qid) {
    global $DB;

    // Delete all dependencies for this question.
    $DB->delete_records('observation_dependency', ['questionid' => $qid]);
    $DB->delete_records('observation_dependency', ['dependquestionid' => $qid]);

    return true;
}

function observation_get_survey_list($courseid=0, $type='') {
    global $DB;

    if ($courseid == 0) {
        if (isadmin()) {
            $sql = "SELECT id,name,courseid,realm,status " .
                   "{observation_survey} " .
                   "ORDER BY realm,name ";
            $params = null;
        } else {
            return false;
        }
    } else {
        if ($type == 'public') {
            $sql = "SELECT s.id,s.name,s.courseid,s.realm,s.status,s.title,q.id as qid,q.name as qname " .
                   "FROM {observation} q " .
                   "INNER JOIN {observation_survey} s ON s.id = q.sid AND s.courseid = q.course " .
                   "WHERE realm = ? " .
                   "ORDER BY realm,name ";
            $params = [$type];
        } else if ($type == 'template') {
            $sql = "SELECT s.id,s.name,s.courseid,s.realm,s.status,s.title,q.id as qid,q.name as qname " .
                   "FROM {observation} q " .
                   "INNER JOIN {observation_survey} s ON s.id = q.sid AND s.courseid = q.course " .
                   "WHERE (realm = ?) " .
                   "ORDER BY realm,name ";
            $params = [$type];
        } else if ($type == 'private') {
            $sql = "SELECT s.id,s.name,s.courseid,s.realm,s.status,q.id as qid,q.name as qname " .
                "FROM {observation} q " .
                "INNER JOIN {observation_survey} s ON s.id = q.sid " .
                "WHERE s.courseid = ? and realm = ? " .
                "ORDER BY realm,name ";
            $params = [$courseid, $type];

        } else {
            // Current get_survey_list is called from function observation_reset_userdata so we need to get a
            // complete list of all observations in current course to reset them.
            $sql = "SELECT s.id,s.name,s.courseid,s.realm,s.status,q.id as qid,q.name as qname " .
                   "FROM {observation} q " .
                    "INNER JOIN {observation_survey} s ON s.id = q.sid AND s.courseid = q.course " .
                   "WHERE s.courseid = ? " .
                   "ORDER BY realm,name ";
            $params = [$courseid];
        }
    }
    return $DB->get_records_sql($sql, $params);
}

function observation_get_survey_select($courseid=0, $type='') {
    global $OUTPUT, $DB;

    $surveylist = array();

    if ($surveys = observation_get_survey_list($courseid, $type)) {
        $strpreview = get_string('preview_observation', 'observation');
        foreach ($surveys as $survey) {
            $originalcourse = $DB->get_record('course', ['id' => $survey->courseid]);
            if (!$originalcourse) {
                // This should not happen, but we found a case where a public survey
                // still existed in a course that had been deleted, and so this
                // code lead to a notice, and a broken link. Since that is useless
                // we just skip surveys like this.
                continue;
            }

            // Prevent creating a copy of a public observation IN THE SAME COURSE as the original.
            if (($type == 'public') && ($survey->courseid == $courseid)) {
                continue;
            } else {
                $args = "sid={$survey->id}&popup=1";
                if (!empty($survey->qid)) {
                    $args .= "&qid={$survey->qid}";
                }
                $link = new moodle_url("/mod/observation/preview.php?{$args}");
                $action = new popup_action('click', $link);
                $label = $OUTPUT->action_link($link, $survey->qname.' ['.$originalcourse->fullname.']',
                    $action, array('title' => $strpreview));
                $surveylist[$type.'-'.$survey->id] = $label;
            }
        }
    }
    return $surveylist;
}

function observation_get_type ($id) {
    switch ($id) {
        case 1:
            return get_string('yesno', 'observation');
        case 2:
            return get_string('textbox', 'observation');
        case 3:
            return get_string('essaybox', 'observation');
        case 4:
            return get_string('radiobuttons', 'observation');
        case 5:
            return get_string('checkboxes', 'observation');
        case 6:
            return get_string('dropdown', 'observation');
        case 8:
            return get_string('ratescale', 'observation');
        case 9:
            return get_string('date', 'observation');
        case 10:
            return get_string('numeric', 'observation');
        case 100:
            return get_string('sectiontext', 'observation');
        case 99:
            return get_string('sectionbreak', 'observation');
        default:
        return $id;
    }
}

/**
 * This creates new events given as opendate and closedate by $observation.
 * @param object $observation
 * @return void
 */
 /* added by JR 16 march 2009 based on lesson_process_post_save script */

function observation_set_events($observation) {
    // Adding the observation to the eventtable.
    global $DB;
    if ($events = $DB->get_records('event', array('modulename' => 'observation', 'instance' => $observation->id))) {
        foreach ($events as $event) {
            $event = calendar_event::load($event);
            $event->delete();
        }
    }

    // The open-event.
    $event = new stdClass;
    $event->description = $observation->name;
    $event->courseid = $observation->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'observation';
    $event->instance = $observation->id;
    $event->eventtype = 'open';
    $event->timestart = $observation->opendate;
    $event->visible = instance_is_visible('observation', $observation);
    $event->timeduration = ($observation->closedate - $observation->opendate);

    if ($observation->closedate && $observation->opendate && ($event->timeduration <= OBSERVATION_MAX_EVENT_LENGTH)) {
        // Single event for the whole observation.
        $event->name = $observation->name;
        calendar_event::create($event);
    } else {
        // Separate start and end events.
        $event->timeduration  = 0;
        if ($observation->opendate) {
            $event->name = $observation->name.' ('.get_string('observationopens', 'observation').')';
            calendar_event::create($event);
            unset($event->id); // So we can use the same object for the close event.
        }
        if ($observation->closedate) {
            $event->name = $observation->name.' ('.get_string('observationcloses', 'observation').')';
            $event->timestart = $observation->closedate;
            $event->eventtype = 'close';
            calendar_event::create($event);
        }
    }
}

/**
 * Get users who have not completed the observation
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @param object $cm
 * @param int $group single groupid
 * @param string $sort
 * @param int $startpage
 * @param int $pagecount
 * @return object the userrecords
 */
function observation_get_incomplete_users($cm, $sid,
                $group = false,
                $sort = '',
                $startpage = false,
                $pagecount = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    // First get all users who can complete this observation.
    $cap = 'mod/observation:submit';
    $fields = 'u.id, u.username';
    if (!$allusers = get_users_by_capability($context,
                    $cap,
                    $fields,
                    $sort,
                    '',
                    '',
                    $group,
                    '',
                    true)) {
        return false;
    }
    $allusers = array_keys($allusers);

    // Nnow get all completed observations.
    $params = array('observationid' => $cm->instance, 'complete' => 'y');
    $sql = "SELECT userid FROM {observation_response} " .
           "WHERE observationid = :observationid AND complete = :complete " .
           "GROUP BY userid ";

    if (!$completedusers = $DB->get_records_sql($sql, $params)) {
        return $allusers;
    }
    $completedusers = array_keys($completedusers);
    // Now strike all completedusers from allusers.
    $allusers = array_diff($allusers, $completedusers);
    // For paging I use array_slice().
    if (($startpage !== false) && ($pagecount !== false)) {
        $allusers = array_slice($allusers, $startpage, $pagecount);
    }
    return $allusers;
}

/**
 * Called by HTML editor in showrespondents and Essay question. Based on question/essay/renderer.
 * Pending general solution to using the HTML editor outside of moodleforms in Moodle pages.
 */
function observation_get_editor_options($context) {
    return array(
                    'subdirs' => 0,
                    'maxbytes' => 0,
                    'maxfiles' => -1,
                    'context' => $context,
                    'noclean' => 0,
                    'trusttext' => 0
    );
}

// Get the parent of a child question.
// TODO - This needs to be refactored or removed.
function observation_get_parent ($question) {
    global $DB;
    $qid = $question->id;
    $parent = array();
    $dependquestion = $DB->get_record('observation_question', ['id' => $question->dependquestionid],
        'id, position, name, type_id');
    if (is_object($dependquestion)) {
        $qdependchoice = '';
        switch ($dependquestion->type_id) {
            case QUESRADIO:
            case QUESDROP:
            case QUESCHECK:
                $dependchoice = $DB->get_record('observation_quest_choice', ['id' => $question->dependchoiceid], 'id,content');
                $qdependchoice = $dependchoice->id;
                $dependchoice = $dependchoice->content;

                $contents = observation_choice_values($dependchoice);
                if ($contents->modname) {
                    $dependchoice = $contents->modname;
                }
                break;
            case QUESYESNO:
                switch ($question->dependchoiceid) {
                    case 0:
                        $dependchoice = get_string('yes');
                        $qdependchoice = 'y';
                        break;
                    case 1:
                        $dependchoice = get_string('no');
                        $qdependchoice = 'n';
                        break;
                }
                break;
        }
        // Qdependquestion, parenttype and qdependchoice fields to be used in preview mode.
        $parent [$qid]['qdependquestion'] = 'q'.$dependquestion->id;
        $parent [$qid]['qdependchoice'] = $qdependchoice;
        $parent [$qid]['parenttype'] = $dependquestion->type_id;
        // Other fields to be used in Questions edit mode.
        $parent [$qid]['position'] = $question->position;
        $parent [$qid]['name'] = $question->name;
        $parent [$qid]['content'] = $question->content;
        $parent [$qid]['parentposition'] = $dependquestion->position;
        $parent [$qid]['parent'] = $dependquestion->name.'->'.$dependchoice;
    }
    return $parent;
}

/**
 * Get parent position of all child questions in current observation.
 * Use the parent with the largest position value.
 *
 * @param array $questions
 * @return array An array with Child-ID->Parentposition.
 */
function observation_get_parent_positions ($questions) {
    $parentpositions = array();
    foreach ($questions as $question) {
        foreach ($question->dependencies as $dependency) {
            $dependquestion = $dependency->dependquestionid;
            if (isset($dependquestion) && $dependquestion != 0) {
                $childid = $question->id;
                $parentpos = $questions[$dependquestion]->position;

                if (!isset($parentpositions[$childid])) {
                    $parentpositions[$childid] = $parentpos;
                }
                if (isset ($parentpositions[$childid]) && $parentpos > $parentpositions[$childid]) {
                    $parentpositions[$childid] = $parentpos;
                }
            }
        }
    }
    return $parentpositions;
}

/**
 * Get child position of all parent questions in current observation.
 * Use the child with the smallest position value.
 *
 * @param array $questions
 * @return array An array with Parent-ID->Childposition.
 */
function observation_get_child_positions ($questions) {
    $childpositions = array();
    foreach ($questions as $question) {
        foreach ($question->dependencies as $dependency) {
            $dependquestion = $dependency->dependquestionid;
            if (isset($dependquestion) && $dependquestion != 0) {
                $parentid = $questions[$dependquestion]->id; // Equals $dependquestion?.
                $childpos = $question->position;

                if (!isset($childpositions[$parentid])) {
                    $childpositions[$parentid] = $childpos;
                }

                if (isset ($childpositions[$parentid]) && $childpos < $childpositions[$parentid]) {
                    $childpositions[$parentid] = $childpos;
                }
            }
        }
    }
    return $childpositions;
}

// Check that the needed page breaks are present to separate child questions.
function observation_check_page_breaks($observation) {
    global $DB;
    $msg = '';
    // Store the new page breaks ids.
    $newpbids = array();
    $delpb = 0;
    $sid = $observation->survey->id;
    $questions = $DB->get_records('observation_question', ['surveyid' => $sid, 'deleted' => 'n'], 'id');
    $positions = array();
    foreach ($questions as $key => $qu) {
        $positions[$qu->position]['question_id'] = $key;
        $positions[$qu->position]['type_id'] = $qu->type_id;
        $positions[$qu->position]['qname'] = $qu->name;
        $positions[$qu->position]['qpos'] = $qu->position;

        $dependencies = $DB->get_records('observation_dependency', ['questionid' => $key , 'surveyid' => $sid],
                'id ASC', 'id, dependquestionid, dependchoiceid, dependlogic');
        $positions[$qu->position]['dependencies'] = $dependencies;
    }
    $count = count($positions);

    for ($i = $count; $i > 0; $i--) {
        $qu = $positions[$i];
        $questionnb = $i;
        if ($qu['type_id'] == QUESPAGEBREAK) {
            $questionnb--;
            // If more than one consecutive page breaks, remove extra one(s).
            $prevqu = null;
            $prevtypeid = null;
            if ($i > 1) {
                $prevqu = $positions[$i - 1];
                $prevtypeid = $prevqu['type_id'];
            }
            // If $i == $count then remove that extra page break in last position.
            if ($prevtypeid == QUESPAGEBREAK || $i == $count || $qu['qpos'] == 1) {
                $qid = $qu['question_id'];
                $delpb ++;
                $msg .= get_string("checkbreaksremoved", "observation", $delpb).'<br />';
                // Need to reload questions.
                $questions = $DB->get_records('observation_question', ['surveyid' => $sid, 'deleted' => 'n'], 'id');
                $DB->set_field('observation_question', 'deleted', 'y', ['id' => $qid, 'surveyid' => $sid]);
                $select = 'surveyid = '.$sid.' AND deleted = \'n\' AND position > '.
                                $questions[$qid]->position;
                if ($records = $DB->get_records_select('observation_question', $select, null, 'position ASC')) {
                    foreach ($records as $record) {
                        $DB->set_field('observation_question', 'position', $record->position - 1, ['id' => $record->id]);
                    }
                }
            }
        }
        // Add pagebreak between question child and not dependent question that follows.
        if ($qu['type_id'] != QUESPAGEBREAK) {
            $j = $i - 1;
            if ($j != 0) {
                $prevtypeid = $positions[$j]['type_id'];
                $prevdependencies = $positions[$j]['dependencies'];

                $outerdependencies = count($qu['dependencies']) >= count($prevdependencies) ? $qu['dependencies'] : $prevdependencies;
                $innerdependencies = count($qu['dependencies']) < count($prevdependencies) ? $qu['dependencies'] : $prevdependencies;

                foreach ($outerdependencies as $okey => $outerdependency) {
                    foreach ($innerdependencies as $ikey => $innerdependency) {
                        if ($outerdependency->dependquestionid === $innerdependency->dependquestionid &&
                            $outerdependency->dependchoiceid === $innerdependency->dependchoiceid &&
                            $outerdependency->dependlogic === $innerdependency->dependlogic) {
                            unset($outerdependencies[$okey]);
                            unset($innerdependencies[$ikey]);
                        }
                    }
                }

                $diffdependencies = count($outerdependencies) + count($innerdependencies);

                if (($prevtypeid != QUESPAGEBREAK && $diffdependencies != 0)
                        || (!isset($qu['dependencies']) && isset($prevdependencies))) {
                    $sql = 'SELECT MAX(position) as maxpos FROM {observation_question} ' .
                        'WHERE surveyid = ' . $observation->survey->id . ' AND deleted = \'n\'';
                    if ($record = $DB->get_record_sql($sql)) {
                        $pos = $record->maxpos + 1;
                    } else {
                        $pos = 1;
                    }
                    $question = new stdClass();
                    $question->surveyid = $observation->survey->id;
                    $question->type_id = QUESPAGEBREAK;
                    $question->position = $pos;
                    $question->content = 'break';

                    if (!($newqid = $DB->insert_record('observation_question', $question))) {
                        return (false);
                    }
                    $newpbids[] = $newqid;
                    $movetopos = $i;
                    $observation = new observation($observation->id, null, $course, $cm);
                    $observation->move_question($newqid, $movetopos);
                }
            }
        }
    }
    if (empty($newpbids) && !$msg) {
        $msg = get_string('checkbreaksok', 'observation');
    } else if ($newpbids) {
        $msg .= get_string('checkbreaksadded', 'observation').'&nbsp;';
        $newpbids = array_reverse ($newpbids);
        $observation = new observation($observation->id, null, $course, $cm);
        foreach ($newpbids as $newpbid) {
            $msg .= $observation->questions[$newpbid]->position.'&nbsp;';
        }
    }
    return($msg);
}

/**
 * Code snippet used to set up the questionform.
 */
function observation_prep_for_questionform($observation, $qid, $qtype) {
    $context = context_module::instance($observation->cm->id);
    if ($qid != 0) {
        $question = clone($observation->questions[$qid]);
        $question->qid = $question->id;
        $question->sid = $observation->survey->id;
        $question->id = $observation->cm->id;
        $draftideditor = file_get_submitted_draft_itemid('question');
        $content = file_prepare_draft_area($draftideditor, $context->id, 'mod_observation', 'question',
                                           $qid, array('subdirs' => true), $question->content);
        $question->content = array('text' => $content, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);

        if (isset($question->dependencies)) {
            foreach ($question->dependencies as $dependencies) {
                if ($dependencies->dependandor === "and") {
                    $question->dependquestions_and[] = $dependencies->dependquestionid.','.$dependencies->dependchoiceid;
                    $question->dependlogic_and[] = $dependencies->dependlogic;
                } else if ($dependencies->dependandor === "or") {
                    $question->dependquestions_or[] = $dependencies->dependquestionid.','.$dependencies->dependchoiceid;
                    $question->dependlogic_or[] = $dependencies->dependlogic;
                }
            }
        }
    } else {
        $question = \mod_observation\question\question::question_builder($qtype);
        $question->sid = $observation->survey->id;
        $question->id = $observation->cm->id;
        $question->type_id = $qtype;
        $question->type = '';
        $draftideditor = file_get_submitted_draft_itemid('question');
        $content = file_prepare_draft_area($draftideditor, $context->id, 'mod_observation', 'question',
                                           null, array('subdirs' => true), '');
        $question->content = array('text' => $content, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
    }
    return $question;
}

/**
 * Get the standard page contructs and check for validity.
 * @param int $id The coursemodule id.
 * @param int $a  The module instance id.
 * @return array An array with the $cm, $course, and $observation records in that order.
 */
function observation_get_standard_page_items($id = null, $a = null) {
    global $DB;

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
        if (! $observation = $DB->get_record("observation", array("id" => $a))) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record("course", array("id" => $observation->course))) {
            print_error('coursemisconf');
        }
        if (! $cm = get_coursemodule_from_instance("observation", $observation->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
    }

    return (array($cm, $course, $observation));
}