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

    /** @var array $record_cache */
    private $record_cache;

    /** @var array $provider_survey */
    private $provider_survey;

    /**
     * @param Client $client
     * @param bool $processPatient
     * @throws \Exception
     */
    public function __construct($client, $processPatient = true)
    {
        $this->setClient($client);
        if ($processPatient) {
            $this->setPatients();
            $this->processPatients();
        }
        // this to tell other classes we need to track data.
//        $this->getClient()->setSaveToREDCap(true);
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
                if ($patient['toDoProviderTaskCount'] == 0) { //commented for testing.
                    break;
                }
                $patients[$index]['object'] = new Patient($this->getClient(), $patient); //Create new patient object
                $journal_entry_photos = $patients[$index]['object']->getJournalEntryPhotos();
                $provider_tasks = $patients[$index]['object']->getProviderTasks();
                if (!empty($journal_entry_photos)) { //Users has pictures to upload
                    foreach($journal_entry_photos as $index => $photo) {
                        if(!isset($photo['media'])){ //Will only be set if corresponding provider task exists
                            $this->getClient()->getEm()->emLog(
                                'Provider Task for corresponding journalEntry ' . $photo['uuid'] . ' , patient '. $patient['user']['uuid'] . ' Already adjudicated, skipping '
                            );
                            continue;
                        }

                        if (!$this->checkRecordExist($photo['uuid'])) { //Not in our database, save record
                            $data['task_uuid'] = $photo['uuid']; //This is our record ID
                            $data['patient_uuid'] = $patient['user']['uuid'];
                            $data['activity_uuid'] = $photo['activityUuid'];
                            $data['created'] = $photo['created']; //keep track of photo upload time
                            $data['status'] = $provider_tasks[$index]['status'];
                            $data['full_json'] = json_encode($provider_tasks[$index]);
                            $data['confidence'] = $photo['confidence'];
                            $data['results'] = $photo['results']; //result yes/no
                            $data['provider_task_uuid'] = $provider_tasks[$index]['uuid'];
                            $data['provider_survey_uuid'] = $provider_tasks[$index]['survey']['uuid'];

                            $response = \REDCap::saveData('json', json_encode(array($data)));
                            if (!empty($response['errors'])) {
                                if (is_array($response['errors'])) {
                                    throw new \Exception(implode(",", $response['errors']));
                                } else {
                                    throw new \Exception($response['errors']);
                                }
                            } else {
                                try { //upload photo here
                                    $photo['media']->uploadImage(
                                        $photo['uuid'],
                                        'image_file',
                                        $this->getClient()->getEm()->getFirstEventId(),
                                        $this->getClient()->getEm()->getProjectSetting('api-token')
                                    );
                                    $this->getClient()->getEm()->emLog("Patient :" . $patient['user']['uuid'] . " was imported successfully");
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


    /**
     * @param $user_uuid
     * @param $task_uuid
     * @param $confidence Adjudicator confidence
     * @param $results
     * @param $type
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTask($user_uuid, $task_uuid, $results, $confidence,
                               $readable, $comments, $passfail, $reagents, $volume, $disc, $seal)
    {
        try {
            $numargs = func_num_args();

            if ($numargs === 11) {
                global $Proj;
                $record_data = json_decode(\REDCap::getData($Proj->project_id, 'json', $task_uuid))[0]; //Fetch task record

                $this->setProviderSurvey($record_data->provider_survey_uuid);
                if (empty($record_data->adjudication_date)) { //If the record hasn't already been adjudicated
                    $update_json = $this->prepareProviderTask($record_data, array(
                        'results' => $results,
                        'readable' => $readable,
                        'adj_conf'=> $confidence,
                        'comments' => $comments,
                        'passfail' => $passfail,
                        'reagents' => $reagents,
                        'volume' => $volume,
                        'disc' => $disc,
                        'seal' => $seal
                    ));

                    $options = [
                        'headers' => [
                            'Authorization' => "Bearer " . $this->getClient()->getToken(),
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode($update_json, JSON_UNESCAPED_SLASHES)
                    ];

                    $response = $this->getClient()->createRequest(
                        'put',
                        FULL_PATTERN_HEALTH_API_URL . 'users/' . $user_uuid . '/tasks/' . $record_data->provider_task_uuid . '?adminOverride=true&hideProviderTasks=false',
                        $options
                    );

                    if(!empty($response) && !strpos($response, 'error')) {
                        $data['task_uuid'] = $task_uuid;
                        $data['status'] = 'completed';
                        $data['coordinator_response'] = $results;
                        $data['coordinator_user_id'] = USERID;
                        $data['readable'] = $readable;
                        $data['response_json'] = json_encode($response); //Different than full_json, response is a different structure. Changed to allow for multiple adjudicaiton submissions if erroneous
                        $data['adjudication_date'] = $update_json->finishTime;
                        $data['adj_conf'] = $confidence;
                        $data['comments'] = $comments;
                        $data['passfail'] = $passfail;
                        $data['reagents'] = $reagents;
                        $data['volume'] = $volume;
                        $data['disc'] = $disc;
                        $data['seal'] = $seal;

                        $save = \REDCap::saveData(
                            $this->getClient()->getEm()->getProjectId(),
                            'json',
                            json_encode(array($data))
                        );

                        if (!empty($save['errors'])) {
                            if (is_array($save['errors']))
                                throw new \Exception('Error: '. implode(".", $save['errors']));
                            else
                                throw new \Exception('Error: '. $save['errors']);
                        }

                        http_response_code(200);//return 200 on success
                    }else{
                        throw new \Exception('ERROR: encountered erroneous response data for user: ' . $user_uuid . ' task ' . $record_data->provider_task_uuid);
                    }


                } else {
                    $this->getClient()->getEm()->emError("Record $task_uuid has already been updated, skipping");
                    http_response_code(200);//send 200 to remove picture from screen
                }

            } else {
                throw new \Exception('ERROR: Failed to update task, empty parameters recieved from client');
            }
        } catch (\Exception $e) {
            $this->getClient()->getEm()->emError($e->getMessage());
            http_response_code(400);
        }
    }

    private function prepareProviderTask($record, $data)
    {
        $update_json = json_decode($record->full_json);
        $update_json->status = 'completed';
        $update_json->progress = '1';
        $update_json->finishTime = gmdate("Y-m-d\TH:i:s\Z");
        $update_json->measurements = $this->processMeasurements($data);
        return $update_json;
    }

    private function processMeasurements($data)
    {
        $measurements = array();
        $survey = $this->getProviderSurvey();
        $groupKey = Client::generateUUID();;
        foreach ($survey['elements'] as $element) {
            if ($data[$element['identifier']] == null) {
                continue;
            }

            $measurement = new \stdClass();
            $measurement->allDay = false;
            $measurement->created = gmdate("Y-m-d\TH:i:s\Z");
            $measurement->startTime = gmdate("Y-m-d\TH:i:s\Z");
            $measurement->endTime = gmdate("Y-m-d\TH:i:s\Z");
            $measurement->groupKey = $groupKey;
            // if its multiple option make sure its in array
            if ($element['constraints']['type'] == 'MultiValueIntegerConstraints') {
                $measurement->json = [$data[$element['identifier']]];
            } else {
                if($element['identifier'] === "adj_conf")
                    $measurement->json = (int)$data[$element['identifier']];
                elseif($element['identifier'] === "passfail" || $element['identifier'] === 'results')
                    $measurement->json = array($data[$element['identifier']]);
                elseif($element['identifier'] === "comments")
                    $measurement->json = $data[$element['identifier']];
                else
                    $measurement->json = $data[$element['identifier']] == "true" ? true : false;
            }

            $measurement->modified = gmdate("Y-m-d\TH:i:s\Z");
            $measurement->sourceName = "pattern/pattern-survey-ui";
            $measurement->sourceUniqueId = $survey['uuid'] . $element['identifier'] . 'surveyAnswer0' . time();
            $measurement->survey = array(
                "title" => $survey['name'],
                "href" => $survey['href'],
            );
            $measurement->surveyQuestionId = $element['identifier'];
            $measurement->type = "surveyAnswer";
            $measurements[] = $measurement;
        }
        return $measurements;
    }


    public function checkRecordExist($record_id)
    {
        $records = $this->getRecordCache();
        if (!isset($records)) {
            $param = array(
                'return_format' => 'array',
                'fields' => \REDCap::getRecordIdField() //this is the task uuid JEP
            );

            $records = \REDCap::getData($param);
            $this->setRecordCache($records);
        }

        return isset($records[$record_id]);
    }

    /**
     * @return array
     */
    public function getRecordCache()
    {
        return $this->record_cache;
    }

    /**
     * @param array $record_cache
     */
    public function setRecordCache($record_cache)
    {
        $this->record_cache = $record_cache;
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
            $this->patients = $this->getClient()->createRequest(
                'get',
                FULL_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members?includePlans=false&adherenceDays=0&sortBy=TODO&sortDirection=DESC&offset=0');
        } else {
            $this->patients = $patients;
        }
    }

    /**
     * @return array
     */
    public function getProviderSurvey()
    {
        return $this->provider_survey;
    }

    /**
     * @param array $provider_survey
     */
    public function setProviderSurvey($surveyUUID)
    {
        $this->provider_survey = $this->getClient()->createRequest(
            'get',
            FULL_PATTERN_HEALTH_API_URL . 'surveys/' . $surveyUUID . '/latest');
    }


}
