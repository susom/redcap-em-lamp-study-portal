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
//                    $this->setWorkflow(new ImageAdjudication($this->getClient()));
                } elseif($this->getProjectSetting("workflow") == "lazy_import") { //Data import
                    $RepeatingFormsEvents = $Proj->hasRepeatingFormsEvents();
                    //TODO finish data pulling
//                    if($RepeatingFormsEvents){ //Necessary for patient saving on numerous tasks
//                        $this->setWorkflow(new DataImport($this->getClient()));
//                    }
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

    public function fetchImages()
    {
        global $Proj;
        $api_token = $this->getProjectSetting("api-token");
        $records = json_decode(\REDCap::getData($Proj->project_id,'json'));
        if (!empty($api_token)) {
            $payload = array();
            foreach($records as $index => $record){
                if (!(int)$record->patient_complete) {
                    //construct value return
                    $pic_info = array(
                        'photo_binary' => $this->generateDataURI($this->callFileApi($api_token,$record->record_id), 'image/png'),
                        'task_uuid' => $record->task_uuid,
                        'confidence' => '100'
                    );
                    //set key to task UUID
                    array_push($payload, $pic_info);
                }
            }
            return $payload;
        }
    }

    public function callFileApi($api_token, $record_id, $field_name='image_file')
    {
        $data = array(
            'token' => $api_token,
            'content' => 'file',
            'action' => 'export',
            'record' => $record_id,
            'field' => $field_name,
            'event' => '',
            'returnFormat' => 'json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function generateDataURI($file_binary, $mime='image/png')
    {
        $base64 = base64_encode($file_binary);
        return ('data:' . $mime . ';base64,' . $base64);
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
