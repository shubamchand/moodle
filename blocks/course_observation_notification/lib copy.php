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
 * @package   block_course_observation_notification
 * @copyright 2012 Dakota Duff
 */

/**
 * Returns CSV values from provided array
 * @param array $array The array to implode
 * @return string $string
 */
function block_course_observation_notification_array2str($array) {
    if (count($array)) {
        $string = implode(',', $array);
    } else {
        $string = null;
    }
    return $string;
}



/**
 * Construct the tree of ungraded items
 * @param array $course The array of ungraded items for a specific course
 * @return string $text
 */
function block_course_observation_notification_tree($course,$sections,$course_section,$cmid) {
    global $CFG, $DB, $OUTPUT, $SESSION;

 
    // Grading image.
    $gradeimg = $CFG->wwwroot.'/blocks/course_observation_notification/pix/check_mark.png';
    // Define text variable.
    $text = '';

    $courseid = $course->id;
    $coursename = $course->fullname;
  
    $altgradebook = get_string('alt_gradebook', 'block_course_observation_notification', array('course_name' => $coursename));
    $gradebookicon = $OUTPUT->pix_icon('i/grades', $altgradebook, null, array('class' => 'gm_icon'));
    $courselink = $CFG->wwwroot.'/course/view.php?id='.$courseid;
    $coursetitle = get_string('link_gradebook', 'block_course_observation_notification', array('course_name' => $coursename));
    $text .= '<div>';
    $text .= '<dt id="obcourseid'.$courseid.'" class="cmod">
    <div class="toggle open" onclick="$(\'dt#obcourseid'.$courseid.
        ' > div.toggle\').toggleClass(\'open\');$(\'dt#obcourseid'.$courseid.
        ' ~ dd\').toggleClass(\'block_course_observation_notification_hide\');"></div>
    <a href="'.$courselink.'">'.$gradebookicon.'</a>
    <a href="'.$courselink.'" title="'.$coursetitle.'">'.$coursename.'</a></dt>'."\n";
    $text .= "\n";

    ksort($course);
    // print_r($sections); exit;
    foreach ($sections as $sectionid => $item) {
        $itemmodule = 'observation';
        $itemname = 'observation';
        $coursemoduleid = $cmid;
        // unset($item['meta']);

        $modulelink = $CFG->wwwroot.'/mod/'.$itemmodule.'/view.php?id='.$coursemoduleid;
        $moduletitle = $sction;
        $moduleicon = $OUTPUT->pix_icon('icon', $moduletitle, $itemmodule, array('class' => 'gm_icon'));
        $sction = $course_section[$sectionid];
        $itemname = $sction;
     

        $text .= '<dd id="obcmid'.$coursemoduleid.'" class="module">'."\n";  // Open module.
        $text .= '<div class="toggle" onclick="$(\'dd#obcmid'.$coursemoduleid.
            ' > div.toggle\').toggleClass(\'open\');$(\'dd#obcmid'.$coursemoduleid.
            ' > ul\').toggleClass(\'block_course_observation_notification_hide\');"></div>'."\n";
        $text .= '<a href="'.$modulelink.'" title="'.$moduletitle.'">'.$moduleicon.'</a>';
        $text .= '<a href="'.$modulelink.'" title="'.$moduletitle.'">'.$itemname.'</a> ('.count($item).')'."\n";

        $text .= '<ul class="block_course_observation_notification_hide">'."\n";

        foreach ($item as $l3 => $submission) {
          
            $timesubmitted = $l3;
            $userid = $submission;
            $submissionid = $submission['submissionid'];

            $submissiontitle = get_string('link_grade_img', 'block_course_observation_notification', array());
            $altmark = get_string('alt_mark', 'block_course_observation_notification', array());

            $user = $DB->get_record('user', array('id' => $userid));
            
            $userfirst = $user->firstname;
            $userfirstlast = $user->firstname.' '.$user->lastname;
            $userprofiletitle = get_string('link_user_profile', 'block_course_observation_notification', array('first_name' => $userfirst));

            $text .= '<li class="gradable">';  // Open gradable.
            $text .= $OUTPUT->user_picture($user, array('size' => 16, 'courseid' => $courseid, 'link' => true));
            $text .= '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&amp;course='.
                $courseid.'" title="'.$userprofiletitle.'">'.$userfirstlast.'</a>';  // User name and profile link.
            $text .= '</li>'."\n";  // End gradable.
        }

        $text .= '</ul>'."\n";
        $text .= '</dd>'."\n";  // Close module.
    }
    $text .= '</div>';

    return $text;
}
