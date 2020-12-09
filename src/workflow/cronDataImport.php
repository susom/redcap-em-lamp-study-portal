<?php
namespace Stanford\LampStudyPortal;
/** @var LampStudyPortal $module */


$startTS = microtime(true);
$type = $module->getProjectSetting("workflow");
$module->emDebug("[" . $module->getProjectId() . "] Type: " . $type);

if ($type == "lazy_import") {
    $module->initialize();
    $module->emLog("[" . $module->getProjectId() . "] Workflow: " . $type . " Duration of run: " . (microtime(true) - $startTS));
}

?>

<nav class="navbar navbar-light bg-light">
    <span class="navbar-text">
        Manual data scan finished, please check record status dashboard for results
        <br>
        <strong>Duration of run: <?php echo (microtime(true) - $startTS) ?></strong>
    </span>
</nav>

