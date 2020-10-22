<?php


namespace Stanford\LampStudyPortal;

/**
 * Class ImageJudication
 * @package Stanford\LampStudyPortal
 * @property Client $client
 */
class ImageAdjudication
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
        $this->setClient($client);
        $this->setPatients();
        $this->processPatients();

        // this to tell other classes we need to track data.
        $this->getClient()->setSaveToREDCap(true);
    }


    private function processPatients()
    {
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) {
//                if ($patient['toDoProviderTaskCount'] == 0) {
//                    break;
//                }
                $patients[$index]['object'] = new Patient($this->getClient(), $patient);

                // now loop over retrieved tasks to see if images exists
                if ($tasks = $patients[$index]['object']->getTasks()) {
                    foreach ($tasks as $tIndex => $task) {
                        if ($tasks[$tIndex]['media']) {
                            // here we need to save the image.
                            $data['patient_uuid'] = $patient['user']['uuid'];
                            $data['task_uuid'] = $task['uuid'];
                            $data['activity_uuid'] = $task['uuid'];
                            #$data['base64_image'] = $tasks[$tIndex]['media']['object']->getBinary();
                            $data['redcap_event_name'] = $this->getClient()->getEm()->getFirstEventId();
                            $response = \REDCap::saveData($this->getClient()->getEm()->getProjectId(), 'json', json_encode(array($data)));
                            if (!empty($response['errors'])) {
                                if (is_array($response['errors'])) {
                                    throw new \Exception(implode(",", $response['errors']));
                                } else {
                                    throw new \Exception($response['errors']);
                                }
                            } else {
                                $this->getClient()->getEm()->emLog("Patient :" . $patient['user']['uuid'] . " was imported successfully");
                            }
                        }
                    }
                }
                $x = $patients['index']['object'];
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
            $this->patients = $this->getClient()->request('get', FULL_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members?includePlans=false&adherenceDays=0&sortBy=TODO&sortDirection=DESC&offset=0');
        } else {
            $this->patients = $patients;
        }

    }
}
