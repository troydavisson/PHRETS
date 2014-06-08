<?php namespace PHRETS;

use PHRETS\Exceptions\InvalidConfiguration;
use PHRETS\Versions\RETSVersion;

class Configuration
{
    protected $username;
    protected $password;
    protected $login_url;
    protected $user_agent;
    protected $user_agent_password;
    /** @var RETSVersion */
    protected $rets_version;
    protected $options = [];

    public function __construct()
    {
        $this->rets_version = (new RETSVersion)->setVersion('1.5');
    }

    /**
     * @return mixed
     */
    public function getLoginUrl()
    {
        return $this->login_url;
    }

    /**
     * @param mixed $login_url
     * @return $this
     */
    public function setLoginUrl($login_url)
    {
        $this->login_url = $login_url;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return RETSVersion
     */
    public function getRetsVersion()
    {
        return $this->rets_version;
    }

    /**
     * @param string $rets_version
     * @return $this
     */
    public function setRetsVersion($rets_version)
    {
        $this->rets_version = (new RETSVersion())->setVersion($rets_version);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * @param string $user_agent
     * @return $this
     */
    public function setUserAgent($user_agent)
    {
        $this->user_agent = $user_agent;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserAgentPassword()
    {
        return $this->user_agent_password;
    }

    /**
     * @param string $user_agent_password
     * @return $this
     */
    public function setUserAgentPassword($user_agent_password)
    {
        $this->user_agent_password = $user_agent_password;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * @param $name
     * @return null
     */
    public function readOption($name)
    {
        return (array_key_exists($name, $this->options)) ? $this->options[$name] : null;
    }

    /**
     * @param array $configuration
     * @return static
     * @throws Exceptions\InvalidConfiguration
     */
    public static function load($configuration = [])
    {
        $variables = [
                'username' => 'Username',
                'password' => 'Password',
                'login_url' => 'LoginUrl',
                'user_agent' => 'UserAgent',
                'user_agent_password' => 'UserAgentPassword',
                'rets_version' => 'RetsVersion',
        ];

        $me = new static;

        foreach ($variables as $k => $m) {
            if (array_key_exists($k, $configuration)) {
                $method = 'set' . $m;
                $me->$method($configuration[$k]);
            }
        }

        if (!$me->valid()) {
            throw new InvalidConfiguration("Login URL and Username must be provided");
        }

        return $me;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if (!$this->getLoginUrl() or !$this->getUsername()) {
            return false;
        }
        return true;
    }
}
