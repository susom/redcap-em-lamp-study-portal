<?php


namespace Stanford\LampStudyPortal;


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

    public function __construct() {
        parent::__construct();
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



}
