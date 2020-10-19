<?php



namespace Stanford\LampStudyPortal;

use \GuzzleHttp\Client;
/**
 * Class Task
 * @package Stanford\LampStudyPortal
 */
class Task
{
    /** @var string $uuid */
    private $uuid;

    /** @var string $type */
    private $type;

    /** @var string $created_date */
    private $created_date;

    /** @var string $modified_date */
    private $modified_date;

    /** @var string $status */
    private $status;

    /** @var Client $client */
    private $client;

    /**
     * Task constructor.
     */
    public function __construct($client, $uuid)
    {
        // Other code to run when object is instantiated

        $this->setUuid($uuid);
        $this->setClient($client);
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getCreatedDate()
    {
        return $this->created_date;
    }

    /**
     * @param string $created_date
     */
    public function setCreatedDate($created_date)
    {
        $this->created_date = $created_date;
    }

    /**
     * @return string
     */
    public function getModifiedDate()
    {
        return $this->modified_date;
    }

    /**
     * @param string $modified_date
     */
    public function setModifiedDate($modified_date)
    {
        $this->modified_date = $modified_date;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
