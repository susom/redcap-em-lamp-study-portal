<?php

namespace Stanford\LampStudyPortal;

use Sabre\VObject\Cli;
use Stanford\LampStudyPortal\Task;
use \GuzzleHttp\Client;

/**
 * Class Patient
 * @package Stanford\LampStudyPortal
 */
class Patient
{
    /**
     * const variables
     */
    const uuid = 'patient_uuid';
    const email = 'patient_email';
    const mobileNumber = 'patient_mobile_number';
    const firstName = 'patient_first_name';
    const lastName = 'patient_last_name';
    const timeZone = 'patient_time_zone';
    const locale = 'patient_locale';
    const gender = 'patient_gender';
    const inHospital = 'patient_in_hospital';
    const birthDate = 'patient_birth_date';
    const accountStatus = 'patient_account_status';
    const lastLoginTime = 'patient_last_login';
    const locked = 'patient_locked';
    const verified = 'patient_verified';
    const mobileNumberVerified = 'patient_mobile_verified';

    /** @var array $patient_json */
    private $patient_json;

    /** @var array $tasks */
    private $tasks;

    /** @var Client $client */
    private $client;

    /** @var float $confidence */
    private $confidence;

    /**
     * Patient constructor.
     * @param $client
     * @param $patient_json
     */
    public function __construct($client, $patient_json)
    {
        $this->setClient($client);
        $this->setPatientJson($patient_json);
        $this->processTasks();
    }

    public function getConstants()
    {
        $refl = new \ReflectionClass($this);
        return $refl->getConstants();
    }

    private function processTasks()
    {
        $tasks = $this->getTasks();
        if (!empty($tasks)) {
            foreach ($tasks as $index => $task) {
                $tasks[$index]['object'] = new Task($this->getClient(), $task['uuid']);
                //TODO do we want to capture other tasks and other Journal Entries?
                if ($task['type'] == 'recordJournalEntry' && !empty($task['measurements'])) {
                    foreach ($task['measurements'] as $mIndex => $measurement) {
                        if ($measurement['type'] == 'journalEntryPhoto') {
                            $tasks[$index]['media']['object'] = new Media($this->getClient(), $measurement['media']['title'], $measurement['media']['href']);
                        } elseif ($measurement['surveyQuestionId'] == 'test_conf') {
                            $this->setConfidence($measurement['json'][0]); //Set patient confidence for later upload. Since they are in separate measurements.
                        }
                    }
                }
            }
            // after initializing the tasks objects update the array.
            $this->setTasks($tasks);
        }

    }

    public function getAttributes()
    {
        $attributes = [];
        foreach ($this as $key => $val) {
            array_push($attributes,$val);
        }
        return $attributes;
    }

    public function getPatientJson()
    {
        return $this->patient_json;
    }

    public function setPatientJson($patient_json)
    {
        $this->patient_json=$patient_json;
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTasks()
    {
        if (!$this->tasks) {
            $this->setTasks();
        }
        return $this->tasks;
    }

    /**
     * @return float
     */
    public function getConfidence()
    {
        return $this->confidence;
    }

    /**
     * @param float $confidence
     */
    public function setConfidence($confidence)
    {
        $this->confidence = $confidence;
    }

    /**
     * @param array $tasks
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setTasks($tasks = array())
    {
        $uuid = $this->getPatientJson()['user']['uuid'];
        $parameters = array(
            'includeMeasurements' => true,
            'excludeBiometricMeasurements' => false,
            'includeInactivePlanTasks' => true,
            "hideProviderTasks" => false,
            "includeSurveyElements" => false
        );
//        http_build_query($parameters)
        // first time just make api call to get the tasks for this patient otherwise update existing ones.
        if (empty($tasks)) {
            $this->tasks = $this->getClient()->request('get', FULL_PATTERN_HEALTH_API_URL . 'users/' . $uuid . '/tasks?includeMeasurements=true&excludeBiometricMeasurements=false&inclusiveInactivePlanTasks=true&hideProviderTasks=false&includeSurveyElements=false');
        } else {
            $this->tasks = $tasks;
        }
    }
}
