<?php


namespace Stanford\LampStudyPortal;

use \GuzzleHttp\Client;
use Sabre\VObject\Cli;

/**
 * Class Media
 * @package Stanford\LampStudyPortal
 * @property \GuzzleHttp\Client $client;
 */
class Media
{
    /** @var string $id */
    private $id;

    /** @var string $doc_ref */
    private $doc_ref;

    /** @var string $binary */
    private $binary;

    /** @var string $api_endpoint */
    private $api_endpoint;

    /** @var string $title */
    private $title;

    /**
     * @var Client $client
     */
    private $client;


    public function __construct($client, $title, $api_endpoint)
    {
        $this->setTitle($title);
        $this->setClient($client);
        $this->setApiEndpoint($api_endpoint);

        $this->setBinary($this->getClient()->request('GET', BASE_PATTERN_HEALTH_API_URL . ltrim($this->getApiEndpoint(), '/')));
        // Other code to run when object is instantiated
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDocRef()
    {
        return $this->doc_ref;
    }

    /**
     * @param string $doc_ref
     */
    public function setDocRef($doc_ref)
    {
        $this->doc_ref = $doc_ref;
    }

    /**
     * @return string
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * @param string $binary
     */
    public function setBinary($binary)
    {
        $this->binary = $binary;
    }

    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->api_endpoint;
    }

    /**
     * @param string $api_endpoint
     */
    public function setApiEndpoint($api_endpoint)
    {
        $this->api_endpoint = $api_endpoint;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
