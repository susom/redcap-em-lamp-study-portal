<?php
namespace Stanford\LampStudyPortal;


use ExternalModules\AbstractExternalModule;

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
                $this->setClient(new Client($this->getProjectSetting('study-group'), $this->getProjectSetting('authentication-email'), $this->getProjectSetting('authentication-password'), $this->getProjectSetting('current-token'), $this->getProjectSetting('token-expiration')));

                //work around if token is updated make sure to save it.
                if ($this->getProjectSetting('current-token') != $this->getClient()->getToken()) {
                    $this->setProjectSetting('current-token', $this->getClient()->getToken());
                    $this->setProjectSetting('token-expiration', $this->getClient()->getExpiration());
                }

                $this->getAllPatients();

            }
            // Other code to run when object is instantiated
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAllPatients()
    {
        $patients = $this->getClient()->request('get', BASE_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members');

        $this->setPatients($patients);
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
