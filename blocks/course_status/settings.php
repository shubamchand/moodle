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
 *
 * Settings for Block course status.
 *
 * @package   block_course_status
 * @copyright 2018 Manoj Solanki (Coventry University)
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('block_course_status/title',
            get_string('title', 'block_course_status'),
            get_string('titledesc', 'block_course_status'), '', PARAM_TEXT));

    $settings->add(new admin_setting_heading(
            'iconconfig',
            get_string('iconconfig', 'block_course_status'),
            get_string('iconconfigdescription', 'block_course_status')
            ));

    $settings->add(new admin_setting_configtext('block_course_status/publishedicon',
            get_string('publishedicontext', 'block_course_status'), get_string('publishedicondescription', 'block_course_status'),
            get_string('publishedicon', 'block_course_status'), PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_course_status/unpublishedicon',
            get_string('unpublishedicontext', 'block_course_status'),
            get_string('unpublishedicondescription', 'block_course_status'),
            get_string('unpublishedicon', 'block_course_status'), PARAM_TEXT));

}