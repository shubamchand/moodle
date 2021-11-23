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
 * Adaptatation of Weeks course format.  Display the whole course as "weeks" made of modules.
 *
 * @package format_collapsibleweeks
 * @copyright  2018 University of Namur - Cellule TICE
 * @copyright 2006 The Open University
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Make sure section 0 is created.
$course = course_get_format($course)->get_course();
course_create_sections_if_missing($course, 0);

$renderer = $PAGE->get_renderer('format_collapsibleweeks');

$renderer->print_multiple_section_page($course, null, null, null, null);

$params = [
    'course' => $course->id,
    'keepstateoversession' => get_config('format_collapsibleweeks', 'keepstateoversession')
];

// Include course format js module.
$PAGE->requires->js('/course/format/collapsibleweeks/format.js');
$PAGE->requires->js_call_amd('format_collapsibleweeks/collapsibleweeks', 'init', array($params));

