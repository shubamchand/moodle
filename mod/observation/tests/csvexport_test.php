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
 * Test performance of observation.
 * @author    Guy Thomas
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Performance test for observation module.
 * @group mod_observation
 * @author     Guy Thomas
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_observation_csvexport_test extends advanced_testcase {

    public function setUp() {
        global $CFG;

        require_once($CFG->dirroot.'/lib/testing/generator/data_generator.php');
        require_once($CFG->dirroot.'/lib/testing/generator/component_generator_base.php');
        require_once($CFG->dirroot.'/lib/testing/generator/module_generator.php');
    }

    /**
     * Get csv text
     *
     * @param array $rows
     * @return string
     */
    private function get_csv_text(array $rows) {
        $lines = [];
        foreach ($rows as $row) {
            // Remove the id and date fields.
            unset($row[0]);
            unset($row[1]);
            unset($row[6]);
            $text = implode("\t", $row);
            $lines[] = $text;
        }
        return $lines;
    }

    public function test_csvexport() {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $qdg = $dg->get_plugin_generator('mod_observation');
        $qdg->create_and_fully_populate(1, 5, 1, 1);

        // The following line simply.
        $observations = $qdg->observations();
        foreach ($observations as $observation) {
            list ($course, $cm) = get_course_and_cm_from_instance($observation->id, 'observation', $observation->course);
            $observationinst = new observation(0, $observation, $course, $cm);

            // Test for only complete responses.
            $newoutput = $this->get_csv_text($observationinst->generate_csv('', '', 0, 0, 0, 0));
            $this->assertEquals(count($newoutput), count($this->expected_complete_output()));
            foreach ($newoutput as $key => $output) {
                $this->assertEquals($this->expected_complete_output()[$key], $output);
            }

            // Test for all responses.
            $newoutput = $this->get_csv_text($observationinst->generate_csv('', '', 0, 0, 0, 1));
            $this->assertEquals(count($newoutput), count($this->expected_incomplete_output()));
            foreach ($newoutput as $key => $output) {
                $this->assertEquals($this->expected_incomplete_output()[$key], $output);
            }
        }
    }

    private function expected_complete_output() {
        return ["Institution	Department	Course	Group	Full name	Username	Q01_Text Box 1000	Q02_Essay Box 1002	" .
            "Q03_Numeric 1004	Q04_Date 1006	Q05_Radio Buttons 1008	Q06_Drop Down 1010	Q07_Check Boxes 1012->four	" .
            "Q07_Check Boxes 1012->five	Q07_Check Boxes 1012->six	Q07_Check Boxes 1012->seven	Q07_Check Boxes 1012->eight	" .
            "Q07_Check Boxes 1012->nine	Q07_Check Boxes 1012->ten	Q07_Check Boxes 1012->eleven	" .
            "Q07_Check Boxes 1012->twelve	Q07_Check Boxes 1012->thirteen	Q08_Rate Scale 1014->fourteen	" .
            "Q08_Rate Scale 1014->fifteen	Q08_Rate Scale 1014->sixteen	Q08_Rate Scale 1014->seventeen	" .
            "Q08_Rate Scale 1014->eighteen	Q08_Rate Scale 1014->nineteen	Q08_Rate Scale 1014->twenty	" .
            "Q08_Rate Scale 1014->happy	Q08_Rate Scale 1014->sad	Q08_Rate Scale 1014->jealous",
            "		Test course 1		Testy Lastname1	username1	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname2	username2	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname3	username3	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname4	username4	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	"];
    }

    private function expected_incomplete_output() {
        return ["Institution	Department	Course	Group	Full name	Username	Complete	Q01_Text Box 1000	" .
            "Q02_Essay Box 1002	" .
            "Q03_Numeric 1004	Q04_Date 1006	Q05_Radio Buttons 1008	Q06_Drop Down 1010	Q07_Check Boxes 1012->four	" .
            "Q07_Check Boxes 1012->five	Q07_Check Boxes 1012->six	Q07_Check Boxes 1012->seven	Q07_Check Boxes 1012->eight	" .
            "Q07_Check Boxes 1012->nine	Q07_Check Boxes 1012->ten	Q07_Check Boxes 1012->eleven	" .
            "Q07_Check Boxes 1012->twelve	Q07_Check Boxes 1012->thirteen	Q08_Rate Scale 1014->fourteen	" .
            "Q08_Rate Scale 1014->fifteen	Q08_Rate Scale 1014->sixteen	Q08_Rate Scale 1014->seventeen	" .
            "Q08_Rate Scale 1014->eighteen	Q08_Rate Scale 1014->nineteen	Q08_Rate Scale 1014->twenty	" .
            "Q08_Rate Scale 1014->happy	Q08_Rate Scale 1014->sad	Q08_Rate Scale 1014->jealous",
            "		Test course 1		Testy Lastname1	username1	y	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname2	username2	y	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname3	username3	y	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname4	username4	y	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	",
            "		Test course 1		Testy Lastname5	username5	n	Test answer	Some header textSome paragraph text	83	" .
            "27/12/2017	wind	three	0	0	0	0	0	0	0	0	0	1	1	2	3	4	5	1	2	3	4	"];
    }
}