<?php
$to = "munder2013@live.com";
$sub = "Ausinet Cron Test";
$msg = "This is test email sent by Ausinet cron";
$s=mail($to,$sub,$msg);
if($s){
    echo "Sent";
}
else{
    echo "Fail";
}

?>