<?php

namespace Stanford\LampStudyPortal;

/**
 * Class Client
 * @package Stanford\LampStudyPortal
 * @property string $token
 * @property string $email
 * @property string $password;
 */
class Client extends \GuzzleHttp\Client
{

    private $token;

    private $email;

    private $password;

    public function __construct($email, $password, array $config = ['Content-Type' => 'application/json'])
    {

        parent::__construct($config);

        $this->setEmail($email);

        $this->setPassword($password);

        $this->generateBearerToken();

    }

    private function generateBearerToken()
    {
        $body = array(
            'email' => $this->getEmail(),
            'password' => $this->getPassword()
        );
        $response = $this->request('post', BASE_PATTERN_HEALTH_API_URL . 'auth/login',
            ['json' => $body,
                'debug' => true,
                'headers' => ['Content-Type' => 'application/json']
            ]
        );
        $code = $response->getStatusCode();
        if ($code == 200 || $code == 201) {
            $result = json_decode($response->getBody()->getContents());
            $this->setToken($result['access_token']);
        } else {
            throw new \Exception("cant make request to Authenticate User");
        }

    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    private function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    private function setPassword(string $password): void
    {
        $this->password = $password;
    }

}
