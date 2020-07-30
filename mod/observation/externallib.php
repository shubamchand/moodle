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
 * Observation module external API
 *
 * @package    mod_observation
 * @category   external
 * @copyright  2018 Igor Sazonov <sovletig@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/observation.class.php');

/**
 * Observation module external functions
 *
 * @package    mod_observation
 * @category   external
 * @copyright  2018 Igor Sazonov <sovletig@yandex.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_observation_external extends \external_api {

    /**
     * Describes the parameters for submit_observation_response.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function submit_observation_response_parameters() {
        return new \external_function_parameters(
            [
                'observationid' => new \external_value(PARAM_INT, 'Observation instance id'),
                'surveyid' => new \external_value(PARAM_INT, 'Survey id'),
                'userid' => new \external_value(PARAM_INT, 'User id'),
                'cmid' => new \external_value(PARAM_INT, 'Course module id'),
                'sec' => new \external_value(PARAM_INT, 'Section number'),
                'completed' => new \external_value(PARAM_INT, 'Completed survey or not'),
                'rid' => new \external_value(PARAM_INT, 'Existing response id'),
                'submit' => new \external_value(PARAM_INT, 'Submit survey or not'),
                'action' => new \external_value(PARAM_ALPHA, 'Page action'),
                'responses' => new \external_multiple_structure(
                    new \external_single_structure(
                        [
                            'name' => new \external_value(PARAM_RAW, 'data key'),
                            'value' => new \external_value(PARAM_RAW, 'data value')
                        ]
                    ),
                    'The data to be saved', VALUE_DEFAULT, []
                )
            ]
        );
    }

    /**
     * Submit observation responses
     *
     * @param int $observationid the observation instance id
     * @param int $surveyid Survey id
     * @param int $userid User id
     * @param int $cmid Course module id
     * @param int $sec Section number
     * @param int $completed Completed survey 1/0
     * @param int $rid Already in progress response id.
     * @param int $submit Submit survey?
     * @param array $responses the response ids
     * @return array answers information and warnings
     * @since Moodle 3.0
     */
    public static function submit_observation_response($observationid, $surveyid, $userid, $cmid, $sec, $completed, $rid,
                                                         $submit, $action, $responses) {
        self::validate_parameters(self::submit_observation_response_parameters(),
            [
                'observationid' => $observationid,
                'surveyid' => $surveyid,
                'userid' => $userid,
                'cmid' => $cmid,
                'sec' => $sec,
                'completed' => $completed,
                'rid' => $rid,
                'submit' => $submit,
                'action' => $action,
                'responses' => $responses
            ]
        );

        list($cm, $course, $observation) = observation_get_standard_page_items($cmid);
        $observation = new \observation(0, $observation, $course, $cm);

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/observation:submit', $context);

        $result = $observation->save_mobile_data($userid, $sec, $completed, $rid, $submit, $action, $responses);
        $result['submitted'] = true;
        if (isset($result['warnings']) && !empty($result['warnings'])) {
            unset($result['responses']);
            $result['submitted'] = false;
        }
        $result['warnings'] = [];
        return $result;
    }

    /**
     * Describes the submit_observation_response return value.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function submit_observation_response_returns() {
        return new \external_single_structure(
            [
                'submitted' => new \external_value(PARAM_BOOL, 'submitted', true, false, false),
                'warnings' => new \external_warnings()
            ]
        );
    }
}