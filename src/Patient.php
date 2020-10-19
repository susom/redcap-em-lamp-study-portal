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
    /** @var string $uuid */
    private $uuid;

    /** @var string $email */
    private $email;

    /** @var string $mobile_number */
    private $mobile_number;

    /** @var string $first_name */
    private $first_name;

    /** @var string $last_name */
    private $last_name;

    /** @var string $time_zone */
    private $time_zone;

    /** @var string $locale */
    private $locale;

    /** @var string $gender */
    private $gender;

    /** @var boolean $in_hospital */
    private $in_hospital;

    /** @var string $birth_date */
    private $birth_date;

    /** @var string $account_status */
    private $account_status;

    /** @var string $last_login_time */
    private $last_login_time;

    /** @var booleann $locked */
    private $locked;

    /** @var boolean $verified */
    private $verified;

    /** @var string $mobile_number_verified */
    private $mobile_number_verified;

    /** @var array $tasks */
    private $tasks;

    /** @var Client $client */
    private $client;


    /**
     * Patient constructor.
     */
    public function __construct($client, $uuid)
    {
        $this->setUuid($uuid);
        $this->setClient($client);

        $this->processTasks();

    }

    private function processTasks()
    {
        $tasks = $this->getTasks();

        if ($tasks['totalCounts'] > 0) {
            foreach ($tasks['results'] as $index => $task) {
                $tasks['results'][$index]['object'] = new Task($this->getClient());
            }
            // after initializing the tasks objects update the array.
            $this->setTasks($tasks);
        }

    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getMobileNumber()
    {
        return $this->mobile_number;
    }

    /**
     * @param string $mobile_number
     */
    public function setMobileNumber($mobile_number)
    {
        $this->mobile_number = $mobile_number;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->time_zone;
    }

    /**
     * @param string $time_zone
     */
    public function setTimeZone($time_zone)
    {
        $this->time_zone = $time_zone;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return bool
     */
    public function isInHospital()
    {
        return $this->in_hospital;
    }

    /**
     * @param bool $in_hospital
     */
    public function setInHospital($in_hospital)
    {
        $this->in_hospital = $in_hospital;
    }

    /**
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * @param string $birth_date
     */
    public function setBirthDate($birth_date)
    {
        $this->birth_date = $birth_date;
    }

    /**
     * @return string
     */
    public function getAccountStatus()
    {
        return $this->account_status;
    }

    /**
     * @param string $account_status
     */
    public function setAccountStatus($account_status)
    {
        $this->account_status = $account_status;
    }

    /**
     * @return string
     */
    public function getLastLoginTime()
    {
        return $this->last_login_time;
    }

    /**
     * @param string $last_login_time
     */
    public function setLastLoginTime($last_login_time)
    {
        $this->last_login_time = $last_login_time;
    }

    /**
     * @return booleann
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @param booleann $locked
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
    }

    /**
     * @return string
     */
    public function getMobileNumberVerified()
    {
        return $this->mobile_number_verified;
    }

    /**
     * @param string $mobile_number_verified
     */
    public function setMobileNumberVerified($mobile_number_verified)
    {
        $this->mobile_number_verified = $mobile_number_verified;
    }

    /**
     * @return array
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
     */
    public function setTasks($tasks = array())
    {
        // first time just make api call to get the tasks for this patient otherwise update existing ones.
        if (empty($tasks)) {
            $this->tasks = $this->getClient()->request('get', BASE_PATTERN_HEALTH_API_URL . 'users/' . $this->getUuid() . '/tasks');;
        } else {
            $this->tasks = $tasks;
        }

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


}
