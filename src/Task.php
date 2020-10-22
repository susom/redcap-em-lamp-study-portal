<?php



namespace Stanford\LampStudyPortal;

use \GuzzleHttp\Client;
/**
 * Class Task
 * @package Stanford\LampStudyPortal
 */
class Task
{
    /**
     * Static task constants : all tasks have these fields.
     */
    const uuid = 'task_uuid';
    const activityUuid = 'task_activity_uuid';
    const type = 'task_type';
    const description = 'task_description';
    const startTime = 'task_end_time';
    const endTime = 'task_start_time';
    const progress = 'task_progress';
    const status = 'task_status';
    const optional = 'task_optional';

    /** @var Client $client */
    private $client;

    /** @var array $task_json */
    private $task_json;

    /**
     * Task constructor.
     */
    public function __construct($client, $task_json)
    {
        $this->setClient($client);
        $this->setTaskJson($task_json);
    }

    public function getConstants()
    {
        $refl = new \ReflectionClass($this);
        return $refl->getConstants();
    }

    /**
     * @return array
     */
    public function getTaskJson()
    {
        return $this->task_json;
    }

    /**
     * @param array $task_json
     */
    public function setTaskJson($task_json)
    {
        $this->task_json = $task_json;
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
