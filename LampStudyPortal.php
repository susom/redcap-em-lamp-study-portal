<?php
namespace Stanford\LampStudyPortal;


use ExternalModules\Framework;
use PHPUnit\Exception;
use Twig\Error\Error;

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

    public $processPatients = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function initialize()
    {
        try {
            if (isset($_GET['pid'])
                && $this->getProjectSetting('study-group')
                && $this->getProjectSetting('authentication-email')
                && $this->getProjectSetting('authentication-password'))
            {
                $this->setClient(
                    new Client(
                        $this,
                        $this->getProjectSetting('study-group'),
                        $this->getProjectSetting('authentication-email'),
                        $this->getProjectSetting('authentication-password'),
                        $this->getProjectSetting('current-token'),
                        $this->getProjectSetting('token-expiration')
                    ));

                $this->getClient()->checkToken();
                if ($this->getProjectSetting("workflow") == "image_adjudication") {
                    $this->setWorkflow(new ImageAdjudication($this->getClient(), $this->processPatients));
                } elseif($this->getProjectSetting("workflow") == "lazy_import") { //Data import
                    $this->setWorkflow(new DataImport($this->getClient()));
                }
            }
        // Other code to run when object is instantiated
        } catch (\Exception $e) {
            \REDCap::logEvent("ERROR/EXCEPTION occurred " . $e->getMessage(), '', null, null);
            $this->emError($e->getMessage());
            echo $e->getMessage();
        }
    }

    /**
     * @param $pid
     * @param $link
     * @return $link sidebar link
     */
    public function redcap_module_link_check_display($pid, $link){
        if($this->getProjectSetting("workflow") == "image_adjudication" && $link["name"] == "Image adjudication client")
            return $link;

        if($this->getProjectSetting("workflow") == "image_adjudication" && $link["name"] == "Trigger image scan")
            return $link;

        if($this->getProjectSetting("workflow") == "lazy_import" && $link['name'] == "Trigger data scan")
            return $link;
    }

    /**
     * @param $cron
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cronImageScanner($cron)
    {
        $projects = $this->framework->getProjectsWithModuleEnabled();
        $url = $this->getUrl('src/workflow/cronImageScanner.php', true); //has to be page
        foreach($projects as $index => $project_id){
            $thisUrl = $url . "&pid=$project_id"; //project specific
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
//            $this->emLog($response->getBody());
        }
    }

    /**
     * @param $cron
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cronDataImport($cron)
    {
        $projects = $this->framework->getProjectsWithModuleEnabled();
        $url = $this->getUrl('src/workflow/dataImportScanner.php', true); //has to be page
        foreach($projects as $index => $project_id){
            $thisUrl = $url . "&pid=$project_id"; //project specific
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
//            $this->emLog($response->getBody());
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
     * @return array
     */
    public function fetchImages()
    {
        global $Proj;
            try {
                //Pull only non completed images
                $records = json_decode(\REDCap::getData($Proj->project_id,'json',null,null,null,null,false,false,false,'[status] != "completed"'));
                $payload = array();
                foreach($records as $index => $record) {
                    $doc_id = $record->image_file;
                    $pic_info = array(
                        'task_uuid' => $record->task_uuid,
                        'user_uuid' => $record->patient_uuid,
                        'photo_binary' => $this->getDocumentName($doc_id)
                    );
                    array_push($payload, $pic_info);
                }
                return $payload;

            } catch (\Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
                $this->emError($e->getMessage());
            }
    }

    /**
     * @param $doc_id
     * @return string
     */
    public function getDocumentName($doc_id) {
        $sql = "select * from redcap_edocs_metadata where doc_id = '$doc_id'";
        $q = db_query($sql);
        if (db_num_rows($q) == 1) {
            while ($row = db_fetch_assoc($q)) {
                return $this->generateDataURI(file_get_contents("/var/www/html/edocs/" . $row['stored_name']));
            }
        }
    }

    /**
     * @param $file_binary
     * @param string $mime
     * @return string
     */
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
            $this->patients = $this->getClient()->request(
                'get',
                FULL_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members');
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
