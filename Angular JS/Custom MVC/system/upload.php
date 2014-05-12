<?php

require_once 'class.upload.php';

$uploader = new Upload($_FILES['file']);
if ($uploader->uploaded) {
  
  $uploader->allowed = array('image/jpeg', 'image/png', 'image/gif');
  
  $uploader->image_resize = true;
  $uploader->image_x = 300;
  $uploader->image_ratio_y = true;
  $uploader->Process('../uploads');
  if ($uploader->processed) {
    echo 'image resized x=300';
    $uploader->Clean();
  } else {
    echo 'error : ' . $uploader->error;
  }
}

?>