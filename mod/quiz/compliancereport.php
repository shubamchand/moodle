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
 * This script controls the display of the quiz reports.
 *
 * @package   mod_quiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Added by nirmal
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/report/default.php');
require_once("$CFG->libdir/excellib.class.php");

$id = optional_param('id', 0, PARAM_INT);
$q = optional_param('q', 0, PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);

if ($id) {
    
    if (!$cm = get_coursemodule_from_id('quiz', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }


    $quizobj = quiz::create($cm->instance, $USER->id);
    if (!$quizobj->has_questions()) {
        echo 'no question';
    }

    $question_array = [];
    $i = 1;foreach($quizobj->get_partial_questions() as $qData){
       
        $question_array[$i] = [
            'Question No' => $i,
            'Compiance Box content' => $qData->compliance,
            'Question Text' => $qData->questiontext,
            'Coments' => ''
        ];
    $i++;}

} else {
    if (!$quiz = $DB->get_record('quiz', array('id' => $q))) {
        print_error('invalidquizid', 'quiz');
    }
    if (!$course = $DB->get_record('course', array('id' => $quiz->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

$url = new moodle_url('/mod/quiz/report.php', array('id' => $cm->id));
if ($mode !== '') {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_pagelayout('report');

// Cache some other capabilities we use several times.
$canmanage = has_capability('mod/quiz:manage', $context);
if(!$canmanage){
    redirect(new moodle_url('/mod/quiz/startattempt.php',
            array('cmid' => $cm->id, 'sesskey' => sesskey())));  
} else {
   
}

// Creating a workbook.
$filename = clean_filename($cm->name). " Comliance Report "  . ".xls";
$workbook = new MoodleExcelWorkbook($filename);

// Creating the worksheet.
error_reporting(0);
$worksheet1 = $workbook->add_worksheet();
error_reporting($CFG->debug);
$worksheet1->hide_gridlines();
$worksheet1->set_column(0, 0, 10);
$worksheet1->set_column(1, 1, 30);
$worksheet1->set_column(2, 20, 15);

// Creating the needed formats.
$xlsformats = new stdClass();
$xlsformats->head1 = $workbook->add_format(['bold' => 1, 'size' => 12]);
$xlsformats->head2 = $workbook->add_format(['align' => 'left', 'bold' => 1, 'bottum' => 2]);
$xlsformats->default = $workbook->add_format(['align' => 'left', 'v_align' => 'top']);
$xlsformats->value_bold = $workbook->add_format(['align' => 'left', 'bold' => 1, 'v_align' => 'top']);
$xlsformats->procent = $workbook->add_format(['align' => 'left', 'bold' => 1, 'v_align' => 'top', 'num_format' => '#,##0.00%']);

// Writing the table header.
$rowoffset1 = 0;
$worksheet1->write_string($rowoffset1, 0, userdate(time()), $xlsformats->head1);


$rowoffset1++;

$format =& $workbook->add_format();
$format->set_bold(1);

$worksheet1->write_string($rowoffset1, 0,'Question No' , $format);
$worksheet1->write_string($rowoffset1, 1, 'Question Text', $format);
$worksheet1->write_string($rowoffset1, 2, 'Compiance Box content', $format);
$worksheet1->write_string($rowoffset1, 2, 'Comments', $format);
$rowoffset1++;

$formatg =& $workbook->add_format();
$formatg->set_bold(1);
$formatg->set_color('green');
$formatg =& $workbook->add_format();
// $formatg->set_align('center');
$worksheet1->merge_cells(0,0,0,0);
$worksheet1->write(0, $col, clean_filename($cm->name),$formatg);
$fields =  [
            'Question No',
            'Compiance Box content',
            'Question Text' ,
            'Comments'
            ];
$col = 0;
foreach ($fields as $fieldname) {
    $worksheet1->write(1, $col, $fieldname,$format);
    $col++;
}
$row = 2;
// echo '<pre>';
// print_r($question_array);
// exit;
foreach ($question_array as $aData) {
    // print_r($aData); exit;
    $col = 0;
    foreach ($fields as $field) {
        if($col==1 || $col==2){
            $html = new PhpOffice\PhpSpreadsheet\Helper\Html();
            $HTMLCODE = $html->toRichTextObject($aData[$field]);
            $worksheet1->write($row, $col, $HTMLCODE);
        } else {
            $worksheet1->write($row, $col, $aData[$field]);

        }
        $col++;
    }
    $row++;
}
// exit;

$workbook->close();