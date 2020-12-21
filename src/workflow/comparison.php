<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

/**
 * @var \Stanford\LampStudyPortal\LampStudyPortal $module
 */
//$module->initialize();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module->processPatients = false; //necessary to skip unnecessary patient pulling on PUT
    $module->initialize();
    $module->getWorkflow()->updateTask(
        filter_var($_POST['user_uuid'], FILTER_SANITIZE_STRING),
        filter_var($_POST['task_uuid'], FILTER_SANITIZE_STRING),
        filter_var($_POST['results'], FILTER_SANITIZE_STRING),
        filter_var($_POST['confidence'], FILTER_SANITIZE_STRING),
        filter_var($_POST['readable'], FILTER_SANITIZE_STRING),
        filter_var($_POST['comments'], FILTER_SANITIZE_STRING),
        filter_var($_POST['fallen'], FILTER_SANITIZE_STRING),
        filter_var($_POST['passfail'], FILTER_SANITIZE_STRING),
        filter_var($_POST['reagents'], FILTER_SANITIZE_STRING),
        filter_var($_POST['shattered'], FILTER_SANITIZE_STRING),
        filter_var($_POST['volume'], FILTER_SANITIZE_STRING),
        filter_var($_POST['wick'], FILTER_SANITIZE_STRING)
    );
}


$image_payload = $module->fetchImages();

