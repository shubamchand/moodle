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
 * Mobile output class for mod_observation.
 *
 * @copyright 2018 Igor Sazonov <sovletig@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_observation\output;

defined('MOODLE_INTERNAL') || die();

class mobile {

    /**
     * Returns the initial page when viewing the activity for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_view_activity($args) {
        global $OUTPUT, $USER, $CFG, $DB;
        require_once($CFG->dirroot.'/mod/observation/observation.class.php');

        $args = (object) $args;

        $cmid = $args->cmid;
        $rid = isset($args->rid) ? $args->rid : 0;
        $action = isset($args->action) ? $args->action : 'index';
        $pagenum = (isset($args->pagenum) && !empty($args->pagenum)) ? intval($args->pagenum) : 1;
        $userid = isset($args->userid) ? $args->userid : $USER->id;
        $submit = isset($args->submit) ? $args->submit : false;
        $completed = isset($args->completed) ? $args->completed : false;

        list($cm, $course, $observation) = observation_get_standard_page_items($cmid);
        $observation = new \observation(0, $observation, $course, $cm);

        $data = [];
        $data['cmid'] = $cmid;
        $data['userid'] = $userid;
        $data['intro'] = $observation->intro;
        $data['autonumquestions'] = $observation->autonum;
        $data['id'] = $observation->id;
        $data['rid'] = $rid;
        $data['surveyid'] = $observation->survey->id;
        $data['pagenum'] = $pagenum;
        $data['prevpage'] = 0;
        $data['nextpage'] = 0;

        // Capabilities check.
        $context = \context_module::instance($cmid);
        self::require_capability($cm, $context, 'mod/observation:view');

        // Any notifications will be displayed on top of main page, and prevent observation from being completed. This also checks
        // appropriate capabilities.
        $data['notifications'] = $observation->user_access_messages($userid);
        $responses = [];
        $result = '';

        $data['emptypage'] = 1;
        $template = 'mod_observation/mobile_main_index_page';

        switch ($action) {
            case 'index':
                self::add_index_data($observation, $data, $userid);
                $template = 'mod_observation/mobile_main_index_page';
                break;

            case 'submit':
            case 'nextpage':
            case 'previouspage':
                if (!$data['notifications']) {
                    $result = $observation->save_mobile_data($userid, $pagenum, $completed, $rid, $submit, $action, (array)$args);
                }

            case 'respond':
            case 'resume':
                // Completing a observation.
                if (!$data['notifications']) {
                    if ($observation->user_has_saved_response($userid)) {
                        if (empty($rid)) {
                            $rid = $observation->get_latest_responseid($userid);
                        }
                        $observation->add_response($rid);
                        $data['rid'] = $rid;
                    }
                    $response = (isset($observation->responses) && !empty($observation->responses)) ?
                        end($observation->responses) : \mod_observation\responsetype\response\response::create_from_data([]);
                    $response->sec = $pagenum;
                    if (isset($result['warnings'])) {
                        if ($action == 'submit') {
                            $response = $result['response'];
                        }
                        $data['notifications'] = $result['warnings'];
                    } else if ($action == 'nextpage') {
                        $pageresult = $result['nextpagenum'];
                        if ($pageresult === false) {
                            $pagenum = count($observation->questionsbysec);
                        } else if (is_string($pageresult)) {
                            $data['notifications'] .= !empty($data['notifications']) ? "\n<br />$pageresult" : $pageresult;
                        } else {
                            $pagenum = $pageresult;
                        }
                    } else if ($action == 'previouspage') {
                        $prevpage = $result['nextpagenum'];
                        if ($prevpage === false) {
                            $pagenum = 1;
                        } else {
                            $pagenum = $prevpage;
                        }
                    } else if ($action == 'submit') {
                        self::add_index_data($observation, $data, $userid);
                        $data['action'] = 'index';
                        $template = 'mod_observation/mobile_main_index_page';
                        break;
                    }
                    $pagequestiondata = self::add_pagequestion_data($observation, $pagenum, $response);
                    $data['pagequestions'] = $pagequestiondata['pagequestions'];
                    $responses = $pagequestiondata['responses'];
                    $numpages = count($observation->questionsbysec);
                    // Set some variables we are going to be using.
                    if (!empty($observation->questionsbysec) && ($numpages > 1)) {
                        if ($pagenum > 1) {
                            $data['prevpage'] = true;
                        }
                        if ($pagenum < $numpages) {
                            $data['nextpage'] = true;
                        }
                    }
                    $data['pagenum'] = $pagenum;
                    $data['completed'] = 0;
                    $data['emptypage'] = 0;
                    $template = 'mod_observation/mobile_view_activity_page';
                }
                break;

            case 'review':
                // If reviewing a submission.
                if ($observation->capabilities->readownresponses && isset($args->submissionid) && !empty($args->submissionid)) {
                    $observation->add_response($args->submissionid);
                    $response = $observation->responses[$args->submissionid];
                    $qnum = 1;
                    $pagequestions = [];
                    foreach ($observation->questions as $question) {
                        if ($question->supports_mobile()) {
                            $pagequestions[] = $question->mobile_question_display($qnum, $observation->autonum);
                            $responses = array_merge($responses, $question->get_mobile_response_data($response));
                            $qnum++;
                        }
                    }
                    $data['prevpage'] = 0;
                    $data['nextpage'] = 0;
                    $data['pagequestions'] = $pagequestions;
                    $data['completed'] = 1;
                    $data['emptypage'] = 0;
                    $template = 'mod_observation/mobile_view_activity_page';
                }
                break;
        }

        $return = [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template($template, $data)
                ],
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/mod/observation/appjs/uncheckother.js'),
            'otherdata' => $responses,
            'files' => null
        ];
        return $return;
    }

    /**
     * Confirms the user is logged in and has the specified capability.
     *
     * @param \stdClass $cm
     * @param \context $context
     * @param string $cap
     */
    protected static function require_capability(\stdClass $cm, \context $context, string $cap) {
        require_login($cm->course, false, $cm, true, true);
        require_capability($cap, $context);
    }

