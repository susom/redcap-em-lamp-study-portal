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
                $this->setClient(new Client($this, $this->getProjectSetting('study-group'), $this->getProjectSetting('authentication-email'), $this->getProjectSetting('authentication-password'), $this->getProjectSetting('current-token'), $this->getProjectSetting('token-expiration')));


                $this->processPatients();

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
                $patients['results'][$index]['object'] = new Patient($this->getClient(), $patient['uuid']);
            }
            $this->setPatients($patients);
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
}
