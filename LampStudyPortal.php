<?php
namespace Stanford\LampStudyPortal;


require_once "emLoggerTrait.php";
require_once "src/Client.php";
require_once "src/Patient.php";
require_once "src/Task.php";
require_once "src/Media.php";
require_once "src/workflow/ImageAdjudication.php";
require_once "src/workflow/DataImport.php";


define("BASE_PATTERN_HEALTH_API_URL", "https://api.patternhealth.io/");
define("FULL_PATTERN_HEALTH_API_URL", "https://api.patternhealth.io/api/");

/**
 * Class LampStudyPortal
 * @package Stanford\LampStudyPortal
 * @property array $patients;
 */
class LampStudyPortal extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    /** @var array $patients */
    private $patients;

    /** @var Client $client */
    private $client;

    /** @var ImageAdjudication $workflow */
    private $workflow;

    public function __construct()
    {
        global $Proj;


        try {
            parent::__construct();

            if (isset($_GET['pid']) && $this->getProjectSetting('study-group') && $this->getProjectSetting('authentication-email') && $this->getProjectSetting('authentication-password')) {
                $this->setClient(new Client($this, $this->getProjectSetting('study-group'), $this->getProjectSetting('authentication-email'), $this->getProjectSetting('authentication-password'), $this->getProjectSetting('current-token'), $this->getProjectSetting('token-expiration')));
                $this->getClient()->checkToken();

                if ($this->getProjectSetting("workflow") == "image_adjudication") {
                    $this->setWorkflow(new ImageAdjudication($this->getClient()));
                } else { //Data import
                    $RepeatingFormsEvents = $Proj->hasRepeatingFormsEvents();

                    if($RepeatingFormsEvents){ //Necessary for patient saving on numerous tasks
                        $this->setWorkflow(new DataImport($this->getClient()));
                    }

                }
            }
            // Other code to run when object is instantiated
        } catch (\Exception $e) {
            \REDCap::logEvent("ERROR/EXCEPTION occurred " . $e->getMessage(), '', null, null);
            $this->emError($e->getMessage());
            echo $e->getMessage();
        }
    }

    public function updateTokenInfo()
    {
        //work around if token is updated make sure to save it.
        if ($this->getProjectSetting('current-token') != $this->getClient()->getToken()) {
            $this->setProjectSetting('current-token', $this->getClient()->getToken());
            $this->setProjectSetting('token-expiration', $this->getClient()->getExpiration());
        }
    }

    /**
     * @param Patient $patient patient object
     * @param array $attributes associative mapping table between pattern keys and redcap keys
     */


    /**
     * @return array
     */
    public function getPatients()
    {
        if (!$this->patients) {
            $this->setPatients();
        }
        return $this->patients;
    }

    /**
     * @param array $patients
     */
    public function setPatients($patients = array())
    {
        if (empty($patients)) {
            $this->patients = $this->getClient()->request('get', FULL_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members');
        } else {
            $this->patients = $patients;
        }

    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return ImageAdjudication
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param ImageAdjudication $workflow
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
    }


}