if(!empty($image_payload)){
?>

<nav class="navbar navbar-light bg-light">
    <span class="navbar-text">
        Total images to be adjudicated:
            <strong id="adjudicationCount"><?php echo sizeof($image_payload) ?></strong>
    </span>
</nav>
<br>
<div>
    <ul class="list-unstyled">
        <li>For the following Images:</li>
        <ul>
            <li>Note where the result zone and the capillary is located (blue/break line on the capillary).</li>
            <li>Note the small droplet of liquid in the capillary, which is ok.</li>
        </ul>
    </ul>
    <button type="button" class="btn btn-sm btn-info example" data-toggle="popover" data-image="<?php echo $module->getUrl('src/workflow/example.jpeg');?>">
        Show example diagram
    </button>
</div>
<br>
<div class='row' style="margin-bottom: 20px;">
    <?php
    foreach ($image_payload as $index => $image){
//        if ($index % 2 == 0 && $index != 0) { //new row entry
    ?>
</div>

<div class='row' style="margin-bottom: 20px;">
    <div class='col-lg-12'>
        <div class='card' style="background-color: rgb(241,241,241)">
            <div class = 'card-header'>
                <h6>
                    <span class="badge badge-light">Task/Record id : <?php echo $image['task_uuid']; ?></span>
                    <span class="float-right badge badge-light">Patient id : <?php echo $image['user_uuid']; ?></span>
                </h6>
            </div>

            <div class='card-content text-center' style="margin-top:10px;">
                <img class = "img" src="<?php echo $image['photo_binary']; ?>" style="max-width: 400px; max-height: 400px;">
            </div>
            <div
                class='card-body'
                data-task-uuid='<?php echo $image['task_uuid']; ?>'
                data-user-uuid='<?php echo $image['user_uuid']; ?>'
            >
                <hr>
                <form style="padding: 2px;">
                    <div class="form-row">
                        <div class="form-group readable-box col-md-3">
                            <label>
                                <strong>Results readable</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Is this picture readable?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="readable" type="radio" value="true">
                                <label class="form-check-label" for="defaultCheck1">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="readable" type="radio" value="false" >
                                <label class="form-check-label" for="defaultCheck2">No</label>
                            </div>
                        </div>
                        <div class="form-group result-box col-md-3">
                            <label>
                                <strong>Image Result</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Look at the zone above the black rubber grommet/cap
                                    (results zone). Which of the following colors does the result most closely resemble?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="results"  value="1">
                                <label class="form-check-label">Not detected</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="results"  value="2">
                                <label class="form-check-label">Detected</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="results" value="3">
                                <label class="form-check-label">Unclear</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="results" value="4">
                                <label class="form-check-label">No liquid visible</label>
                            </div>
                        </div>
                        <div class="form-group shattered-box col-md-3">
                            <label>
                                <strong>Shattered</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Is the capillary shattered in more than one location?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="shattered" type="radio" value="true">
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="shattered" type="radio" value="false" >
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                        <div class="form-group fallen-box col-md-3">
                            <label>
                                <strong>Fallen</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Have any capillary parts fallen into the result zone?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="fallen" type="radio" value="true">
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="fallen" type="radio" value="false" >
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group reagents-box col-md-3">
                            <label>
                                <strong>Reagents</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Have the majority of the reagents dropped into the result zone (beyond one small droplet)?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="reagents" type="radio" value="true">
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="reagents" type="radio" value="false" >
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                        <div class="form-group wick-box col-md-3">
                            <label>
                                <strong>Wick</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Is there a visible “wick” (look for the reflective piece)?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="wick" type="radio" value="true">
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="wick" type="radio" value="false" >
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                        <div class="form-group passfail-box col-md-3">
                            <label>
                                <strong>Pass or Fail</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Would this be considered a passed or failed test
                                    (something did not appear to work correctly on the SnapDX test)?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="passfail" type="radio" value=1>
                                <label class="form-check-label">Pass</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="passfail" type="radio" value=0 >
                                <label class="form-check-label">Fail</label>
                            </div>
                        </div>
                        <div class="form-group volume-box col-md-3">
                            <label>
                                <strong>Volume</strong>
                                <i class="far fa-question-circle" data-toggle="tooltip" title="Look at the zone above the black rubber
                                    grommet/cap (result zone).Is there enough (~3mm) colored liquid at the result zone?"></i>
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" name="volume" type="radio" value="true">
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="volume" type="radio" value="false" >
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="range-<?php echo $index; ?>" ><strong>Confidence</strong></label>
                        <div class="d-flex justify-content-center ">
                            <form class="range-field w-75">
                                <input type="range" class="form-control-range" id="range-<?php echo $index; ?>">
                            </form>
                            <span class="font-weight-bold text-primary mt-1 ml-2 confidenceCount"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Comments</strong></label>
                        <textarea class="form-control" class="comments" rows="3"></textarea>
                    </div>
                </form>

<!--                <form>-->
<!--                    <div class="form-group readable-box">-->
<!--                        <label>Results readable</label>-->
<!--                        <div class="form-check">-->
<!--                            <input class="form-check-input" name="readable" type="radio" value="true">-->
<!--                            <label class="form-check-label" for="defaultCheck1">Yes</label>-->
<!--                        </div>-->
<!--                        <div class="form-check">-->
<!--                            <input class="form-check-input" name="readable" type="radio" value="false" >-->
<!--                            <label class="form-check-label" for="defaultCheck2">No</label>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr>-->
<!--                    <label>Image Result</label>-->
<!--                    <div class="form-group result-box">-->
<!--                        <div class="form-check">-->
<!--                            <input class="form-check-input" type="radio" name="results"  value="1">-->
<!--                            <label class="form-check-label">Not detected</label>-->
<!--                        </div>-->
<!--                        <div class="form-check">-->
<!--                            <input class="form-check-input" type="radio" name="results"  value="2">-->
<!--                            <label class="form-check-label">Detected</label>-->
<!--                        </div>-->
<!--                        <div class="form-check">-->
<!--                            <input class="form-check-input" type="radio" name="results" value="3">-->
<!--                            <label class="form-check-label">Unclear</label>-->
<!--                        </div>-->
<!--                        <div class="form-check">-->
<!--                            <input class="form-check-input" type="radio" name="results" value="4">-->
<!--                            <label class="form-check-label">No liquid visible</label>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr>-->
<!--                    <div class="form-group">-->
<!--                        <label for="range---><?php //echo $index; ?><!--" >Confidence</label>-->
<!--                        <div class="d-flex justify-content-center ">-->
<!--                            <form class="range-field w-75">-->
<!--                                <input type="range" class="form-control-range" id="range---><?php //echo $index; ?><!--">-->
<!--                            </form>-->
<!--                            <span class="font-weight-bold text-primary mt-1 ml-2 confidenceCount"></span>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </form>-->
                <button class="float-right btn btn-primary submit">Submit</button>
            </div>
        </div>
    </div>

<?php
    }
//    } //foreach
} else {
    ?>
    <div class="alert alert-warning" role="alert" style="margin-right: 20px;">
        No current images found needing adjudication
    </div>
    <?php
}


?>
    <script src="<?php echo $module->getUrl('src/js/jquery.zoom.js'); ?>"></script>
    <script src="<?php echo $module->getUrl('src/js/config.js'); ?>"></script>
    <style type="text/css">
        .popover{
            max-width:800px;
        }
    </style>
