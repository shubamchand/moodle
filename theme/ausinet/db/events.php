<?php 


$observers = array(

	array(
		'eventname' => 'mod_quiz\event\attempt_submitted',
		'callback' => 'theme_ausinet\attempt_observer::attempt_submitted'
	),

	array(
		'eventname' => 'core\event\course_completed',
		'callback' => 'theme_ausinet\attempt_observer::course_completed'
	)
);

