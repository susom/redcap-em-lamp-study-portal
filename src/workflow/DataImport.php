<?php


namespace Stanford\LampStudyPortal;


class DataImport
{
    /** @var Client $client */
    private $client;

    /** @var array $patients */
    private $patients;

    /**
     * DataImport constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->setClient($client);
        $this->processPatients();
    }

    private function processPatients()
    {
        $patients = $this->getPatients();
        if ($patients['totalCount'] > 0) {
            foreach ($patients['results'] as $index => $patient) {
                $patientObj = new Patient($this->getClient(), $patient);
                $this->createPatientRecord($patientObj, $patientObj->getConstants());
                break; //for testing only
            }
        } else {
            $this->getClient()->getEm()->emError('No patients currently exist for current GroupID ', $this->getClient()->getGroup());
        }

    }

    /**
     * @param Patient $patient patient object
     * @param array $attributes associative mapping table between pattern keys and redcap keys
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

            $result =  \REDCap::saveData('json', json_encode(array($data)));
            if (!empty($result['errors'])) $this->emError("Errors saving result: ", '', '', $result);

        } else {
            $this->emError('No patient currently passed', '', $patient, $attributes);
        }
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
            $this->patients = $this->getClient()->request('get', BASE_PATTERN_HEALTH_API_URL . 'groups/' . $this->getClient()->getGroup() . '/members');
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

}
