<?php

namespace Stanford\LampStudyPortal;

/**
 * Class Client
 * @package Stanford\LampStudyPortal
 * @property string $token
 * @property string $email
 * @property string $password;
 * @property string $expiration;
 * @property string $group;
 */
class Client extends \GuzzleHttp\Client
{

    private $token;

    private $email;

    private $password;

    private $expiration;

    private $group;

    public function __construct($group, $email, $password, $token = '', $expiration = '', array $config = ['Content-Type' => 'application/json'])
    {

        parent::__construct($config);

        $this->setGroup($group);

        $this->setEmail($email);

        $this->setPassword($password);

        // still token not expired then save it to be used.
        if ($expiration && $token && strtotime($expiration) > time()) {
            $this->setToken($token);
            $this->setExpiration($expiration);
        } elseif ($email && $password) {
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
        $result = $this->request('post', BASE_PATTERN_HEALTH_API_URL . 'auth/login',
            ['json' => $body,
                #'debug' => true,
                'headers' => ['Content-Type' => 'application/json']
            ]
        );
        $this->setToken($result['access_token']);
        $this->setExpiration(date('Y-m-d H:i:s', time() + 12 * 3600));

    }

    public function request($method, $uri = '', array $options = [])
    {

        // make it easy to make call without passing token
        if (empty($options)) {
            $options = ['headers' =>
                [
                    'Authorization' => "Bearer " . $this->getToken()
                ]
            ];
        }

        $response = parent::request($method, $uri, $options);

        $code = $response->getStatusCode();

        if ($code == 200 || $code == 201) {
            return json_decode($response->getBody()->getContents(), true);
        } else {
            throw new \Exception("cant make request!");
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


}
