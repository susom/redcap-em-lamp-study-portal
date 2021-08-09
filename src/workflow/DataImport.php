<?php


namespace Stanford\LampStudyPortal;


use Sabre\VObject\Property\VCard\DateTime;

class DataImport
{
    /** @var Client $client */
    private $client;

    /** @var array $patients */
    private $patients;

    /** @var array $record_cache */
    private $record_cache;

    /** @var array $last_update_cache */
    private $last_update_cache;

    /** @var String $ts_start */
    private $ts_start;

    /** @var -> Redcap conversion map */
    private $map;

    /** @var -> List of activities to ignore */
    private $ignore_list;

    /** @bool -> T/F to ignore pictures & attachments */
    private $ignore_uploads;

    /**
     * @var array $post_processed_files
     */
    private $post_processed_files;

    /**
     * DataImport constructor.
     * @param Client $client
     */
    public function __construct(Client $client, $ignore_uploads = false)
    {
        $this->setClient($client);
        $this->setTsStart(microtime(true));
        $this->setMap(json_decode(file_get_contents($this->getClient()->getEm()->getModulePath() . 'src/workflow/map.json'), true));
        $this->setIgnoreList(json_decode(file_get_contents($this->getClient()->getEm()->getModulePath() . 'src/workflow/ignore.json'), true));
        $this->setIgnoreUploads($ignore_uploads);
        $this->processPatients();
    }

    private function processPatients()
    {
        $this->getClient()->getEm()->emLog('Starting query for patients');
        $patients = $this->getPatients(); //will return 0-x where x is 200 max
        if($patients['totalCount'] == 0) {
            $this->getClient()->getEm()->emError('No patients currently exist for current GroupID ', $this->getClient()->getGroup());
            \REDCap::logEvent("ERROR/EXCEPTION occurred", '', '', 'No patients have been returned from Pattern');
        } else { //total is greater than 200 max, have to partition
            $total_left = $patients['totalCount'];
            $offset = 0;
            while($total_left > 0) {
                $this->runPatientIteration($patients, $offset); //each subsequent will be in batches of 200
                $total_left = $total_left - 200;
                $offset = $offset + 200;
            }
        }
    }

    public function runPatientIteration($patients, $offset = 0)
    {
        if($offset !== 0)
            $patients = $this->getClient()->createRequest('get', BASE_PATTERN_HEALTH_API_URL . 'api/groups/' . $this->getClient()->getGroup() . "/members?limit=200&offset=$offset");

        foreach ($patients['results'] as $index => $patient) {
            sleep(1);
            $total_ct = $patients['totalCount'];
            $patient_id = $patient['user']['uuid'];
            $this->getClient()->getEm()->emLog("Running patient iteration, total: $total_ct, offset: " . ($offset+$index) . ", current patient: $patient_id ");

            if (!$this->checkValidPatient($patient))
                continue;
//            if ($patient_id !== 'u-UNAuFz-kQ-DuBjwdSLnBQA')
//                continue;

            $patientObj = new Patient($this->getClient(), $patient); //Create new patient object, contains all tasks
            if (!$this->checkRecordExist($patient['user']['uuid'])) { //Check if this patient is already saved within redcap
                $this->createPatientRecord($patientObj, $patientObj->getConstants());
                $this->getClient()->getEm()->emLog('Patient UUID ' . $patient['user']['uuid'] . ' Created');
            }
            $last_task_updated_time = $this->checkLastUpdateTime($patient['user']['uuid']);

            $this->createTaskRecord($patientObj, $last_task_updated_time);
        }

        if(!$this->getIgnoreUploads()) // Only process upload files if ignore_uploads is false
            $this->postProcessUpload();

    }


    /**
     * @param $patient_json
     * @return bool
     */
    public function checkValidPatient($patient_json)
    {
        $first = $patient_json['user']['firstName'];
        $last = $patient_json['user']['lastName'];
        if (strpos($first,"first_") !== false && strpos($last, "last_") !== false) {
            return false;
        }
        return true;
    }

    /**
     * @param Patient $patient
     */
    public function postProcessUpload()
    {
        $measurements = $this->getPostProcessedFiles();
        if (!empty($measurements)) {
            foreach ($measurements as $index => $measurement) {
                $media = new Media($this->getClient(), $measurement['media']['title'], $measurement['media']['href']); //create new key to save media object
                try {
                    $media->uploadImage(
                        $measurement['record_id'], //record ID field
                        $measurement['prefix'] . 'image_file',
                        $measurement['event_name'],
                        $this->getClient()->getEm()->getProjectSetting('api-token')
                    );
                    $this->getClient()->getEm()->emLog("Image " . $measurement['prefix'] . 'image_file' . " for:  " . $measurement['record_id'] . " was imported successfully");
                } catch (\Exception $e) {
                    $this->getClient()->getEm()->emError($e->getMessage());
                }
            }
        }
    }

