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
     * @param $patients
     * @param Client $client
     */
    public function __construct($patients, $client)
    {

        $this->setPatients($patients);
        $this->setClient($client);

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
        return $this->patients;
    }

    /**
     * @param array $patients
     */
    public function setPatients(array $patients)
    {
        $this->patients = $patients;
    }


}
