<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'ausinet6_moodleLMS';
$CFG->dbuser    = 'ausinet6_moodleL';
$CFG->dbpass    = 'moodleL123#@!';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8_general_ci',
);

$CFG->wwwroot   = 'http://ausinet.net.au';
$CFG->dataroot  = '/home/ausinet6/public_html/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');

// Custom theme function to show the login username in form.
$file = $CFG->dirroot.'/theme/ausinet/custom.php';
if (file_exists($file)) {
	require_once($file);
}
// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
