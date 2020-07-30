<?php 

namespace theme_ausinet;

class selectusers extends \moodleform {

	function definition() {
		global $CFG;
		$mform = $this->_form;

		$users = $this->_customdata['users'];
		$cmid = $this->_customdata['cmid'];
		$type = $this->_customdata['type'];

		$areanames = array();
		// $areanames[] = 'All';
		foreach ($users as $areaid => $user) {                         
		    $areanames[$user->id] = fullname($user);               
		}                                                                                                                           
		$options = array(                         
		    'multiple' => true,
		    // 'noselectionstring' => get_string('noselection', 'search'),                                                                
		);        

		$mform->addElement('header', 'main', 'Restrict users' ) ;

		$methods = array();
		$methods[] = $mform->createElement('radio', 'method', '', get_string('all'), 1);
		$methods[] = $mform->createElement('radio', 'method', '', get_string('selectedusers', 'theme_ausinet'), 2);
		$mform->addGroup($methods, 'method', 'Select method', array(' '), false);

		$mform->setDefault('method', 1);

		$mform->addElement('autocomplete', 'students', 'Select users', $areanames, $options);
		$mform->disabledIf('students', 'method', 'neq', 2);

		$actions = ['lock' => 'Lock', 'unlock' => 'Unlock'];
		$mform->addElement('select', 'action', 'Action', $actions);

		// Id for module or section.
		$mform->addElement('hidden', 'cmid');
		$mform->setDefault('cmid', $cmid);
		// Type for the lock field
		$mform->addElement('hidden', 'type');
		$mform->setDefault('type', $type);
		// $mform->setDefault('user', $this->_customdata['user']);

		// $this->add_action_buttons(false, 'Get');
	}
}