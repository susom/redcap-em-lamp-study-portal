<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$image_payload = $module->fetchImages();

if(!empty($image_payload)){
    ?>
    <div class = 'row' style="margin-bottom: 20px;">
    <?php
    foreach($image_payload as $index => $image){
        if ($index % 2 == 0 && $index != 0) { //new row entry
            ?>
                </div><div class = 'row' style="margin-bottom: 20px;">
                    <div class = 'col-lg-6'>
                        <div class = 'card text-center' style="background-color: rgb(241,241,241)">
                            <div class = 'card-content' style="margin-top:10px;">
                                <img src="<?php echo $image['photo_binary'];?>" style="max-width: 400px; max-height: 400px;">
                            </div>
                            <div class = 'card-body' task_uuid = <?php echo $image['task_uuid']; ?> user_uuid = <?php echo $image['user_uuid']; ?>>
                                <button class="btn btn-success agree">Positive</button>
                                <button class="btn btn-danger disagree">Negative</button>
                            </div>
                        </div>
                    </div>
            <?php
        } else { //not a new row
            ?>
                <div class = 'col-lg-6'>
                    <div class = 'card text-center' style="background-color: rgb(241,241,241)">
                        <div class = 'card-content' style="margin-top:10px;">
                            <img src="<?php echo $image['photo_binary'];?>" style="max-width: 400px; max-height: 400px;">
                        </div>
                        <div class = 'card-body' task_uuid = <?php echo $image['task_uuid']; ?> user_uuid = <?php echo $image['user_uuid']; ?>>
                            <button class="btn btn-success agree">Positive</button>
                            <button class="btn btn-danger disagree">Negative</button>
                        </div>
                    </div>
                </div>
            <?php
        }
    }
} else {
    ?>
    <div class="alert alert-warning" role="alert" style="margin-right: 20px;">
        No current images found needing adjudication
    </div>
    <?php
}


?>

<script src="<?php echo $module->getUrl('src/js/config.js'); ?>"></script>
<script>
    LAMP.data = <?php echo json_encode($image_payload); ?>;
</script>

