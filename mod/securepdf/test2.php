<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(20000);
ini_set('memory_limit', '128M');

$file = fopen("javanotes5.pdf", "r");
$imagick = new Imagick();


 $numpages = $imagick->getNumberImages();
 try {
       $imagick->readImageFile($file);
    } catch (Exception $e) {
        var_dump($e);die;
    }

