<?php 

require_once(__DIR__.'/../../config.php');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/report/ausinet') );

$PAGE->set_pagelayout('admin');

$PAGE->set_heading('Admin reports');

require_login();

// report locallib.php.
require_once($CFG->dirroot.'/report/ausinet/locallib.php');

// Check the course report requested.
$course = optional_param('course', '', PARAM_INT);
$user = optional_param('user', '', PARAM_INT);
$type = optional_param('type', '', PARAM_INT);

if (is_siteadmin()) {
	if (!empty($course)) {
		$report['courseactive'] = 'show active';
	} else {
		$report['siteactive'] = 'show active';
	}
	$report['sitereports'] = \report_ausinet\site_report::get_blocks();
	// $report['userreports'] = \report_ausinet\user_report::get_blocks();
	$report['coursereports'] = \report_ausinet\course_report::get_blocks($course, '', 'student');

} else if (!empty($course)) { 
	// check the loggedin user current role in the course.
	$coursecontext = context_course::instance($course);	 // Course context.	
	$userrole = current(get_user_roles($coursecontext, $USER->id))->shortname;
	// If logged in user role is student in the requested course, return the student report in the course.	
	if ($userrole == 'student') { 
		$report['coursereports'] = \report_ausinet\course_report::get_blocks($course, $USER->id, 'student');
		$report['courseactive'] = 'show active';
	} 
	$PAGE->set_context($coursecontext);

} else if ( $type == 'userinfo' ) {
	$user = ($user) ? $user : $USER->id;
	$report['useractive'] = 'show active';
	$report['userreports'] = \report_ausinet\user_report::get_blocks($user);

} else {
	$sitecontext = context_system::instance();
	require_capability('moodle/user:update', $sitecontext);
}

echo $OUTPUT->header();

// Site reports only for admin.






// echo $list;
echo '<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" ></script>;
<link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" ></link>';

$PAGE->requires->js('/report/ausinet/javascript/ausinet.js');
// $blocks['site_report'] = \report_ausinet\

echo $OUTPUT->render_from_template('report_ausinet/reports', $report);


?>
<script type="text/javascript">
	$(document).ready(function() {
		$(".assignment-block .generaltable").DataTable();
		$(".quiz-block .generaltable").DataTable();
		$(".enrol-courses-block .generaltable").DataTable();

	})
</script>

<?php
echo $OUTPUT->footer();