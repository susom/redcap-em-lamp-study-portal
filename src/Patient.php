<?php

namespace Stanford\LampStudyPortal;

use Stanford\LampStudyPortal\Task;

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

    /**
     * Patient constructor.
     */
    public function __construct($uuid) {
        parent::__construct();
        // Other code to run when object is instantiated
        $this->$uuid = $uuid;
        $this->tasks = array();
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
        return $this->tasks;
    }

    /**
     * @param array $tasks
     */
    public function setTasks($tasks)
    {
        $this->tasks = $tasks;
    }


}
