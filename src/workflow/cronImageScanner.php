<?php
namespace Stanford\LampStudyPortal;
/** @var LampStudyPortal $module */


$startTS = microtime(true);
$type = $module->getProjectSetting("workflow");
$module->emDebug("[" . $module->getProjectId() . "] Type: " . $type);

if($type == "image_adjudication"){
    $module->initialize();
    $module->emLog("[" . $module->getProjectId() . "] Workflow: " . $type . " Duration of run: " . (microtime(true) - $startTS));
}

?>

<nav class="navbar navbar-light bg-light">
    <span class="navbar-text">
        Image scan finished, please check record status dashboard for results
        <br>
        Note: This process will only pull data when an open provider task for image adjudicaton is found. If none are available,
        no record updates will be performed.
        <br>
        <strong>Duration of run: <?php echo (microtime(true) - $startTS) ?></strong>
    </span>
</nav>
