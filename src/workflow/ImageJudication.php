<?php


namespace Stanford\LampStudyPortal;

/**
 * Class ImageJudication
 * @package Stanford\LampStudyPortal
 * @property Client $client
 */
class ImageJudication
{
    /** @var Client $client */
    private $client;

    /** @var array $patients */
    private $patients;

    /**
     * @param Client $client
     */
    public function __construct($client)
    {

        $this->setPatients();
        $this->setClient($client);

    }


    private function processPatients()
    {
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) {
                $patientObj = new Patient($this->getClient(), $patient);
                break; //for testing only
            }
        } else {
            $this->getClient()->getEm()->emError('No patients currently exist for current GroupID ', $this->getClient()->getGroup());
        }
        //update patient object
        $this->setPatients($patients);
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
}
