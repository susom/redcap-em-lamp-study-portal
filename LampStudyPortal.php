<?php
namespace Stanford\LampStudyPortal;


require_once "emLoggerTrait.php";
require_once "src/Client.php";
require_once "src/Patient.php";
require_once "src/Task.php";
require_once "src/Media.php";
require_once "src/workflow/ImageJudication.php";


define("BASE_PATTERN_HEALTH_API_URL", "https://api.patternhealth.io/api/");

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

    /** @var ImageJudication $workflow */
    private $workflow;

    public function __construct()
    {
        try {
            parent::__construct();
            global $Proj;

            if (isset($_GET['pid']) && $this->getProjectSetting('study-group') && $this->getProjectSetting('authentication-email') && $this->getProjectSetting('authentication-password')) {
                $this->setClient(new Client($this, $this->getProjectSetting('study-group'), $this->getProjectSetting('authentication-email'), $this->getProjectSetting('authentication-password'), $this->getProjectSetting('current-token'), $this->getProjectSetting('token-expiration')));
                $this->getClient()->checkToken();
                $this->processPatients();

                if ($this->getProjectSetting("workflow") == "image_judication") {
                    $this->setWorkflow(new ImageJudication($this->getPatients(), $this->getClient()));
                } else {
                    //TODO Jordan please define your class here and set it as workflow
                }

            }
            // Other code to run when object is instantiated
        } catch (\Exception $e) {
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

    private function processPatients()
    {
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) {
                $patientObj = new Patient($this->getClient(), $patient);
                $this->createPatientRecord($patientObj, $patientObj->getConstants());
                break; //for testing only
            }
        } else {
            $this->emError('No patients currently exist for current GroupID ', $this->getClient()->getGroup());
        }
        //update patient object
        $this->setPatients($patients);
    }


    /**
     * @param Patient $patient patient object
     * @param array $attributes associative mapping table between pattern keys and redcap keys
     */
    public function createPatientRecord(Patient $patient, array $attributes)
    {
        if (isset($patient) && !empty($attributes)) {
            $patient_json = $patient->getPatientJson();
            $data = array("record_id" => $patient_json['user']['uuid']);

            //Update all patient variables in redcap instrument
            foreach($attributes as $pattern_key => $redcap_variable_name){
                if(isset($patient_json['user'][$pattern_key])){

                    $data[$redcap_variable_name] = is_string($patient_json['user'][$pattern_key]) ? $patient_json['user'][$pattern_key] : (string)(int)$patient_json['user'][$pattern_key] ;
                }
            }

            $result =  \REDCap::saveData('json', json_encode(array($data)));
            if (!empty($result['errors'])) $this->emError("Errors saving result: ", '', '', $result);

        } else {
            $this->emError('No patient currently passed', '', $patient, $attributes);
        }
    }

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
            $this->patients = $this->getClient()->request('get', BASE_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members');
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
     * @return ImageJudication
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param ImageJudication $workflow
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
    }


}
