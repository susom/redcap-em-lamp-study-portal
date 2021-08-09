<?php

namespace Stanford\LampStudyPortal;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Client
 * @package Stanford\LampStudyPortal
 * @property string $token
 * @property string $email
 * @property string $password;
 * @property string $expiration;
 * @property string $group;
 * @property \Stanford\LampStudyPortal\LampStudyPortal $em
 * @property bool $saveToREDCap
 * @property array $REDCapData
 */
class Client extends \GuzzleHttp\Client
{

    private $token;

    private $email;

    private $password;

    private $expiration;

    private $group;

    private $em;

    private $saveToREDCap = false;

    private $REDCapData;

    public function __construct($em, $group, $email, $password, $token = '', $expiration = '', array $config = ['Content-Type' => 'application/json'])
    {

        parent::__construct($config);

        $this->setEm($em);

        $this->setGroup($group);

        $this->setEmail($email);

        $this->setPassword($password);


    }

    public function checkToken()
    {

        // still token not expired then save it to be used.
        if ($this->getExpiration() && $this->getToken() && strtotime($this->getExpiration()) > time()) {
            $this->setToken($this->getToken());
            $this->setExpiration($this->getExpiration());
        } elseif ($this->getEmail() && $this->getPassword()) {
            $this->generateBearerToken();
        } else {
            throw new \Exception("no config found");
        }
    }

    private function generateBearerToken()
    {
        $body = array(
            'email' => $this->getEmail(),
            'password' => $this->getPassword()
        );
        $result = $this->createRequest('post', FULL_PATTERN_HEALTH_API_URL . 'auth/login',
            ['json' => $body,
                #'debug' => true,
                'headers' => ['Content-Type' => 'application/json']
            ]
        );
        $this->setToken($result['access_token']);
        $this->setExpiration(date('Y-m-d H:i:s', time() + 30 * 60));
        $this->getEm()->updateTokenInfo();
    }

    public function createRequest($method, $uri = '', array $options = [], $refreshed = false)
    {
        try {
            // make it easy to make call without passing token
            if (empty($options)) {
                $options = [
                    'headers' => ['Authorization' => "Bearer " . $this->getToken()]
                ];
            }

            $response = parent::request($method, $uri, $options);

            $code = $response->getStatusCode();

            if ($code == 200 || $code == 201 || $code == 202) {
                $content = $response->getBody()->getContents();
                if (is_array(json_decode($content, true))) {
                    return json_decode($content, true);;
                }
                return $content;
            } else {
                // for regular request if failed try to generate new token and try again. otherwise throw exception.
                if (!$refreshed && empty($options)) {
                    $this->generateBearerToken();

                    return $this->createRequest($method, $uri, $options, true);
                }
                throw new \Exception("cant make request!");
            }
        } catch (ClientException $e) {
            // for regular request if failed try to generate new token and try again. otherwise throw exception.
            if (!$refreshed) {
                $this->generateBearerToken();

                return $this->createRequest($method, $uri, $options, true);
            } else {
                echo $e->getMessage();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (GuzzleException $e) {
            return $e->getMessage();
        }

    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
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
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    private function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * @param string $expiration
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return LampStudyPortal
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @param LampStudyPortal $em
     */
    public function setEm(LampStudyPortal $em)
    {
        $this->em = $em;
    }

    /**
     * @return bool
     */
    public function isSaveToREDCap()
    {
        return $this->saveToREDCap;
    }

    /**
     * @param bool $saveToREDCap
     */
    public function setSaveToREDCap($saveToREDCap)
    {
        $this->saveToREDCap = $saveToREDCap;
    }

    /**
     * @return array
     */
    public function getREDCapData()
    {
        return $this->REDCapData;
    }

    /**
     * @param array $REDCapData
     */
    public function setREDCapData($REDCapData)
    {
        $this->REDCapData = $REDCapData;
    }

    public static function generateUUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
