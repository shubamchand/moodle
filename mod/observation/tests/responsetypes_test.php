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
 * PHPUnit observation generator tests
 *
 * @package    mod_observation
 * @copyright  2015 Mike Churchward (mike@churchward.ca)
 * @author     Mike Churchward
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_observation\question\question;

global $CFG;
require_once($CFG->dirroot.'/mod/observation/locallib.php');
require_once($CFG->dirroot . '/mod/observation/tests/generator_test.php');
require_once($CFG->dirroot . '/mod/observation/tests/questiontypes_test.php');

/**
 * Unit tests for {@link observation_responsetypes_testcase}.
 * @group mod_observation
 */
class mod_observation_responsetypes_testcase extends advanced_testcase {
    public function test_create_response_boolean() {
        global $DB;

        $this->resetAfterTest();

        // Some common variables used below.
        $userid = 1;

        // Set up a questinnaire with one boolean response question.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $observation = $generator->create_test_observation($course, QUESYESNO, ['content' => 'Enter yes or no']);
        $question = reset($observation->questions);
        $response = $generator->create_question_response($observation, $question, 'y', $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid);

        // Retrieve the specific boolean response.
        $booleanresponses = $DB->get_records('observation_response_bool', ['response_id' => $response->id]);
        $this->assertEquals(1, count($booleanresponses));
        $booleanresponse = reset($booleanresponses);
        $this->assertEquals($question->id, $booleanresponse->question_id);
        $this->assertEquals('y', $booleanresponse->choice_id);
    }

    public function test_create_response_text() {
        global $DB;

        $this->resetAfterTest();

        // Some common variables used below.
        $userid = 1;

        // Set up a observation with one text response question.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $questiondata = ['content' => 'Enter some text', 'length' => 0, 'precise' => 5];
        $observation = $generator->create_test_observation($course, QUESESSAY, $questiondata);
        $question = reset($observation->questions);
        $response = $generator->create_question_response($observation, $question, 'This is my essay.', $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid);

        // Retrieve the specific text response.
        $textresponses = $DB->get_records('observation_response_text', ['response_id' => $response->id]);
        $this->assertEquals(1, count($textresponses));
        $textresponse = reset($textresponses);
        $this->assertEquals($question->id, $textresponse->question_id);
        $this->assertEquals('This is my essay.', $textresponse->response);
    }

    public function test_create_response_date() {
        global $DB;

        $this->resetAfterTest();

        // Some common variables used below.
        $userid = 1;

        // Set up a observation with one text response question.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $observation = $generator->create_test_observation($course, QUESDATE, ['content' => 'Enter a date']);
        $question = reset($observation->questions);
        // Date format is configured per site. This won't work unless it matches the configured format.
        $response = $generator->create_question_response($observation, $question, '2015-01-27', $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid);

        // Retrieve the specific date response.
        $dateresponses = $DB->get_records('observation_response_date', ['response_id' => $response->id]);
        $this->assertEquals(1, count($dateresponses));
        $dateresponse = reset($dateresponses);
        $this->assertEquals($question->id, $dateresponse->question_id);
        // The date is always stored in the database in the same way.
        $this->assertEquals('2015-01-27', $dateresponse->response);
    }

    public function test_create_response_single() {
        global $DB;

        $this->resetAfterTest();

        // Some common variables used below.
        $userid = 1;

        // Set up a questinnaire with one question with choices including an "other" option.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $choicedata = [
            (object)['content' => 'One', 'value' => 1],
            (object)['content' => 'Two', 'value' => 2],
            (object)['content' => 'Three', 'value' => 3],
            (object)['content' => '!other=Something else', 'value' => 4]
        ];
        $observation = $generator->create_test_observation($course, QUESRADIO, ['content' => 'Select one'], $choicedata);

        // Create a response using one of the choices.
        $question = reset($observation->questions);
        $val = 'unknown';
        foreach ($question->choices as $cid => $choice) {
            if ($choice->content == 'Two') {
                $val = $cid;
            }
        }
        $response = $generator->create_question_response($observation, $question, $val, $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid);

        // Retrieve the specific single response.
        $singresponses = $DB->get_records('observation_resp_single', ['response_id' => $response->id]);
        $this->assertEquals(1, count($singresponses));
        $singresponse = reset($singresponses);
        $this->assertEquals($question->id, $singresponse->question_id);
        $this->assertEquals($val, $singresponse->choice_id);

        // Create another response using the '!other' choice.
        foreach ($question->choices as $cid => $choice) {
            if ($choice->content == '!other=Something else') {
                $val = $cid;
            }
        }
        $vals = ['q'.$question->id => $val,
                 'q'.$question->id.\mod_observation\question\choice\choice::id_other_choice_name($val) => 'Forty-four'];
        $userid = 2;
        $response = $generator->create_question_response($observation, $question, $vals, $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid, 1, 2);

        // Retrieve the specific single response.
        $singresponses = $DB->get_records('observation_resp_single', ['response_id' => $response->id]);
        $this->assertEquals(1, count($singresponses));
        $singresponse = reset($singresponses);
        $this->assertEquals($question->id, $singresponse->question_id);
        $this->assertEquals($val, $singresponse->choice_id);

