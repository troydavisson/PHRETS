<?php namespace PHRETS;

use GuzzleHttp\Client;
use Illuminate\Container\Container;
use PHRETS\Exceptions\MissingConfiguration;
use PHRETS\Http\Client as PHRETSClient;
use PHRETS\Parsers\Login\OneFive;

class Session
{
    /** @var Configuration */
    protected $configuration;
    /** @var Capabilities */
    protected $capabilities;
    /** @var Client */
    protected $client;
    /** @var Container */
    protected $container;

    function __construct(Configuration $configuration)
    {
        // save the configuration along with this session
        $this->configuration = $configuration;

        // start up our Guzzle HTTP client
        $this->client = PHRETSClient::make();

        // set the authentication as defaults to use for the entire client
        $this->client->setDefaultOption(
                'auth',
                [
                        $configuration->getUsername(),
                        $configuration->getPassword(),
                        'digest'
                ]
        );

        // start up the Capabilities tracker and add Login as the first one
        $this->capabilities = new Capabilities;
        $this->capabilities->add('Login', $configuration->getLoginUrl());

        // start up the service locator
        $this->container = new Container;
    }

    public function Login()
    {
        if (!$this->configuration or !$this->configuration->valid()) {
            throw new MissingConfiguration("Cannot issue Login without a valid configuration loaded");
        }

        $response = $this->request('Login');

        $parser = new OneFive;
        $parser->parse($response->{'RETS-RESPONSE'}->__toString());

        foreach ($parser->getCapabilities() as $k => $v) {
            $this->capabilities->add($k, $v);
        }
    }

    protected function request($capability, $options = [])
    {
        /** @var \GuzzleHttp\Message\ResponseInterface $response */
        $response = $this->client->get($this->capabilities->get($capability), $options);
        return $response->xml();
    }

    /**
     * @return Capabilities
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
