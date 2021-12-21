<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'electricalcourses';
$CFG->dbuser    = 'root';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8_general_ci',
);

$CFG->wwwroot   = 'http://localhost/public_html';
$CFG->dataroot  = 'E:\\Ausinet\\xampp\\moodledata';
$CFG->admin     = 'admin';

$CFG->traineremail = 'trainer@ausinet.com.au.test-google-a.com';

// $CFG->noreplyaddress = 'trainer@ausinet.com.au';
$CFG->supportname = "Trainer Ausinet";
$CFG->shortname = "Ausinet";
// $CFG->emailfromvia = "Trainer Ausinet";

$CFG->directorypermissions = 0777;
//$CFG->debug = 2047; 
//$CFG->debugdisplay = 1;
$CFG->alternative_file_system_class ='\tool_objectfs\s3_file_system';

require_once(__DIR__ . '/lib/setup.php');

// Custom theme function to show the login username in form.
$file = $CFG->dirroot.'/theme/ausinet/custom.php';
if (file_exists($file)) {
	require_once($file);
}
// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!

// set_config('overridetossl', 1);
