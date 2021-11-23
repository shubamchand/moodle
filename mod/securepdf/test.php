<?php 
  $imagick = new Imagick(); 
  $imagick->readImage('javanotes5.pdf'); 
  $imagick->setImageFormat('jpeg'); 
  $imagick->writeImages('sg_test.jpg'); 
  $imagick->clear();
  $imagick->destroy();
?>
