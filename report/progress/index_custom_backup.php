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
 * Activity progress reports
 *
 * @package    report
 * @subpackage progress
 * @copyright  2008 Sam Marshall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require('locallib.php');

define('COMPLETION_REPORT_PAGE', 25);


// Get course
$id = required_param('course',PARAM_INT);
$course = $DB->get_record('course',array('id'=>$id));
if (!$course) {
    print_error('invalidcourseid');
}
$context = context_course::instance($course->id);

// Sort (default lastname, optionally firstname)
$sort = optional_param('sort','',PARAM_ALPHA);
$firstnamesort = $sort == 'firstname';

// CSV format
$format = optional_param('format','',PARAM_ALPHA);
$excel = $format == 'excelcsv';
$csv = $format == 'csv' || $excel;

// Paging
$start   = optional_param('start', 0, PARAM_INT);
$sifirst = optional_param('sifirst', 'all', PARAM_NOTAGS);
$silast  = optional_param('silast', 'all', PARAM_NOTAGS);
$start   = optional_param('start', 0, PARAM_INT);

// Whether to show extra user identity information
$extrafields = get_extra_user_fields($context);
$leftcols = 1 + count($extrafields);

function csv_quote($value) {
    global $excel;
    if ($excel) {
        return core_text::convert('"'.str_replace('"',"'",$value).'"','UTF-8','UTF-16LE');
    } else {
        return '"'.str_replace('"',"'",$value).'"';
    }
}

$url = new moodle_url('/report/progress/index_custom.php', array('course'=>$id));

if(isset($_POST['save'])){
	global $SESSION;
	$result = $notes->save_changes($_POST['changecompl'], $_POST['note'], $_POST['smode']);
	if($result===true){
		$SESSION->result = "Changes saved successfully";
		redirect($url);
	}
}

