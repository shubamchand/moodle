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
 * Activity progress reports
 *
 * @package    report
 * @subpackage progress
 * @copyright  2020 Baljit Singh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class notes{
	
	public function get_note($cmcid){
		global $DB;
		$sql = 'SELECT * FROM {report_progress_notes} 
		WHERE cmcid = ?';
		$params = array($cmcid);
		$note = $DB->get_record_sql($sql, $params);
		return $note;
	}
	
	public function is_note($cmcid){
		global $DB;
		if ($DB->record_exists('report_progress_notes', array('cmcid'=>$cmcid))) {
			return true;
		}
		else{
			return false;
		}
	}
	
	public function get_cmcid($cmid,$userid){
		global $DB;
		$cmcid = 0;
		$sql = 'SELECT * FROM {course_modules_completion} 
		WHERE coursemoduleid = ? AND userid = ?';
		$params = array($cmid,$userid);
		$completion = $DB->get_record_sql($sql, $params);
		if($completion){
			$cmcid = $completion->id;
		}
		return $cmcid;
	}
	
	public function get_cmcdetail($cmid,$userid){
		global $DB;
		$sql = 'SELECT * FROM {course_modules_completion} 
		WHERE coursemoduleid = ? AND userid = ?';
		$params = array($cmid,$userid);
		$completion = $DB->get_record_sql($sql, $params);
		if($completion){
			$notedata = self::get_note($completion->id);
			$completion->note = $notedata->note;
			$completion->setby = self::get_user_fullname($completion->overrideby);
		}
		else{
			$completion = "Empty";
		}
		return $completion;
	}
	
	private function get_user_fullname($userid){
		global $DB;
		$sql = 'SELECT * FROM {user} 
		WHERE id = ?';
		$params = array($userid);
		$user = $DB->get_record_sql($sql, $params);
		if($user){
			$fullname = $user->firstname . " " . $user->lastname;
		}
		else{
			$fullname = "";
		}
		return $fullname;
	}
	
	public function save_changes($changecompl, $notetext, $smode){
		global $USER;
		
		//Extract all IDs
		$id = explode("-",$changecompl);
		$cmcid = $id[0];
		$userid = $id[1];
		$cmid = $id[2];
		$newstate = $id[3];
		$noteid = $id[4];
		$courseid = $id[5];
		$sectionid = $id[6];
		
		$note = new StdClass;
		$note->note = $notetext;
		
		$cmc = new StdClass;
		$cmc->userid = $userid;
		$cmc->completionstate = $newstate;
		$cmc->viewed = 0;
		$cmc->overrideby = $USER->id;
		$cmc->timemodified = time();
		
		$result = false;
		
		//Saving/Updating for single activity
		if($smode === "single"){
			$cmc->coursemoduleid = $cmid;
			if($cmcid>0){
				$note->cmcid = $cmcid;
				if(self::update_completion($cmcid, $cmc)===true){				
					if($noteid>0){
						if(self::update_note($noteid, $note)===true){
							$result = true;
						}
					}
					else{
						if(self::add_note($note) > 0){
							$result = true;
						}
					}
				}
			}
			else{
				$cmcid = self::add_completion($cmc);
				if($cmcid > 0){
					$note->cmcid = $cmcid;
					if(self::add_note($note) > 0){
						$result = true;
					}
				}
			}
		}
		else{
			
		//Saving or Updating for whole section activities
			$activities = self::get_activities_by_section($courseid, $sectionid);
			foreach($activities as $cm){
				$cmc->coursemoduleid = $cm->id;
				$cmcid = self::get_cmcid($cm->id, $userid);
				if($cmcid>0){
					$note->cmcid = $cmcid;
					if(self::update_completion($cmcid, $cmc)===true){
						$notedata = self::get_note($cmcid);
						$noteid = $notedata->id;
						if($noteid>0){
							if(self::update_note($noteid, $note)===true){
								$result = true;
							}
						}
						else{
							if(self::add_note($note) > 0){
								$result = true;
							}
						}
					}
				}
				else{
					$cmcid = self::add_completion($cmc);
					if($cmcid > 0){
						$note->cmcid = $cmcid;
						if(self::add_note($note) > 0){
							$result = true;
						}
					}
				}
			}
		}

		return $result;
	}
	
	public function get_section($sectionid){
		global $DB;
		$sql = 'SELECT * FROM {course_sections} 
		WHERE id = ?';
		$params = array($sectionid);
		$section = $DB->get_record_sql($sql, $params);
		return $section;	
	}
	
	private function get_activities_by_section($courseid, $sectionid){
		global $DB;
		$sql = 'SELECT * FROM {course_modules} 
		WHERE course = ? AND section = ?';
		$params = array($courseid, $sectionid);
		$activities = $DB->get_records_sql($sql, $params);
		return $activities;
	}
	
	private function add_note($note){
		global $DB;
		$result = $DB->insert_record('report_progress_notes',$note);
		return $result;
	}
	
	private function add_completion($cmc){
		global $DB;
		$result = $DB->insert_record('course_modules_completion',$cmc);
		return $result;
	}
	
	private function update_completion($cmcid, $cmc){
		global $DB;
		$sql = 'UPDATE {course_modules_completion} 
		SET coursemoduleid = '. $cmc->coursemoduleid .',
		userid = '. $cmc->userid .',
		completionstate = '. $cmc->completionstate .',
		viewed = '. $cmc->viewed .',
		overrideby = '. $cmc->overrideby .',
		timemodified = '. $cmc->timemodified .' 
		WHERE id = ?';
		$params = array($cmcid);
		$result = $DB->execute($sql, $params);
		$this->updateObservation($cmcid, $cmc); // added by nirmal
		return $result;
	}

	// added by nirmal for observation
	public function updateObservation($cmcid, $cmc){
		global $DB;
		$check_record = $DB->get_record('course_modules_completion_observation',
			array(
				'coursemoduleid' => $cmc->coursemoduleid,
				'userid' => $cmc->userid
			),
			'*'
		);
		if($check_record){
			$sql = 'UPDATE {course_modules_completion_observation} 
			SET completionstate = '. $cmc->completionstate .'
			WHERE coursemoduleid = ? and userid = ?';
		
			$params = array($cmc->coursemoduleid,$cmc->userid);
			$result = $DB->execute($sql, $params);
			return $result;
		}	
	}
	
	private function update_note($noteid, $note){
		global $DB;
		$sql = "UPDATE {report_progress_notes}
		SET note = '" . $note->note . "' 
		WHERE id = ? AND cmcid = ?";
		$params = array($noteid, $note->cmcid);
		$result = $DB->execute($sql, $params);
		return $result;
	}
}

$notes = new notes();