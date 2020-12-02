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