    /**
     * @param $observation
     * @param $data
     */
    protected static function add_index_data($observation, &$data, $userid) {
        // List any existing submissions, if user is allowed to review them.
        if ($observation->capabilities->readownresponses) {
            $observation->add_user_responses();
            $submissions = [];
            foreach ($observation->responses as $response) {
                $submissions[] = ['submissiondate' => userdate($response->submitted), 'submissionid' => $response->id];
            }
            if (!empty($submissions)) {
                $data['submissions'] = $submissions;
            } else {
                $data['emptypage'] = 1;
            }
            if ($observation->user_has_saved_response($userid)) {
                $data['resume'] = 1;
            }
            $data['emptypage'] = 0;
        }
    }

    /**
     * @param $observation
     * @param $pagenum
     * @param null $response
     * @return array
     */
    protected static function add_pagequestion_data($observation, $pagenum, $response=null) {
        $qnum = 1;
        $pagequestions = [];
        $responses = [];

        // Find out what question number we are on $i New fix for question numbering.
        $i = 0;
        if ($pagenum > 1) {
            for ($j = 2; $j <= $pagenum; $j++) {
                foreach ($observation->questionsbysec[$j - 1] as $questionid) {
                    if ($observation->questions[$questionid]->type_id < QUESPAGEBREAK) {
                        $i++;
                    }
                }
            }
        }
        $qnum = $i + 1;

        foreach ($observation->questionsbysec[$pagenum] as $questionid) {
            $question = $observation->questions[$questionid];
            if ($question->supports_mobile()) {
                $pagequestions[] = $question->mobile_question_display($qnum, $observation->autonum);
                if (($response !== null) && isset($response->answers[$questionid])) {
                    $responses = array_merge($responses, $question->get_mobile_response_data($response));
                }
            }
            $qnum++;
        }

        return ['pagequestions' => $pagequestions, 'responses' => $responses];
    }
}