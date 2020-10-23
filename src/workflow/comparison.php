<?php

$image_payload = $module->fetchImages();
foreach($image_payload as $index => $image){
    ?> <img src="<?php echo $image['photo_binary']; ?>" style="max-width:300px;" /> <?php
}
?>
<!--<img src="--><?php //echo $image_payload[0]['photo_binary']; ?><!--" alt="An elephant" />-->
