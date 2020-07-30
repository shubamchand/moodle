<?php 

class selectusers extends moodleform {

	function definition() {
		global $CFG;
		$mform = $this->_form;

		$users = $this->_customdata['users'];
		$areanames = array();
		$areanames[] = 'All';
		foreach ($users as $areaid => $user) {                         
		    $areanames[$user->id] = fullname($user);               
		}                                                                                                                           
		$options = array(                         
		    'multiple' => false,
		    'noselectionstring' => get_string('allareas', 'search'),                                                                
		);         
		$mform->addElement('autocomplete', 'user', 'Select student:', $areanames, $options);
		$mform->setDefault('user', $this->_customdata['user']);

		$this->add_action_buttons(false, 'Get');
	}
}