    public function checkLastUpdateTime($record_id)
    {
        $records = $this->getLastUpdateCache();
        if (!isset($records)) { //first patient calls this getData call only
            $param = array(
                'return_format' => 'array',
                'fields' => 'last_task_update_time', //last time any tasks have been updated
                'events' => 'baseline_arm_1'
            );

            $records = \REDCap::getData($param);
            $this->setLastUpdateCache($records);
        }
        if(isset($records[$record_id])){ //If we are updating a patient's records
            $event_id = array_keys($records[$record_id])[0];
            return $records[$record_id][$event_id]['last_task_update_time'];
        } else { //Otherwise this is a new patient, so return nothing
            return '';
        }

    }

    /**
     * @param Patient $patient
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTaskRecord(Patient $patient, $last_task_updated_time)
    {
        global $Proj;
        $all_tasks = $patient->getTasks();
        $data = [];

        foreach($all_tasks as $index => $task) {

            if(isset($task['finishTime']) && !$this->getIgnoreUploads()) { //check if we have to update records
                $ft = strtotime($task['finishTime']);
                $lu = strtotime($last_task_updated_time);

                if(!empty($lu) && $ft < $lu)
                    continue; //skip over all measurements in this task, do not update.
            }


            if(!empty($task['measurements'])){ // The user has some survey answers
                $map = $this->getTaskMap($task);

                if(!empty($map)) { //
                    $form_data = [];
                    $missing_fields = [];
                    $prefix = $map['prefix'];

                    foreach ($task['measurements'] as $measurement) {
                        $question_id = $measurement['surveyQuestionId'];
                        $value = is_array($measurement['json']) ? json_encode($measurement['json']) : $this->castAsString($measurement['json']);
                        $full_name = strtolower(str_replace(' ', '_', ($prefix . $question_id))); //Replace all spaces with underscores for REDCAP, lowercase

                        if (!isset($question_id)) { // This measurement is a Journal entry type
                            if (isset($measurement['media']['href'])) { //Photo
                                $measurement['record_id'] = $patient->getPatientJson()['user']['uuid'];
                                $measurement['event_name'] = $map['event_name'];
                                if($map['description'] === 'CHILD SIGNATURE REQUIRED - Child assent form') //only exception, have to route children consent to different field in the same instrument
                                    $measurement['prefix'] = "c_child_";
                                else
                                    $measurement['prefix'] = $map['prefix'];
                                $this->setPostProcessedFiles($measurement);
                            } elseif (isset($measurement['text'])) {
                                $form_data[$prefix . 'submission_text'] = $measurement['text']; //Text response
                            } //always skip others, as the actual surveys dont have to be stored per patient (signatureCompleted)
                                continue; //skip field push, images can be handled after
                        }

                        if(strpos($question_id,"i_")) //if the question is designated to be skipped
                            continue;

                        if (!isset($Proj->metadata[$full_name]))
                            array_push($missing_fields, $full_name);
                        else
                            $form_data[$full_name] = $value; //Set data, redcap can only support lowercase
                    } //End of measurement iteration

                    if (!empty($task['finishTime'])) {
                       if(! isset($Proj->metadata[$prefix . 'finish_time'])) {
                           array_push($missing_fields, $prefix . 'finish_time');
                       } else {
                           $form_data[$prefix . 'finish_time'] = $task['finishTime'];
                           $form_data[$prefix . 'readable_finish_time'] = gmdate('Y-m-d H:i:s', strtotime($task['finishTime']));
                       }
                    }

                    if (!empty($task['startTime'])) {
                        if(! isset($Proj->metadata[$prefix . 'start_time']))
                            array_push($missing_fields, $prefix . 'start_time');
                        else
                            $form_data[$prefix . 'start_time'] = $task['startTime'];
                    }

                    if (!empty($missing_fields)) //Explode missing fields for mapping
                        $this->getClient()->getEm()->emError('Missing fields: ' . implode(" ", $missing_fields));

                    if(!empty($form_data)) {
                        if(empty($data[$map['event_name']])){
                            $data[$map['event_name']] = [];
                        }
                        $data[$map['event_name']] = array_merge($data[$map['event_name']], $form_data);

                    }
                }
            }
        }

        if (!empty($data)) {
            $payload = [];
            $update_time_needed = 1;

            foreach($data as $event_name => $fields){
                if($event_name === 'baseline_arm_1'){
                    $update_time_needed = 0; //If we make updates to baseline, we include last task update time
                    $payload[] = array_merge($fields, [
                        "record_id" => $patient->getPatientJson()['user']['uuid'],
                        "redcap_event_name" => $event_name,
                        "last_task_update_time" => gmdate("Y-m-d\TH:i:s\Z")
                    ]);
                } else {
                    $payload[] = array_merge($fields, [
                        "record_id" => $patient->getPatientJson()['user']['uuid'],
                        "redcap_event_name" => $event_name,
                    ]);
                }
            }

            if($update_time_needed) { //We did not encounter baseline update in payload, create a new element to update
                $payload[] = [
                    "record_id" => $patient->getPatientJson()['user']['uuid'],
                    "redcap_event_name" => "baseline_arm_1",
                    "last_task_update_time" => gmdate("Y-m-d\TH:i:s\Z")
                ];
            }

            $response =  \REDCap::saveData('json', json_encode($payload));
            if (!empty($response['errors'])) {
                if (is_array($response['errors'])) {
                    throw new \Exception(implode(",", $response['errors']));
                } else {
                    throw new \Exception($response['errors']);
                }
            }
        }

    }


    /**
     * @param $value
     * @return string
     */
    public function castAsString($value)
    {
        if (is_bool($value))
            $converted = $value ?  'true' : 'false';
        else
            $converted = strval($value);

        return $converted;
    }

