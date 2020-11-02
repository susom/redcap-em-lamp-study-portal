<?php


namespace Stanford\LampStudyPortal;

use PHPUnit\Exception;

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


    /**
     * @throws \Exception
     */
    private function processPatients()
    {
        // Fetch all patients sorted by provider task count
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) { //iterate in order, break when no toDoProviderTasks
//                if ($patient['toDoProviderTaskCount'] == 0) { //commented for testing.
//                    break;
//                }
                if($patient['user']['uuid'] == 'u-S5kGbk5lTYWNpKunztGe0g') //for testing, this user has a picture
                    echo 'yes';
                $patients[$index]['object'] = new Patient($this->getClient(), $patient); //Create new patient object
                $journal_entry_photos = $patients[$index]['object']->getJournalEntryPhotos();
                if (!empty($journal_entry_photos)) { //Users has pictures to upload
                    //TODO determine whether or not to save records if no image found.
                    foreach($journal_entry_photos as $index => $photo) {
                        if (!$this->checkRecordExist($photo['uuid'])) { //Not in our database, save record
                            $data['task_uuid'] = $photo['uuid']; //This is our record ID
                            $data['patient_uuid'] = $patient['user']['uuid'];
                            $data['activity_uuid'] = $photo['activityUuid'];
                            $data['created'] = $photo['created']; //keep track of photo upload time
                            $data['status'] = $photo['status'];
                            #$data['base64_image'] = $tasks[$tIndex]['media']['object']->getBinary();
                            $data['redcap_event_name'] = $this->getClient()->getEm()->getFirstEventId();
                            $data['full_json'] = json_encode($photo);
                            $data['confidence'] = $photo['confidence'];

                            $response = \REDCap::saveData($this->getClient()->getEm()->getProjectId(), 'json', json_encode(array($data)));
                            if (!empty($response['errors'])) {
                                if (is_array($response['errors'])) {
                                    throw new \Exception(implode(",", $response['errors']));
                                } else {
                                    throw new \Exception($response['errors']);
                                }
                            } else {
                                try { //upload photo here
                                    if(isset($photo['media'])) {
                                        $photo['media']->uploadImage(end($response['ids']), 'image_file', $this->getClient()->getEm()->getFirstEventId(), $this->getClient()->getEm()->getProjectSetting('api-token'));
                                        $this->getClient()->getEm()->emLog("Patient :" . $patient['user']['uuid'] . " was imported successfully");
                                    } else {
                                        throw new \Exception("<br>Error uploading image, no media" . "<br>Photo Info:<pre>" . print_r($photo, true) . "</pre>");
                                    }
                                } catch (\Exception $e) {
//                                    \REDCap::logEvent("ERROR/EXCEPTION occurred " . $e->getMessage(), '', null, null);
                                    $this->getClient()->getEm()->emError($e->getMessage());
                                    echo $e->getMessage();
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $this->getClient()->getEm()->emError('No patients currently exist for current GroupID ', $this->getClient()->getGroup());
        }
        //update patient object
        $this->setPatients($patients);
    }

    public function checkRecordExist($record_id)
    {
        $param = array(
            'return_format' => 'json',
            'records' => $record_id
        );

        $data = json_decode(\REDCap::getData($param));
        if(!empty($data))
            return true;
        return false;
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
            $this->patients = $this->getClient()->request(
                'get',
                FULL_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members?includePlans=false&adherenceDays=0&sortBy=TODO&sortDirection=DESC&offset=0');
        } else {
            $this->patients = $patients;
        }

    }
}
