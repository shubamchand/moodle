<?php
require('config.php');
global $DB;
$date = new DateTime();
        $unixdatetime = $date->getTimestamp();
        // Example of using question mark placeholders.
        $query = $DB->get_records_sql('SELECT userid FROM mdl_user_enrolments ue WHERE (ue.timeend > '.$unixdatetime.' OR ue.timeend = 0 AND ue.status = 0) AND ue.enrolid = 123', array());
        
        // $context = get_context_instance(CONTEXT_COURSE,$COURSE->id);
// $roles = get_user_roles($context, $USER->id);
foreach($query as $record){
    //echo "Not empty";
   echo $record->userid; ?><br>
<?php }

?>