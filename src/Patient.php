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
            $this->tasks = $this->getClient()->request('get', BASE_PATTERN_HEALTH_API_URL . 'users/' . $uuid . '/tasks');
        } else {
            $this->tasks = $tasks;
        }
    }
}
