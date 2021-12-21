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
function block_course_observation_notification_tree($courses) {
    global $CFG, $DB, $OUTPUT, $SESSION;

    // Grading image.
    $gradeimg = $CFG->wwwroot.'/blocks/course_observation_notification/pix/check_mark.png';
    // Define text variable.
    $text = '';
    $text .= '<div>';
    foreach($courses as $courseid => $coursemodules){
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $courseid = $course->id;
        $coursename = $course->fullname;
        $altgradebook = get_string('alt_gradebook', 'block_course_observation_notification', array('course_name' => $coursename));
        $gradebookicon = $OUTPUT->pix_icon('i/grades', $altgradebook, null, array('class' => 'gm_icon'));
        $courselink = $CFG->wwwroot.'/course/view.php?id='.$courseid;
        $coursetitle = get_string('link_gradebook', 'block_course_observation_notification', array('course_name' => $coursename));
       
        $text .= '<dt id="obcourseid'.$courseid.'" class="cmod">
        <div class="toggle open" onclick="$(\'dt#obcourseid'.$courseid.
            ' > div.toggle\').toggleClass(\'open\');$(\'dt#obcourseid'.$courseid.
            ' ~ dd\').toggleClass(\'block_course_observation_notification_hide\');"></div>
        <a href="'.$courselink.'">'.$gradebookicon.'</a>
        <a href="'.$courselink.'" title="'.$coursetitle.'">'.$coursename.'</a></dt>'."\n";
        $text .= "\n";


        foreach($coursemodules as $coursemoduleid => $users){
            // $cm = $DB->get_record('course_modules', array('id' => $coursemoduleid), '*', MUST_EXIST);
            $modinfo = get_fast_modinfo($courseid);
            $module = $modinfo->cms[$coursemoduleid];

            $modulename = $module->name;
            
            $itemmodule = 'observation';
            $itemname = $module->name;
            // $itemname = 'observation';
          
            $modulelink = $CFG->wwwroot.'/mod/'.$itemmodule.'/view.php?id='.$coursemoduleid;
            $moduletitle = $itemname;
            $moduleicon = $OUTPUT->pix_icon('icon', $moduletitle, $itemmodule, array('class' => 'gm_icon'));
           
            

            $text .= '<dd id="obcmid'.$coursemoduleid.'" class="module">'."\n";  // Open module.
            $text .= '<div class="toggle" onclick="$(\'dd#obcmid'.$coursemoduleid.
                ' > div.toggle\').toggleClass(\'open\');$(\'dd#obcmid'.$coursemoduleid.
                ' > ul\').toggleClass(\'block_course_observation_notification_hide\');"></div>'."\n";
            $text .= '<a href="'.$modulelink.'" title="'.$moduletitle.'">'.$moduleicon.'</a>';
            $text .= '<a href="'.$modulelink.'" title="'.$moduletitle.'">'.$itemname.'</a> ('.count($users).')'."\n";

            $text .= '<ul class="block_course_observation_notification_hide">'."\n";
          
            foreach ($users as $l3 => $userData) {
                $timesubmitted = $l3;
                $userid = $userData['userid'];

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

    }
    $text .= '</div>';

    return $text;
}
