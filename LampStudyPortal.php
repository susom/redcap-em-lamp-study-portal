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
require_once "src/workflow/AlternateAdjudication.php";

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


    /**
     * Load project-specific settings
     */
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

                // This module supports multiple 'use cases' - based on the workflow
                $this->workflow = $this->getProjectSetting("workflow");
                switch ($this->workflow) {
                    case "image_adjudication":
                        // Review provider tasks and present UI for image adjudication
                        $this->setWorkflow(new ImageAdjudication($this->getClient(), $this->processPatients));
                        break;
                    case "lazy_import":
                        // Sync pattern data with REDCAp
                        $this->setWorkflow(new DataImport($this->getClient()));
                        break;
                    default:
                        $this->emError("Invalid workflow: $this->workflow");
                }

            }
        // Other code to run when object is instantiated
        } catch (\Exception $e) {
            \REDCap::logEvent("ERROR/EXCEPTION occurred " . $e->getMessage(), '', null, null);
            $this->emError($e->getMessage());
            echo $e->getMessage();
        }
    }

    //For one off alternate adjudication only: initializes alternate adjudication workflow.
    public function alternateInitialize()
    {
        $this->setClient(
            new Client(
                $this,
                $this->getProjectSetting('study-group'),
                $this->getProjectSetting('authentication-email'),
                $this->getProjectSetting('authentication-password'),
                $this->getProjectSetting('current-token'),
                $this->getProjectSetting('token-expiration'))
        );
        $this->setWorkflow(new AlternateAdjudication($this->getClient()));
    }

    // Refresh user survey responses re
    public function refreshUserSurveyResponse()
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

                // This module supports multiple 'use cases' - based on the workflow
                $this->workflow = $this->getProjectSetting("workflow");
                $this->setWorkflow(new DataImport($this->getClient(),true));

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
        $workflow = $this->getProjectSetting("workflow");

        if($workflow == "image_adjudication" && $link["name"] == "Image adjudication client")
            return $link;

        if($workflow == "image_adjudication" && $link["name"] == "Trigger image scan")
            return $link;

        if($workflow == "image_adjudication_alternate" && $link["name"] == "Image adjudication alternate")
            return $link;

//        if($workflow == "lazy_import" && $link["name"] == "Trigger data refresh")
//            return $link;
//
//        if($workflow == "lazy_import" && $link['name'] == "Trigger data scan")
//            return $link;
    }


    /**
     * @param $cron
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cronImageScanner($cron)
    {
        $this->emDebug("Starting " . __METHOD__);
        $url = $this->getUrl('src/workflow/cronImageScanner.php', true); //has to be page

        $projects = $this->framework->getProjectsWithModuleEnabled();
        $this->emLog($projects);
        foreach($projects as $index => $project_id){
            $thisUrl = $url . "&pid=$project_id"; //project specific
            $client = new \GuzzleHttp\Client();
            try{
                $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
            } catch (\Exception $e) {
                $this->emError($project_id, $e->getMessage(), debug_backtrace(0));
            }
        }
    }


    /**
     * @param $cron
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cronDataImport($cron)
    {
        $this->emDebug("Starting " . __METHOD__);
        $url = $this->getUrl('src/workflow/cronDataImport.php', true); //has to be page

        $projects = $this->framework->getProjectsWithModuleEnabled();
        $this->emLog($projects);
        foreach($projects as $index => $project_id){
            $thisUrl = $url . "&pid=$project_id"; //project specific
            $client = new \GuzzleHttp\Client();
            try {
                $client->request('GET', $thisUrl, array(\GuzzleHttp\RequestOptions::SYNCHRONOUS => true));
            } catch (\Exception $e) {
                $this->emError($project_id, $e->getMessage(), debug_backtrace(0));
            }

        }
    }


    /**
     * Maintain fresh token for pattern health api
     */
    public function updateTokenInfo()
    {
        //work around if token is updated make sure to save it.
        if ($this->getProjectSetting('current-token') != $this->getClient()->getToken()) {
            $this->setProjectSetting('current-token', $this->getClient()->getToken());
            $this->setProjectSetting('token-expiration', $this->getClient()->getExpiration());
        }
    }


    /**
     * Get all images downloaded to REDCap but that have not been adjudicated by the REDCap UI
     * @return array
     */
    public function fetchImages()
    {
        try {
            //Pull only non completed images
            $records = json_decode(\REDCap::getData('json',null,null,null,null,false,false,false,'[status] != "completed"'), true);
            $payload = array();
            foreach($records as $index => $record) {
                $doc_id = $record["image_file"];
                $doc_temp_path = \Files::copyEdocToTemp($doc_id, false, true);
                $binary = null;

                if($doc_temp_path) {
                    $binary = file_get_contents($doc_temp_path);
                    unlink($doc_temp_path);
                    $pic_info = array(
                        'task_uuid' => $record["task_uuid"],
                        'user_uuid' => $record["patient_uuid"],
                        'photo_binary' => empty($binary) ? null : $this->generateDataURI($binary)
                    );

                    $this->emLog("Pushing to frontend: task_uuid: " .
                        $record->task_uuid . ' user_uuid: ' . $record->patient_uuid,
                        'photoBinary: ' . strlen($pic_info['photo_binary'] ) .' len characters'
                    );

                    array_push($payload, $pic_info);
                }

            }

            return $payload;

        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->emError($e->getMessage());
        }
    }


    // /**
    //  * @param $doc_id
    //  * @return string
    //  */
    // public function getDocumentName($doc_id) {
    //     $sql = "select * from redcap_edocs_metadata where doc_id = '$doc_id'";
    //     $q = db_query($sql);
    //     if (db_num_rows($q) == 1) {
    //         while ($row = db_fetch_assoc($q)) {
    //             $binary = file_get_contents("/var/www/html/edocs/" . $row['stored_name']);
    //             if(!empty($binary)) {
    //                 return $this->generateDataURI($binary);
    //             } else {
    //                 $this->emError('Binary image data not found for doc id: ' . $doc_id . ' Full edocs path : /var/www/html/edocs/' . $row['stored_name']);
    //                 return '';
    //             }
    //         }
    //     }
    // }

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
