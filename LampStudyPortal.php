<?php
namespace Stanford\LampStudyPortal;

require_once "emLoggerTrait.php";

/**
 * Class LampStudyPortal
 * @package Stanford\LampStudyPortal
 * @param
 */
class LampStudyPortal extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}

	public function redcap_module_system_enable( $version ) {

	}


	public function redcap_module_project_enable( $version, $project_id ) {

	}


	public function redcap_module_save_configuration( $project_id ) {

	}


}
