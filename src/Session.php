<?php namespace PHRETS;

use GuzzleHttp\Client;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use PHRETS\Exceptions\CapabilityUnavailable;
use PHRETS\Exceptions\MetadataNotFound;
use PHRETS\Exceptions\MissingConfiguration;
use PHRETS\Http\Client as PHRETSClient;
use PHRETS\Interpreters\GetObject;
use PHRETS\Models\Bulletin;

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

        $parser = new \PHRETS\Parsers\Login\OneFive;
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

    /**
     * @param $resource
     * @param $type
     * @param $content_id
     * @param int $location
     * @return \PHRETS\Models\Object
     */
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
            $parser = new \PHRETS\Parsers\GetObject\Multiple;
            $collection = $parser->parse($response);
        } else {
            $collection = new Collection;
            $parser = new \PHRETS\Parsers\GetObject\Single;
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

        $parser = new \PHRETS\Parsers\GetMetadata\System;
        return $parser->parse($this, $response);
    }

    /**
     * @param string $resource_id
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MetadataNotFound
     * @return Collection|\PHRETS\Models\Metadata\Resource
     */
    public function GetResourcesMetadata($resource_id = null)
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-RESOURCE',
                    'ID' => 0,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new \PHRETS\Parsers\GetMetadata\Resource;
        $result = $parser->parse($this, $response);

        if ($resource_id) {
            foreach ($result as $r) {
                if ($r->getResourceID() == $resource_id) {
                    return $r;
                }
            }
            throw new MetadataNotFound("Requested '{$resource_id}' resource metadata does not exist");
        }

        return $result;
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

        $parser = new \PHRETS\Parsers\GetMetadata\ResourceClass;
        return $parser->parse($this, $response);
    }

    public function GetTableMetadata($resource_id, $class_id, $keyed_by = 'SystemName')
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-TABLE',
                    'ID' => $resource_id . ':' . $class_id,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new \PHRETS\Parsers\GetMetadata\Table;
        return $parser->parse($this, $response, $keyed_by);
    }

    public function GetObjectMetadata($resource_id)
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-OBJECT',
                    'ID' => $resource_id,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new \PHRETS\Parsers\GetMetadata\Object;
        return $parser->parse($this, $response);
    }

    public function GetLookupValues($resource_id, $lookup_name)
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => 'METADATA-LOOKUP_TYPE',
                    'ID' => $resource_id . ':' . $lookup_name,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = new \PHRETS\Parsers\GetMetadata\LookupType;
        return $parser->parse($this, $response);
    }

    public function Search($resource_id, $class_id, $dmql_query, $optional_parameters = [])
    {
        $defaults = [
            'SearchType' => $resource_id,
            'Class' => $class_id,
            'Query' => $dmql_query,
            'QueryType' => 'DMQL2',
            'Count' => 1,
            'Format' => 'COMPACT-DECODED',
            'Limit' => 99999999,
            'StandardNames' => 0,
        ];

        $parameters = array_merge($defaults, $optional_parameters);

        $response = $this->request(
            'Search',
            [
                'query' => $parameters
            ]
        );

        $parser = new \PHRETS\Parsers\Search\OneX;
        return $parser->parse($this, $response, $resource_id, $class_id);
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
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->capabilities->get('Login');
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
