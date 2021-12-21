<?php 

class chat_trainer_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        require_once("$CFG->libdir/formslib.php");
       
        $mform = $this->_form; // Don't forget the underscore! 

        $mform->addElement('textarea', 'email', get_string('email')); // Add elements to your form
        // $mform->setType('textarea', PARAM_NOTAGS);                   //Set type of element
        // $mform->setDefault('email', 'Please enter email');        //Default value
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}