        // Retrieve the 'other' response data.
        $otherresponses = $DB->get_records('observation_response_other',
            ['response_id' => $response->id, 'question_id' => $question->id]);
        $this->assertEquals(1, count($otherresponses));
        $otherresponse = reset($otherresponses);
        $this->assertEquals($val, $otherresponse->choice_id);
        $this->assertEquals('Forty-four', $otherresponse->response);
    }

    public function test_create_response_multiple() {
        global $DB;

        $this->resetAfterTest();

        // Some common variables used below.
        $userid = 1;

        // Set up a observation with one question with choices including an "other" option.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $choicedata = [
            (object)['content' => 'One', 'value' => 1],
            (object)['content' => 'Two', 'value' => 2],
            (object)['content' => 'Three', 'value' => 3],
            (object)['content' => '!other=Another number', 'value' => 4]
        ];
        $observation = $generator->create_test_observation($course, QUESCHECK, ['content' => 'Select any'], $choicedata);

        $question = reset($observation->questions);
        $val = [];
        foreach ($question->choices as $cid => $choice) {
            if (($choice->content == 'Two') || ($choice->content == 'Three')) {
                $val[$cid] = $cid;
            } else if ($choice->content == '!other=Another number') {
                $val[$cid] = $cid;
                $val[\mod_observation\question\choice\choice::id_other_choice_name($cid)] = 'Forty-four';
                $ocid = $cid;
            }
        }
        $vals = ['q'.$question->id => $val];
        $response = $generator->create_question_response($observation, $question, $vals, $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid);

        // Retrieve the specific multiples responses.
        $multresponses = $DB->get_records('observation_resp_multiple', ['response_id' => $response->id]);
        $this->assertEquals(3, count($multresponses));
        $multresponse = reset($multresponses);
        $this->assertEquals($question->id, $multresponse->question_id);
        $this->assertEquals(reset($val), $multresponse->choice_id);
        $multresponse = next($multresponses);
        $this->assertEquals($question->id, $multresponse->question_id);
        $this->assertEquals(next($val), $multresponse->choice_id);

        // Retrieve the specific other response.
        $otherresponses = $DB->get_records('observation_response_other',
            ['response_id' => $response->id, 'question_id' => $question->id]);
        $this->assertEquals(1, count($otherresponses));
        $otherresponse = reset($otherresponses);
        $this->assertEquals($ocid, $otherresponse->choice_id);
        $this->assertEquals('Forty-four', $otherresponse->response);
    }

    public function test_create_response_rank() {
        global $DB;

        $this->resetAfterTest();

        // Some common variables used below.
        $userid = 1;

        // Set up a observation with one ranking question.
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $choicedata = [
            (object)['content' => 'One', 'value' => 1],
            (object)['content' => 'Two', 'value' => 2],
            (object)['content' => 'Three', 'value' => 3]
        ];
        $questiondata = ['content' => 'Rank these', 'length' => 5, 'precise' => 0];
        $observation = $generator->create_test_observation($course, QUESRATE, $questiondata, $choicedata);

        // Create a response for each choice.
        $question = reset($observation->questions);
        $vals = [];
        $i = 1;
        foreach ($question->choices as $cid => $choice) {
            $vals[$cid] = $i;
            $vals['q'.$question->id.'_'.$cid] = $i++;
        }
        $response = $generator->create_question_response($observation, $question, $vals, $userid);

        // Test the responses for this observation.
        $this->response_tests($observation->id, $response->id, $userid);

        // Retrieve the specific rank response.
        $multresponses = $DB->get_records('observation_response_rank', ['response_id' => $response->id]);
        $this->assertEquals(3, count($multresponses));
        foreach ($multresponses as $multresponse) {
            $this->assertEquals($question->id, $multresponse->question_id);
            $this->assertEquals($vals[$multresponse->choice_id], $multresponse->rankvalue);
        }
    }

    // General tests to call from specific tests above.

    public function create_test_observation($qtype, $questiondata = [], $choicedata = null) {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $observation = $generator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('observation', $observation->id);

        $questiondata['type_id'] = $qtype;
        $questiondata['surveyid'] = $observation->sid;
        $questiondata['name'] = isset($questiondata['name']) ? $questiondata['name'] : 'Q1';
        $questiondata['content'] = isset($questiondata['content']) ? $questiondata['content'] : 'Test content';
        $generator->create_question($observation, $questiondata, $choicedata);

        $observation = new observation($observation->id, null, $course, $cm, true);

        return $observation;
    }

    private function response_tests($observationid, $responseid, $userid,
                                    $attemptcount = 1, $responsecount = 1) {
        global $DB;

        $attempts = $DB->get_records('observation_response',
                    ['observationid' => $observationid, 'userid' => $userid, 'id' => $responseid, 'complete' => 'y']);
        $this->assertEquals($attemptcount, count($attempts));
        $responses = $DB->get_records('observation_response', ['observationid' => $observationid]);
        $this->assertEquals($responsecount, count($responses));
        $this->assertArrayHasKey($responseid, $responses);
        $this->assertEquals($responseid, $responses[$responseid]->id);
    }
}
