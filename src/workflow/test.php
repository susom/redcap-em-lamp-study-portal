<?php
namespace Stanford\LampStudyPortal;

/**
 * @var \Stanford\LampStudyPortal\LampStudyPortal $module
 */

if($module->getProjectSetting("workflow") == "lazy_import")
    $module->initialize();
else
    echo 'Lazy import option not selected in EM settings';
