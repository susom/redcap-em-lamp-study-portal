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

    /**@var Client $client */
    private $client;

    /**
     * Media constructor.
     * @param $client
     * @param $title
     * @param $api_endpoint
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __construct($client, $title, $api_endpoint)
    {
        $this->setTitle($title);
        $this->setClient($client);
        $this->setApiEndpoint($api_endpoint);
        $this->setBinary($this->getClient()->createRequest('GET', BASE_PATTERN_HEALTH_API_URL . ltrim($this->getApiEndpoint(), '/')));
        // Other code to run when object is instantiated
    }

    /**
     * @param $content
     * @return string
     */
    public function getMimeType($content)
    {
        $imgdata = base64_decode($content);

        $f = finfo_open();

        $mime_type = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);
        return $mime_type;
    }

    /**
     * @param $record
     * @param $field
     * @param $event
     * @param $api_token
     * @throws \Exception
     */
    public function uploadImage($record, $field, $event, $api_token)
    {
        $is_pdf = strpos($this->getTitle(), '.pdf');
        if($is_pdf)
            file_put_contents('/tmp/' . $this->getTitle(), $this->getBinary());
        else
            file_put_contents('/tmp/' . $this->getTitle() . '.jpeg', $this->getBinary());

        $file_name = $is_pdf ? $this->getTitle() : $this->getTitle() . '.jpeg';
        $this->writeFileToApi(
            array(
                'tmp_name' => '/tmp/' . $file_name,
                'type' => $is_pdf ? 'application/pdf': 'image/jpeg',
                'name' => $file_name
            ),
            $record, $field, $event, $api_token);
    }


    /**
     * @param $file
     * @param $record
     * @param $field
     * @param $event
     * @param $api_token
     * @return bool
     * @throws \Exception
     */
    public function writeFileToApi($file, $record, $field, $event, $api_token)
    {
        // Prepare upload file
        $curlFile = curl_file_create($file["tmp_name"], $file["type"], $file["name"]);
        $data = array(
            'token' => $api_token,
            'content' => 'file',
            'action' => 'import',
            'record' => $record,
            'field' => $field,
            'event' => $event,
            'file' => $curlFile,
            'returnFormat' => 'json'
        );
        $ch = curl_init(APP_PATH_WEBROOT_FULL . 'api/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 105200);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);
        $this->getClient()->getEm()->emDebug($result);
        $info = curl_getinfo($ch);
        $this->getClient()->getEm()->emDebug($info);
        curl_close($ch);
        if ($info['http_code'] != 200) {
            throw new \Exception(
                "<br>Error uploading $field to $record" .
                "<br>Upload Request Info:<pre>" .
                print_r($info, true) .
                "</pre>" . "<br>Upload Request:<pre>" .
                print_r($result, true) . "</pre>");
        }
        return true;
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
