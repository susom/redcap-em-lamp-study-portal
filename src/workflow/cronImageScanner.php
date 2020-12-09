<?php

namespace Stanford\LampStudyPortal;

/**
 * @var \Stanford\LampStudyPortal\LampStudyPortal $module
 */
$startTS = microtime(true);
$type = $module->getProjectSetting("workflow");
if($type == "image_adjudication"){
    $module->initialize();
    $module->emLog("Workflow: " . $type . " Duration of run: " . (microtime(true) - $startTS));

}

?>

<nav class="navbar navbar-light bg-light">
    <span class="navbar-text">
        Image scan finished, please check record status dashboard for results
        <br>
        <strong>Duration of run: <?php echo (microtime(true) - $startTS) ?></strong>
    </span>
</nav>
