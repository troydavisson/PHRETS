<?php namespace PHRETS;

use GuzzleHttp\Client;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use PHRETS\Exceptions\CapabilityUnavailable;
use PHRETS\Exceptions\MissingConfiguration;
use PHRETS\Http\Client as PHRETSClient;
use PHRETS\Interpreters\GetObject;
use PHRETS\Models\Bulletin;
use PHRETS\Models\Object;
use PHRETS\Parsers\GetMetadata\Resource;
use PHRETS\Parsers\GetMetadata\ResourceClass;
use PHRETS\Parsers\GetMetadata\System;
use PHRETS\Parsers\GetObject\Multiple;
use PHRETS\Parsers\GetObject\Single;
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
    /** @var \PSR\Log\LoggerInterface */
    protected $logger;

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
        $this->client->setDefaultOption(
                'headers',
                [
                    'User-Agent' => $configuration->getUserAgent(),
                    'RETS-Version' => $configuration->getRetsVersion()->asHeader(),
                ]
        );

        // start up the Capabilities tracker and add Login as the first one
        $this->capabilities = new Capabilities;
        $this->capabilities->add('Login', $configuration->getLoginUrl());

        // start up the service locator
        $this->container = new Container;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MissingConfiguration
     * @returns Bulletin
     */
    public function Login()
    {
        if (!$this->configuration or !$this->configuration->valid()) {
            throw new MissingConfiguration("Cannot issue Login without a valid configuration loaded");
        }

        $response = $this->request('Login');

        $parser = new OneFive;
        $parser->parse($response->xml()->{'RETS-RESPONSE'}->__toString());

        foreach ($parser->getCapabilities() as $k => $v) {
            $this->capabilities->add($k, $v);
        }

        if ($this->capabilities->get('Action')) {
            $response = $this->request('Action');
            $bulletin = new Bulletin;
            $bulletin->setBody($response->getBody()->getContents());
            return $bulletin;
        } else {
            return new Bulletin;
        }
    }

    public function GetPreferredObject($resource, $type, $content_id, $location = 0)
    {
        $collection = $this->GetObject($resource, $type, $content_id, '0', $location);
        return $collection->first();
    }

    public function GetObject($resource, $type, $content_ids, $object_ids = '*', $location = 0)
    {
        $request_id = GetObject::ids($content_ids, $object_ids);

        $response = $this->request(
            'GetObject',
            [
                'query' => [
                    'Resource' => $resource,
                    'Type' => $type,
                    'ID' => implode(',', $request_id),
                    'Location' => $location,
                ]
            ]
        );

        if (preg_match('/multipart/', $response->getHeader('Content-Type'))) {
            $parser = new Multiple;
            $collection = $parser->parse($response);
        } else {
            $collection = new Collection;
            $parser = new Single;
            $object = $parser->parse($response);
            $collection->push($object);
        }

        return $collection;
    }

    /**
     * @return Models\Metadata\System
     * @throws Exceptions\CapabilityUnavailable
     */
    public function GetSystemMetadata()
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-SYSTEM',
                    'ID' => 0,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new System;
        return $parser->parse($this, $response);
    }

    public function GetResourcesMetadata($id = 0)
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-RESOURCE',
                    'ID' => $id,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new Resource;
        return $parser->parse($this, $response);
    }

    public function GetClassesMetadata($resource_id)
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-CLASS',
                    'ID' => $resource_id,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new ResourceClass;
        return $parser->parse($this, $response);
    }

    /**
     * @param $capability
     * @param array $options
     * @return \GuzzleHttp\Message\ResponseInterface
     * @throws Exceptions\CapabilityUnavailable
     */
    protected function request($capability, $options = [])
    {
        $url = $this->capabilities->get($capability);

        if (!$url) {
            throw new CapabilityUnavailable("'{$capability}' tried but no valid endpoint was found.  Did you forget to Login()?");
        }

        if ($this->logger) {
            $this->logger->debug("Sending HTTP Request for {$url} ({$capability})", $options);
        }
        /** @var \GuzzleHttp\Message\ResponseInterface $response */
        $response = $this->client->get($url, $options);

        if ($this->logger) {
            $this->logger->debug('Response: HTTP ' . $response->getStatusCode());
        }
        return $response;
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
