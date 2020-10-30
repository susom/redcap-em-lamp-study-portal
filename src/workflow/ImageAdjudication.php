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
        // Fetch all patients sorted by provider task count
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) { //iterate in order, break when no toDoProviderTasks
//                if ($patient['toDoProviderTaskCount'] == 0) {
//                    break;
//                }
                if($patient['user']['uuid'] == 'u-S5kGbk5lTYWNpKunztGe0g') //for testing, this user has a picture
                    echo 'yes';
                $patients[$index]['object'] = new Patient($this->getClient(), $patient); //Create new patient object
                $journal_entry_photos = $patients[$index]['object']->getJournalEntryPhotos();
                if (!empty($journal_entry_photos)) { //Users has pictures to upload
                    //Check here to see if record already exists in our db.
                    foreach($journal_entry_photos as $index => $photo) {
                        $check = \REDCap::getData('json',$photo['uuid']);
                        if (!empty($check)) { //Not in our database, save record
                            //TODO HOW TO CHECK IF EMPTY ?
                            $data['task_uuid'] = $photo['uuid']; //Change this to task ID ?
                            $data['patient_uuid'] = $patient['user']['uuid'];

                            $data['activity_uuid'] = $photo['activityUuid'];
                            $data['created'] = $photo['created']; //keep track of photo upload time
                            $data['status'] = $photo['status'];
                            #$data['base64_image'] = $tasks[$tIndex]['media']['object']->getBinary();
                            $data['redcap_event_name'] = $this->getClient()->getEm()->getFirstEventId();
                            $data['full_json'] = json_encode($photo);
//                            $data['confidence'] = $patients[$index]['object']->getConfidence();

                            $response = \REDCap::saveData($this->getClient()->getEm()->getProjectId(), 'json', json_encode(array($data)));
                            if (!empty($response['errors'])) {
                                if (is_array($response['errors'])) {
                                    throw new \Exception(implode(",", $response['errors']));
                                } else {
                                    throw new \Exception($response['errors']);
                                }
                            } else {
                                $photo['media']->uploadImage(end($response['ids']), 'image_file', $this->getClient()->getEm()->getFirstEventId(), $this->getClient()->getEm()->getProjectSetting('api-token'));
                                $this->getClient()->getEm()->emLog("Patient :" . $patient['user']['uuid'] . " was imported successfully");
                            }

                        }
                    }

                }

                // now loop over retrieved tasks to see if images exists
//                if ($tasks = $patients[$index]['object']->getTasks()) {
//                    foreach ($tasks as $tIndex => $task) {
//                        if ($tasks[$tIndex]['media']) { //There is a photo
////                            $data['record_id'] = $patient['user']['uuid']; //Change this to task ID ?
//                            $data['patient_uuid'] = $patient['user']['uuid'];
//                            $data['task_uuid'] = $task['uuid'];
//                            $data['activity_uuid'] = $task['activityUuid'];
//                            $data['created'] = $task['created']; //keep track of photo upload time
//                            $data['status'] = $task['status'];
//                            #$data['base64_image'] = $tasks[$tIndex]['media']['object']->getBinary();
//                            $data['redcap_event_name'] = $this->getClient()->getEm()->getFirstEventId();
//                            $data['full_json'] = json_encode($task);
//                            $data['confidence'] = $patients[$index]['object']->getConfidence();
//
//                            $response = \REDCap::saveData($this->getClient()->getEm()->getProjectId(), 'json', json_encode(array($data)));
//                            if (!empty($response['errors'])) {
//                                if (is_array($response['errors'])) {
//                                    throw new \Exception(implode(",", $response['errors']));
//                                } else {
//                                    throw new \Exception($response['errors']);
//                                }
//                            } else {
//
//                                $tasks[$tIndex]['media']['object']->uploadImage(end($response['ids']), 'image_file', $this->getClient()->getEm()->getFirstEventId(), $this->getClient()->getEm()->getProjectSetting('api-token'));
//                                $this->getClient()->getEm()->emLog("Patient :" . $patient['user']['uuid'] . " was imported successfully");
//                            }
//                        }
//                    }
//                }
//                $x = $patients['index']['object'];
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