if ($sort !== '') {
    $url->param('sort', $sort);
}
if ($format !== '') {
    $url->param('format', $format);
}
if ($start !== 0) {
    $url->param('start', $start);
}
if ($sifirst !== 'all') {
    $url->param('sifirst', $sifirst);
}
if ($silast !== 'all') {
    $url->param('silast', $silast);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/report/progress/custom/style.css'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/lib/jquery/jquery-3.4.1.js'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/report/progress/custom/custom.js'));

require_login($course);

// Check basic permission
require_capability('report/progress:view',$context);

// Get group mode
$group = groups_get_course_group($course,true); // Supposed to verify group
if ($group===0 && $course->groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

// Get data on activities and progress of all users, and give error if we've
// nothing to display (no users or no activities)
$reportsurl = $CFG->wwwroot.'/course/report.php?id='.$course->id;
$completion = new completion_info($course);
$activities = $completion->get_activities();

if ($sifirst !== 'all') {
    set_user_preference('ifirst', $sifirst);
}
if ($silast !== 'all') {
    set_user_preference('ilast', $silast);
}

if (!empty($USER->preference['ifirst'])) {
    $sifirst = $USER->preference['ifirst'];
} else {
    $sifirst = 'all';
}

if (!empty($USER->preference['ilast'])) {
    $silast = $USER->preference['ilast'];
} else {
    $silast = 'all';
}

// Generate where clause
$where = array();
$where_params = array();

if ($sifirst !== 'all') {
    $where[] = $DB->sql_like('u.firstname', ':sifirst', false, false);
    $where_params['sifirst'] = $sifirst.'%';
}

if ($silast !== 'all') {
    $where[] = $DB->sql_like('u.lastname', ':silast', false, false);
    $where_params['silast'] = $silast.'%';
}

// Get user match count
$total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);

// Total user count
$grandtotal = $completion->get_num_tracked_users('', array(), $group);

// Get user data
$progress = array();

if ($total) {
    $users = $completion->get_progress_all(
        implode(' AND ', $where),
        $where_params,
        $group,
        $firstnamesort ? 'u.firstname ASC, u.lastname ASC' : 'u.lastname ASC, u.firstname ASC',
        $csv ? 0 : COMPLETION_REPORT_PAGE,
        $csv ? 0 : $start,
        $context
    );
    $student = optional_param('user', null, PARAM_INT);
    if ($student) {
        $where[] = 'u.id = :userid';
        $where_params['userid'] = $student;
    }
    $progress = $completion->get_progress_all(
        implode(' AND ', $where),
        $where_params,
        $group,
        $firstnamesort ? 'u.firstname ASC, u.lastname ASC' : 'u.lastname ASC, u.firstname ASC',
        $csv ? 0 : COMPLETION_REPORT_PAGE,
        $csv ? 0 : $start,
        $context
    );
}

if ($csv && $grandtotal && count($activities)>0) { // Only show CSV if there are some users/actvs
    $shortname = format_string($course->shortname, true, array('context' => $context));
    header('Content-Disposition: attachment; filename=progress.'.
        preg_replace('/[^a-z0-9-]/','_',core_text::strtolower(strip_tags($shortname))).'.csv');
    // Unicode byte-order mark for Excel
    if ($excel) {
        header('Content-Type: text/csv; charset=UTF-16LE');
        print chr(0xFF).chr(0xFE);
        $sep="\t".chr(0);
        $line="\n".chr(0);
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        $sep=",";
        $line="\n";
    }
} else {
    // Navigation and header
    $strreports = get_string("reports");
    $strcompletion = get_string('activitycompletion', 'completion');
    $PAGE->set_title($strcompletion);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    $PAGE->requires->js_call_amd('report_progress/completion_override', 'init', [fullname($USER)]);

    // Handle groups (if enabled)
    groups_print_course_menu($course,$CFG->wwwroot.'/report/progress/?course='.$course->id);
}

if (count($activities)==0) {
    echo $OUTPUT->container(get_string('err_noactivities', 'completion'), 'errorbox errorboxcontent');
    echo $OUTPUT->footer();
    exit;
}

// If no users in this course what-so-ever
if (!$grandtotal) {
    echo $OUTPUT->container(get_string('err_nousers', 'completion'), 'errorbox errorboxcontent');
    echo $OUTPUT->footer();
    exit;
}

// Build link for paging
$link = $CFG->wwwroot.'/report/progress/?course='.$course->id;
if (strlen($sort)) {
    $link .= '&amp;sort='.$sort;
}
$link .= '&amp;start=';
$pagingbar = '';

// Initials bar.
$prefixfirst = 'sifirst';
$prefixlast = 'silast';
$pagingbar .= $OUTPUT->initials_bar($sifirst, 'firstinitial', get_string('firstname'), $prefixfirst, $url);
$pagingbar .= $OUTPUT->initials_bar($silast, 'lastinitial', get_string('lastname'), $prefixlast, $url);

// Do we need a paging bar?
if ($total > COMPLETION_REPORT_PAGE) { 
    // Paging bar
    $pagingbar .= '<div class="paging">';
    $pagingbar .= get_string('page').': ';
    $sistrings = array();
    if ($sifirst != 'all') {
        $sistrings[] =  "sifirst={$sifirst}";
    }
    if ($silast != 'all') {
        $sistrings[] =  "silast={$silast}";
    }
    $sistring = !empty($sistrings) ? '&amp;'.implode('&amp;', $sistrings) : '';

    // Display previous link
    if ($start > 0) {
        $pstart = max($start - COMPLETION_REPORT_PAGE, 0);
        $pagingbar .= "(<a class=\"previous\" href=\"{$link}{$pstart}{$sistring}\">".get_string('previous').'</a>)&nbsp;';
    }

    // Create page links
    $curstart = 0;
    $curpage = 0;
    while ($curstart < $total) {
        $curpage++;
        if ($curstart == $start) {
            $pagingbar .= '&nbsp;'.$curpage.'&nbsp;';
        } else {
            $pagingbar .= "&nbsp;<a href=\"{$link}{$curstart}{$sistring}\">$curpage</a>&nbsp;";
        }
        $curstart += COMPLETION_REPORT_PAGE;
    }
    // Display next link
    $nstart = $start + COMPLETION_REPORT_PAGE;
    if ($nstart < $total) {
        $pagingbar .= "&nbsp;(<a class=\"next\" href=\"{$link}{$nstart}{$sistring}\">".get_string('next').'</a>)';
    }
    $pagingbar .= '</div>';
}

// Okay, let's draw the table of progress info,
// Start of table
if (!$csv) {
    print '<br class="clearer"/>';
    print $pagingbar;
    if(!$total){
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
        echo $OUTPUT->footer();
        exit;
    }
    $base_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];
    $url = $base_url.$_SERVER['REQUEST_URI'];
    $str = $_SERVER['QUERY_STRING'];
    parse_str($str, $params );
    // USERS autocomplete form
    // array_unshift($users, 'All');
    require_once($CFG->dirroot.'/report/progress/autocomplete_form.php');
    $userform = new selectusers($url, ['users' => $users, 'user' => $student]);

    $studentselector = '<div class="student-selector">     
    <div class="student-selector" >
    '.$userform->display().'
    </div>';
    echo $studentselector;
	
	//Show session message if any
	if(isset($SESSION->result)){
		print '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>'. $SESSION->result .'</div>';
		unset($SESSION->result);
	}

    print '<div id="completion-progress-wrapper" class="no-overflow">';
	
	// Add Note Popup - Start
    print '<div class="popupbg"></div>
	<div class="custompopup">
		<div class="cp_student"></div>
		<form action="" method="post">
		<textarea name="note" class="note" required></textarea>
		<input type="hidden" name="changecompl" class="changecompl">
		<label>Choose saving mode:</label>
		<select name="smode" class="smode" required>
		<option value="single">Save note for single activity</option>
		<option value="multi">Save note for whole section</option>
		</select>
		<div class="popup_single_acti"></div>
		<div class="popup_multi_acti"></div>
		<input type="submit" class="btn_savepopup btn btn-primary" value="Save changes" name="save">
        <button type="button" class="btn_cancelpopup btn btn-primary">Cancel</button>
		</form>
	</div>';
	// Add Note Popup - End
	
    print '<table id="completion-progress" class="generaltable flexible boxaligncenter" style="text-align:left"><thead><tr style="vertical-align:top">';

    // User heading / sort option
    print '<th scope="col" class="completion-sortchoice">';

    $sistring = "&amp;silast={$silast}&amp;sifirst={$sifirst}";

    print  get_string('activities').'</a> / '.
            get_string('resources');
    print '</th>';

    foreach($progress as $user) {
        // User name
        if ($csv) {
            print csv_quote(fullname($user));
        } else {
            $course_progress = \core_completion\progress::get_course_progress_percentage($course, $user->id);            
            print '<th scope="row" class="completion-header" data-progress="'.(int) $course_progress .'" >
			<a href="'.$CFG->wwwroot.'/user/view.php?id='. $user->id .'&amp;course='. $course->id .'">
            <div class="rotated-text-container"><span class="rotated-text">'. fullname($user) .'</span></div></a></th>';
        }
    }

} else {
    foreach ($extrafields as $field) {
        echo $sep . csv_quote(get_user_field_name($field));
    }
}

