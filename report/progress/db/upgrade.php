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
 * This file keeps track of upgrades to the manual enrolment plugin
 *
 * @package    Report Progress
 * @copyright  2020 Baljit Singh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_report_progress_upgrade($oldversion) {
    global $CFG, $DB;
 
    require_once($CFG->libdir.'/db/upgradelib.php'); // Core Upgrade-related functions.

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.
	
	$result = TRUE;
 
	if ($oldversion < 2019111910) {

         // Define table report_progress_notes to be created.
        $table = new xmldb_table('report_progress_notes');

        // Adding fields to table report_progress_notes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmcid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table report_progress_notes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for report_progress_notes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Performance_review savepoint reached.
        upgrade_plugin_savepoint(true, 2019111910, 'local', 'report_progress');
    }
 
    return $result;
}