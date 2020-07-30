<?php 


/* Custom scripts are run on every page */

if (isset($_POST['loginform'])) {
    global $SESSION;
    $form_username = optional_param('username', null, PARAM_RAW);
    $SESSION->formuser = $form_username;
    // echo $SESSION->formuser;exit;
}
