<?php
namespace Stanford\LampStudyPortal;


require_once "emLoggerTrait.php";
require_once "src/Client.php";
require_once "src/Patient.php";
require_once "src/Task.php";
require_once "src/Media.php";


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

    public function __construct()
    {
        try {
            parent::__construct();

            if (isset($_GET['pid'])) {
                $this->setClient(new Client($this->getProjectSetting('authentication-email'), $this->getProjectSetting('authentication-password')));
            }
            // Other code to run when object is instantiated
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @return array
     */
    public function getPatients()
    {
        return $this->patients;
    }

    /**
     * @param array $patients
     */
    public function setPatients($patients)
    {
        $this->patients = $patients;
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
}
