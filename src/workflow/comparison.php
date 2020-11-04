<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

/**
 * @var \Stanford\LampStudyPortal\LampStudyPortal $module
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module->processPatients = false;
    $module->initialize();
    $module->getWorkflow()->updateTask(
        filter_var($_POST['user_uuid'], FILTER_SANITIZE_STRING),
        filter_var($_POST['task_uuid'], FILTER_SANITIZE_STRING),
        filter_var($_POST['type'], FILTER_SANITIZE_STRING)
    );
}


$image_payload = $module->fetchImages();

if(!empty($image_payload)){
    ?>

        <nav class="navbar navbar-light bg-light">
            <span class="navbar-text">
                Total images to be adjudicated:
                    <strong id ="adjudicationCount"><?php echo sizeof($image_payload) ?></strong>
            </span>
        </nav>
        <br>
        <div class = 'row' style="margin-bottom: 20px;">
    <?php
    foreach($image_payload as $index => $image){
        if ($index % 2 == 0 && $index != 0) { //new row entry
    ?>
    </div>
    <div class='row' style="margin-bottom: 20px;">
        <div class='col-lg-6'>
            <div class='card text-center' style="background-color: rgb(241,241,241)">
                <div class='card-content' style="margin-top:10px;">
                    <img src="<?php echo $image['photo_binary']; ?>" style="max-width: 400px; max-height: 400px;">
                </div>
                <div
                    class='card-body'
                    data-task-uuid='<?php echo $image['task_uuid']; ?>'
                    data-user-uuid='<?php echo $image['user_uuid']; ?>'
                >
                    <textarea placeholder="Please provide a description..." class="form-control" rows="3"></textarea>

                </div>
                <div class="card-body">
                    <button class="btn btn-success agree">Positive</button>
                    <button class="btn btn-danger disagree">Negative</button>
                </div>
            </div>
        </div>
        <?php
        } else { //not a new row
            ?>
            <div class='col-lg-6'>
                <div class='card text-center' style="background-color: rgb(241,241,241)">
                    <div class='card-content' style="margin-top:10px;">
                        <img src="<?php echo $image['photo_binary']; ?>" style="max-width: 400px; max-height: 400px;">
                    </div>
                    <div
                        class='card-body'
                        data-task-uuid='<?php echo $image['task_uuid']; ?>'
                        data-user-uuid='<?php echo $image['user_uuid']; ?>'
                    >
                        <textarea placeholder="Please provide a description..." class="form-control" rows="3"></textarea>
                    </div>
                    <div class = 'card-body'>
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