    /**
     * @param $task
     * @return array|mixed
     */
    public function getTaskMap($task)
    {
        $map = $this->getMap();
        $ignore = $this->getIgnoreList();
//        if($task['activityUuid'] === 'act-qynuiPpTEZ8yZnYjukDTjw' ||
//            $task['activityUuid'] === 'act-Sm_98nPEdZ6mt_1HmhfcFw' ||
//            $task['activityUuid'] === 'act-Ta4pcKevWd5Qh_igM5qXpA' ||
//            $task['activityUuid'] === 'act-oCWpL5rzrcyJAwNrMG2mYQ')
//            $b = 1;

        if(isset($ignore[$task['activityUuid']])) //If task id is in ignore list, skip without logging
            return [];

        if($task['type'] === 'signDocument'){
            if(isset($map[$task['activityUuid']]))
                return $map[$task['activityUuid']];
        } else {
            if(isset($map[$task['survey']['uuid']])) { //Else return the mapping obj
                return $map[$task['survey']['uuid']];
            } else {
                if (isset($task['survey']['uuid'])) { //some elements have a survey UUID
                    $this->getClient()->getEm()->emLog('Unmapped task: ' . $task['survey']['uuid'] . ' Description ' . $task['survey']['name']);
                    return [];
                }
            }
        }
        $this->getClient()->getEm()->emLog('Unmapped task: ' . $task['activityUuid'] . ' Description ' . $task['description']);
        return [];
    }


    /**
     * @param Patient $patient patient object
     * @param array $attributes associative mapping array between pattern keys and redcap keys
     */
    public function createPatientRecord(Patient $patient, array $attributes)
    {
        if (isset($patient) && !empty($attributes)) {
            $patient_json = $patient->getPatientJson();
            $data = array("record_id" => $patient_json['user']['uuid']);

            //Update all patient variables in redcap instrument
            foreach($attributes as $pattern_key => $redcap_variable_name){
                if(isset($patient_json['user'][$pattern_key])){
                    $data[$redcap_variable_name] = is_string($patient_json['user'][$pattern_key]) ? $patient_json['user'][$pattern_key] : (string)(int)$patient_json['user'][$pattern_key];
                }
            }
            $data['redcap_event_name'] = 'baseline_arm_1'; //necessary
            $response =  \REDCap::saveData('json', json_encode(array($data)));
            if (!empty($response['errors'])) {
                if (is_array($response['errors']))
                    $this->getClient()->getEm()->emError(implode(",", $response['errors']));
                else
                    $this->getClient()->getEm()->emError($response['errors']);
            }
        } else {
            $this->getClient()->getEm()->emError('Either patient or attribute map is null: ', '', $patient, $attributes);
        }
    }


    /**
     * @param $record_id Patient UUID
     * @return bool
     */
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
    public function getLastUpdateCache()
    {
        return $this->last_update_cache;
    }

    /**
     * @param array $last_update_cache
     */
    public function setLastUpdateCache($last_update_cache)
    {
        $this->last_update_cache = $last_update_cache;
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
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setPatients($patients = array())
    {
        if (empty($patients)) {
            $this->patients = $this->getClient()->createRequest('get', BASE_PATTERN_HEALTH_API_URL . 'api/groups/' . $this->getClient()->getGroup() . '/members?limit=200');
        } else {
            $this->patients = $patients;
        }

    }

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
     * @return String
     */
    public function getTsStart()
    {
        return $this->ts_start;
    }

    /**
     * @param String $ts_start
     */
    public function setTsStart($ts_start)
    {
        $this->ts_start = $ts_start;
    }

    /**
     * @return mixed
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param mixed $map
     */
    public function setMap($map)
    {
        $this->map = $map;
    }

    /**
     * @return mixed
     */
    public function getIgnoreList()
    {
        return $this->ignore_list;
    }


    /**
     * @param mixed $ignore_list
     */
    public function setIgnoreList($ignore_list)
    {
        $this->ignore_list = $ignore_list;
    }

    public function getIgnoreUploads()
    {
        return $this->ignore_uploads;
    }

    public function setIgnoreUploads($response)
    {
        $this->ignore_uploads = $response;
    }

    /**
     * @return array
     */
    public function getPostProcessedFiles()
    {
        return $this->post_processed_files;
    }

    /**
     * @param array $post_processed_files
     */
    public function setPostProcessedFiles($post_processed_files)
    {
        $this->post_processed_files[] = $post_processed_files;
//        if(empty($this->post_processed_files))
//            $this->post_processed_files = $post_processed_files;
//        else
//            $this->post_processed_files[] = $post_processed_files;
    }



}
