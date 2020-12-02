<?php

namespace Stanford\LampStudyPortal;

/**
 * @var \Stanford\LampStudyPortal\LampStudyPortal $module
 */
$startTS = microtime(true);
$type = $module->getProjectSetting("workflow");
if ($type == "lazy_import") {
    $module->initialize();
    $module->emLog("Workflow: " . $type . " Duration of run: " . (microtime(true) - $startTS));
}

