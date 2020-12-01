<?php

namespace Stanford\LampStudyPortal;

/**
 * @var \Stanford\LampStudyPortal\LampStudyPortal $module
 */
$startTS = microtime(true);
$type = $module->getProjectSetting("workflow");
if($type == "image_adjudication"){
    $module->initialize();
    $module->emDebug("Workflow: " . $type . " Duration of run: " . microtime(true) - $startTS );
}

