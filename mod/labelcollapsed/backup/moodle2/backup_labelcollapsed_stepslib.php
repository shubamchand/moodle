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
 * @package mod_labelcollapsed
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Define the complete labelcollapsed structure for backup, with file and id annotations
 */

class backup_labelcollapsed_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $labelcollapsed = new backup_nested_element('labelcollapsed', array('id'), array(
            'labelsection', 'sectioncolor', 'sectionbgcolor', 'name', 'intro', 'introformat', 'timemodified'));

        // Build the tree.

        // Define sources.
        $labelcollapsed->set_source_table('labelcollapsed', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations.
        // None.

        // Define file annotations.
        $labelcollapsed->annotate_files('mod_labelcollapsed', 'intro', null); // This file area hasn't itemid.

        // Return the root element (labelcollapsed), wrapped into standard activity structure.
        return $this->prepare_activity_structure($labelcollapsed);
    }
}
