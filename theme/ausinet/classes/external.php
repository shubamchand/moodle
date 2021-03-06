<?php 

namespace theme_ausinet;

require_once("$CFG->libdir/externallib.php");


class external extends \external_api {

	 public static function restrict_users_parameters() {
	 	return new \external_function_parameters(
            array(
                'contextid' => new \external_value(PARAM_INT, 'The context id for the course'),
                'formdata' => new \external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
	}

	public static function restrict_users($contextid, $formdata) {
		global $CFG;
		require_once($CFG->dirroot.'/theme/ausinet/locallib.php');
		// Parse serialize form data.
		parse_str($formdata, $data);

		$result = new \restrict_users($data);	
	
		return true;
	}

	public static function restrict_users_returns() {

		return new \external_value(PARAM_INT, 'group id');
	}
}