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
 * English lang file
 * @package    theme_ausinet
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['configtitle'] = "Ausinet Settings";
$string['choosereadme'] = '<div class="clearfix"><div class="theme_screenshot"><img class=img-polaroid src="ausinet/pix/screenshot.jpg" /><br/><p></p><h3>Theme Credits</h3><p><h3>Moodle Ausinet theme</h3><p>This theme is based on the Boost Moodle theme.</p><p>Authors: LMSACE Dev Team<br>Contact: info@lmsace.com<br>Website: <a href="http://www.lmsace.com">www.lmsace.com</a><br></p>';
$string['configtitle'] = 'Ausinet';
$string['pluginname'] = 'Ausinet';
$string['headerheading'] = 'Ausinet Theme Settings';
$string['logo'] = 'Logo';
$string['logodesc'] = 'Attach your portal logo image';
$string['readmore'] = 'Read More';
$string['region-side-pre'] = 'Side Pre';
$string['region-side-top'] = 'Side Top';
$string['coursename'] = 'Course name';
$string['bannerimage'] = 'Banner image';
$string['bannerimagedesc'] = 'Upload the banner image.';
$string['bannercaption'] = 'Banner caption';
$string['bannercaptiondesc'] = 'Enter your banner caption text';
$string['bannercaptiondefault'] = 'Moodle Themes';
$string['bannerdesc'] = 'Banner Description';
$string['bannerdesctext'] = 'Enter you banner\'s description content  visible on the fronpage under banner caption ';
$string['bannerdescdefault'] = 'Lorum ipsum is simply dummy text of the printing and typesetting industry';
$string['togglebanner'] = 'Toggle Banner';
$string['togglebannerdesc'] = 'Enable the banner from frontpage.';
$string['bannerlink'] = 'Banner link';
$string['bannerlinkdesc'] = 'Enter the banner link for the button read more';
$string['categoryimgcategory'] = 'Category images';
$string['toggleaboutus'] = ' toggleaboutus';
$string['aboutus_title'] = 'About Us title';
$string['marketingSpot1_statusdesc'] = ' marketingSpot1_statusdesc';
$string['mspottitledesc'] = ' mspottitledesc';
$string['mspotdescdesc'] = ' mspotdescdesc';
$string['media'] = ' media';
$string['mspotmediadesc'] = ' mspotmediadesc';
$string['footerheading'] = 'Footer';
$string['copyrightheading'] = 'copyrightheading';
$string['copyright'] = 'copyright';
$string['copyrightdesc'] = 'copyrightdesc';
$string['links'] = 'links';
$string['footerlink_desc'] = 'footerlink_desc';
$string['footerlinkdefault'] = 'footerlinkdefault';
$string['categoryimgheading'] = 'Category Images';
$string['completion'] = 'Completion';
$string['emailquizgradebody'] = 'Hi {$a->username},

Your attempt on \'{$a->quizname}\' (<a href="{$a->quizurl}"> {$a->quizurl} </a>) in course \'{$a->coursename}\' graded.

You can review your grade at <a href="{$a->quizreviewurl}">{$a->quizreviewurl}<a>.';
$string['emailquizgradesmall'] = 'Attempt has been graded {$a->quizname}. See {$a->quizreviewurl}';
$string['emailquizgradesubject'] = 'Attempt has been graded {$a->quizname}';
$string['reportquestionbody'] = '{$a->username} in course {$a->coursename} has reported question {$a->qno} in {$a->quizname}.

Message from student:  <{$a->email}>
{$a->rq_msg}

You can review quiz at <a href="{$a->quizurl}">{$a->quizname}</a>.

You can review question {$a->qno} at <a href="{$a->quizreviewurl}">{$a->quizreviewurl}</a>.';
$string['reportquestionsmall'] = 'Question {$a->qno} has been reported {$a->quizname}. See {$a->quizreviewurl}';
$string['reportquestionsubject'] = '{$a->username} reported Question {$a->qno} in {$a->quizname}';
$string['info'] = 'Info';
$string['selectedusers'] = 'Selected users';
$string['completionmail_student'] = 'Congratulations! You have succesfully completed {$a->course}';
// changes added here with new vairable coursecode to display shortname field in the email template
$string['completionmail_teacher'] = 'Course succesfully completed<br /> <strong>Student Name</strong> : {$a->student}<br /> <strong>Course</strong> : {$a->course}<br /><strong>Course Code</strong> : {$a->coursecode}';
$string['completionmailsubject_student'] = 'Course completed';
$string['completionmailsubject_teacher'] = 'Course completed';
$string['course_contact'] = 'Contact <a href="mailto:support@ausinet.com.au">support@ausinet.com.au</a> if you are interested in enrolling in one of those courses';