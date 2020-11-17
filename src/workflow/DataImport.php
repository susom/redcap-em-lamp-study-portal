<?php


namespace Stanford\LampStudyPortal;


class DataImport
{
    /** @var Client $client */
    private $client;

    /** @var array $patients */
    private $patients;

    /** @var array $record_cache */
    private $record_cache;

    /** @var String $ts_start*/
    private $ts_start;

    /**
     * Task -> Redcap conversion map
     */
     private $map;

    /**
     * DataImport constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->setClient($client);
        $this->setTsStart(microtime(true));
        $this->setMap(json_decode(file_get_contents($this->getClient()->getEm()->getModulePath() . 'src/workflow/map.json'), true));
        $this->processPatients();
    }


    private function processPatients()
    {
        $this->getClient()->getEm()->emLog('Starting query for patients');
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) {
                $patientObj = new Patient($this->getClient(), $patient); //Create new patient object, contains all tasks
                if(!$this->checkRecordExist($patient['user']['uuid'])) { //Check if this patient is already saved within redcap
                    $this->createPatientRecord($patientObj, $patientObj->getConstants());
                    $this->getClient()->getEm()->emLog('Patient UUID ' .  $patient['user']['uuid'] . ' Created');
                }
                $this->createTaskRecord($patientObj);
            }
        } else {
            $this->getClient()->getEm()->emError('No patients currently exist for current GroupID ', $this->getClient()->getGroup());
            \REDCap::logEvent("ERROR/EXCEPTION occurred", '', '', 'No patients have been returned from Pattern');

        }

    }

    public function createTaskRecord(Patient $patient)
    {
        global $Proj;
        $all_tasks = $patient->getTasks();
        $data = [];
        foreach($all_tasks as $index => $task) {
            if(!empty($task['measurements'])){ // The user has some survey answers
                $map = $this->getTaskMap($task);
                if(!empty($map)) { //
                    $form_data = [];
                    $missing_fields = [];
                    $prefix =$map['prefix'];
                    foreach ($task['measurements'] as $measurement) {
                        $question_id = $measurement['surveyQuestionId'];
                        $value = is_array($measurement['json']) ? json_encode($measurement['json']) : $this->castAsString($measurement['json']);
                        $full_name = $prefix . $question_id;
                        if (!isset($Proj->metadata[$full_name]))
                            array_push($missing_fields, $full_name);

                        else
                            $form_data[$full_name] = $value; //Set data
                    }

                    if (!empty($task['finishTime'])) {
                       if(! isset($Proj->metadata[$prefix . 'finish_time']))
                           array_push($missing_fields, $prefix . 'finish_time');
                       else
                           $form_data[$prefix . 'finish_time'] = $task['finishTime'];
                    }

                    if (!empty($missing_fields))
                        $this->getClient()->getEm()->emError(implode(",", $missing_fields));

                    if(!empty($form_data)) {
                        $form_data['redcap_event_name'] = $map['event_name'];
                        $form_data['record_id'] = $patient->getPatientJson()['user']['uuid'];
                        $data[] = $form_data;
                    }
                }
            }
        }
        if(! empty($data)) {
            $results =  \REDCap::saveData('json', json_encode($data));
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
        if(isset($map[$task['activityUuid']])) {
            return $map[$task['activityUuid']];
        } else {
            $this->getClient()->getEm()->emLog('Unmapped task :' . $task['activityUuid'] . ' Description '. $task['description']);
            return [];
        }

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
            $result =  \REDCap::saveData('json', json_encode(array($data)));
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
            $this->patients = $this->getClient()->request('get', BASE_PATTERN_HEALTH_API_URL . 'api/groups/' . $this->getClient()->getGroup() . '/members');
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

}
