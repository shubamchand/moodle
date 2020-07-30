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
 * Observation external functions and service definitions.
 *
 * @package    mod_observation
 * @category   external
 * @copyright  2018 Igor Sazonov <sovletig@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$services = [
    'mod_observation_ws' => [
        'functions' => ['mod_observation_submit_observation_response'],
        'requiredcapability' => '',
        'enabled' => 1
    ]
];

$functions = [
    'mod_observation_submit_observation_response' => [
        'classname' => 'mod_observation_external',
        'methodname' => 'submit_observation_response',
        'classpath' => 'mod/observation/externallib.php',
        'description' => 'Observation submit',
        'type' => 'write',
        'capabilities' => 'mod/observation:submit',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ]
];