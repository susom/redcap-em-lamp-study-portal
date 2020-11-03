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

    /** @var array $provider_tasks */
    private $provider_tasks;

    /** @var array $journal_entry_photos */
    private $journal_entry_photos;

    /** @var array */
    private $media;

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
        $this->fetchMediaInformation();
    }

    public function getConstants()
    {
        $refl = new \ReflectionClass($this);
        return $refl->getConstants();
    }


    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * Function that fetches provider and journal tasks, run once for each Patient
     */
    private function processTasks()
    {
        $tasks = $this->getTasks(); //Pull all user tasks
        $provider = array();
        $journal = array();

        if (!empty($tasks)) {
            foreach ($tasks as $index => $task) { //We only want to iterate through each task array once:: save all that apply
                //Create new task object for mapping
                $tasks[$index]['object'] = new Task($this->getClient(), $task['uuid']);

                switch ($task['description']) { //We only care about types of recordJournalEntry and Provider
                    case "Provider - Review Test 1 Results": //save them in specific order relative to survey 0-2
                        $provider[0] = $task;
                        break;
                    case "Provider - Review Test 2 Results":
                        $provider[1] = $task;
                        break;
                    case "Provider - Review Test 3 Results":
                        $provider[2] = $task;
                        break;
                    case "Record Test 1 Results": //Keep track of tasks that have a JournalEntryPhoto
                        $journal[0] = $task;
                        break;
                    case "Record Test 2 Results":
                        $journal[1] = $task;
                        break;
                    case "Record Test 3 Results":
                        $journal[2] = $task;
                        break;
                }
            }

            $this->setProviderTasks($provider); //Set the tasks that are providers
            $this->setJournalEntryPhotos($journal); //Set the tasks that have a journal Entry photo
            // after initializing the tasks objects update the array.
            $this->setTasks($tasks);
        }

    }

    /**
     * Function that will set the internal media array to the # of media objects able to be uploaded
     */
    public function fetchMediaInformation()
    {
        $provider_tasks = $this->getProviderTasks();
        if (!empty($provider_tasks)) { //Skip all downloads if no provider task, no adjudication needed
            $journal_entry_photos = $this->getJournalEntryPhotos();
            foreach($provider_tasks as $ind => $task) { //Iterate through provider tasks (max 3)
//                if($task['status'] == 'inProgress') { // commented out for testing
//                if($task['status'] == 'failed') { // only want to save images if status is pending
                    foreach($journal_entry_photos[$ind]['measurements'] as $mind => $measurement) { //iterate over all measurements for a corresponding task containing a photo, we have the match via $ind
                        if ($measurement['type'] == 'journalEntryPhoto') {
                            $journal_entry_photos[$ind]['media'] = new Media($this->getClient(), $measurement['media']['title'], $measurement['media']['href']); //create new key to save media object
                        } elseif ($measurement['surveyQuestionId'] == 'test_conf') {
                            $journal_entry_photos[$ind]['confidence'] = $measurement['json'][0];
                        } elseif($measurement['surveyQuestionId'] == 'results') {
                            $journal_entry_photos[$ind]['results'] = $measurement['json'][0];
                        }
                    }
//                }
            }
            //push updates to journal photos
            $this->setJournalEntryPhotos($journal_entry_photos);
        }
    }

    /**
     * @return array
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param array $media
     */
    public function setMedia($media)
    {
        $this->media = $media;
    }


    /**
     * @return array
     */
    public function getJournalEntryPhotos()
    {
        return $this->journal_entry_photos;
    }

    /**
     * @param array $journal_entry_photos
     * Tasks which have a Journal Entry photo
     */
    public function setJournalEntryPhotos($journal_entry_photos)
    {
        $this->journal_entry_photos = $journal_entry_photos;
    }


    /**
     * @param $tasks
     */
    public function setProviderTasks($tasks)
    {
        $this->provider_tasks = $tasks;
    }

    /**
     * @return array
     */
    public function getProviderTasks()
    {
        return $this->provider_tasks;
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
        // first time just make api call to get the tasks for this patient otherwise update existing ones.
        if (empty($tasks)) {
            $uuid = $this->getPatientJson()['user']['uuid'];
            $this->tasks = $this->getClient()->request('get', FULL_PATTERN_HEALTH_API_URL . 'users/' . $uuid . '/tasks?includeMeasurements=true&excludeBiometricMeasurements=false&inclusiveInactivePlanTasks=true&hideProviderTasks=false&includeSurveyElements=false');
        } else {
            $this->tasks = $tasks;
        }
    }
}