if ($csv) {
    print $line;
} else {
    print '</tr></thead><tbody>';
}

$colspan = 1 + count($progress);
$sections = array();
$show = true;

foreach($activities as $activity) {

    $datepassed = $activity->completionexpected && $activity->completionexpected <= time();
    $datepassedclass = $datepassed ? 'completion-expired' : '';

    if ($activity->completionexpected) {
        $datetext=userdate($activity->completionexpected,get_string('strftimedate','langconfig'));
    } else {
        $datetext='';
    }

    // Some names (labels) come URL-encoded and can be very long, so shorten them
    $displayname = format_string($activity->name, true, array('context' => $activity->context));

    if ($csv) {
        print $sep.csv_quote($displayname).$sep.csv_quote($datetext);
    } else {
        // $shortenedname = shorten_text($displayname);
		$class = "section".$activity->section;
		$section = $notes->get_section($activity->section);
		
		if(!in_array($section->name,$sections) || $show==true){
			if(!empty($section->name)){
				print '<tr><td align="center" colspan="'.$colspan.'"> Section : '. $section->name .'</td></tr>';
			}
			else{
				print '<tr><td align="center" colspan="'.$colspan.'"> Section : Name not available</td></tr>';
			}
			$sections[$activity->section] = $section->name;
			$show = false;
		}
		
        print '<tr><td>' .
            '<a class="'.$class.'" href="'.$CFG->wwwroot.'/mod/'.$activity->modname .
            '/view.php?id='.$activity->id.'" title="' . s($displayname) . '">'.
            '<span class="">'.$displayname.'
			</span>'.
            '<div class="modicon">'.
            $OUTPUT->image_icon('icon', get_string('modulename', $activity->modname), $activity->modname) .
            '</div>'.
            '</a>';
        if ($activity->completionexpected) {
            print '<div class="completion-expected"><span>'.$datetext.'</span></div>';
        }
        print '</td>';
    }
    $formattedactivities[$activity->id] = (object)array(
        'datepassedclass' => $datepassedclass,
        'displayname' => $displayname,
    );

    // Row for each user
    foreach($progress as $user) {

    // Progress for each activity

        // Get progress information and state
        if (array_key_exists($activity->id, $user->progress)) {
            $thisprogress = $user->progress[$activity->id];
            $state = $thisprogress->completionstate;
            $overrideby = $thisprogress->overrideby;
            $date = userdate($thisprogress->timemodified);
			$cmcid = $notes->get_cmcid($activity->id,$thisprogress->userid);
        } else {
            $state = COMPLETION_INCOMPLETE;
            $overrideby = 0;
			$cmcid = 0;
            $date = '';	
        }

        // Work out how it corresponds to an icon
        switch($state) {
            case COMPLETION_INCOMPLETE :
                $completiontype = 'n'.($overrideby ? '-override' : '');
                break;
            case COMPLETION_COMPLETE :
                $completiontype = 'y'.($overrideby ? '-override' : '');
                break;
            case COMPLETION_COMPLETE_PASS :
                $completiontype = 'pass';
                break;
            case COMPLETION_COMPLETE_FAIL :
                $completiontype = 'fail';
                break;
        }
        $completiontrackingstring = $activity->completion == COMPLETION_TRACKING_AUTOMATIC ? 'auto' : 'manual';
        $completionicon = 'completion-' . $completiontrackingstring. '-' . $completiontype;

        if ($overrideby) {
            $overridebyuser = \core_user::get_user($overrideby, '*', MUST_EXIST);
            $describe = get_string('completion-' . $completiontype, 'completion', fullname($overridebyuser));
        } else {
            $describe = get_string('completion-' . $completiontype, 'completion');
        }
		
        $a = new StdClass;
        $a->state = $describe;
        $a->date = $date;
        $a->user = fullname($user);
        $a->activity = $formattedactivities[$activity->id]->displayname;
        $fulldescribe = get_string('progress-title','completion',$a);
		
		if($cmcid>0){
			if($notes->is_note($cmcid)){
				$notedata = $notes->get_note($cmcid);
				$noteid = $notedata->id;
				$note = $notedata->note;
				$fulldescribe = "Note : $note \n---\n" . $fulldescribe;
			}
			else{
				$noteid = 0;
				$note = "";
			}
		}
		else{
			$noteid = 0;
			$note = "";
		}

        if ($csv) {
            print $sep.csv_quote($describe).$sep.csv_quote($date);
        } else {
            $celltext = $OUTPUT->pix_icon('i/' . $completionicon, s($fulldescribe));
            if (has_capability('moodle/course:overridecompletion', $context) &&
                    $state != COMPLETION_COMPLETE_PASS && $state != COMPLETION_COMPLETE_FAIL) {
                $newstate = ($state == COMPLETION_COMPLETE) ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE;
                $changecompl = $cmcid . '-' . $user->id . '-' . $activity->id . '-' . $newstate . '-' . $noteid . '-' . $course->id . '-' . $activity->section;
                $url = new moodle_url($PAGE->url, ['sesskey' => sesskey()]);
                $celltext = html_writer::link($url, $celltext, array(
				'class' => "change_status", 
				'data-changecompl' => $changecompl,
				'data-note' => $note,
				'data-activityname' => $a->activity,
				'data-userfullname' => $a->user,
				'data-completiontracking' => $completiontrackingstring,
				'role' => 'button'));
            }
            print '<td class="completion-progresscell '.$formattedactivities[$activity->id]->datepassedclass.'">'.
                $celltext . '</td>';
        }
    }

    if ($csv) {
        print $line;
    } else {
        print '</tr>';
    }
}

if ($csv) {
    exit;
}
print '</tbody></table>';
print '</div>';

print '<ul class="progress-actions"><li><a href="index.php?course='.$course->id.
    '&amp;format=csv">'.get_string('csvdownload','completion').'</a></li>
    <li><a href="index.php?course='.$course->id.'&amp;format=excelcsv">'.
    get_string('excelcsvdownload','completion').'</a></li></ul>';

echo $OUTPUT->footer();