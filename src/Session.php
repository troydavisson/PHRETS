<?php namespace PHRETS;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
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
    protected $rets_session_id;
    protected $cookie_jar;
    protected $last_request_url;
    /** @var \GuzzleHttp\Message\ResponseInterface */
    protected $last_response;

    function __construct(Configuration $configuration)
    {
        // save the configuration along with this session
        $this->configuration = $configuration;

        // start up our Guzzle HTTP client
        $this->client = PHRETSClient::make();

        $this->cookie_jar = new CookieJar;

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
                    'Accept-Encoding' => 'gzip',
                ]
        );

        // disable following 'Location' header (redirects) automatically
        if ($this->configuration->readOption('disable_follow_location')) {
            $this->client->setDefaultOption('allow_redirects', false);
        }

        // start up the Capabilities tracker and add Login as the first one
        $this->capabilities = new Capabilities;
        $this->capabilities->add('Login', $configuration->getLoginUrl());

        // start up the service locator
        $this->container = new Container;

        $default_parsers = [
            'login' => '\PHRETS\Parsers\Login\OneFive',
            'object.single' => '\PHRETS\Parsers\GetObject\Single',
            'object.multiple' => '\PHRETS\Parsers\GetObject\Multiple',
            'search' => '\PHRETS\Parsers\Search\OneX',
            'search.recursive' => '\PHRETS\Parsers\Search\RecursiveOneX',
            'metadata.system' => '\PHRETS\Parsers\GetMetadata\System',
            'metadata.resource' => '\PHRETS\Parsers\GetMetadata\Resource',
            'metadata.class' => '\PHRETS\Parsers\GetMetadata\ResourceClass',
            'metadata.table' => '\PHRETS\Parsers\GetMetadata\Table',
            'metadata.object' => '\PHRETS\Parsers\GetMetadata\Object',
            'metadata.lookuptype' => '\PHRETS\Parsers\GetMetadata\LookupType',
        ];

        foreach ($default_parsers as $k => $v) {
            $this->container->bind('parser.' . $k, function () use ($v) { return new $v; });
        }
    }

    /**
     * PSR-3 compatible logger can be attached here
     *
     * @param $logger
     */
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

        $parser = $this->container->make('parser.login');
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

    /**
     * @param $resource
     * @param $type
     * @param $content_ids
     * @param string $object_ids
     * @param int $location
     * @return Collection
     * @throws Exceptions\CapabilityUnavailable
     */
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
            $parser = $this->container->make('parser.object.multiple');
            $collection = $parser->parse($response);
        } else {
            $collection = new Collection;
            $parser = $this->container->make('parser.object.single');
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
        return $this->MakeMetadataRequest('METADATA-SYSTEM', 0, 'metadata.system');
    }

    /**
     * @param string $resource_id
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MetadataNotFound
     * @return Collection|\PHRETS\Models\Metadata\Resource
     */
    public function GetResourcesMetadata($resource_id = null)
    {
        $result = $this->MakeMetadataRequest('METADATA-RESOURCE', 0, 'metadata.resource');

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

    /**
     * @param $resource_id
     * @return mixed
     * @throws Exceptions\CapabilityUnavailable
     */
    public function GetClassesMetadata($resource_id)
    {
        return $this->MakeMetadataRequest('METADATA-CLASS', $resource_id, 'metadata.class');
    }

    /**
     * @param $resource_id
     * @param $class_id
     * @param string $keyed_by
     * @return mixed
     * @throws Exceptions\CapabilityUnavailable
     */
    public function GetTableMetadata($resource_id, $class_id, $keyed_by = 'SystemName')
    {
        return $this->MakeMetadataRequest('METADATA-TABLE', $resource_id . ':' . $class_id, 'metadata.table', $keyed_by);
    }

    /**
     * @param $resource_id
     * @return mixed
     * @throws Exceptions\CapabilityUnavailable
     */
    public function GetObjectMetadata($resource_id)
    {
        return $this->MakeMetadataRequest('METADATA-OBJECT', $resource_id, 'metadata.object');
    }

    /**
     * @param $resource_id
     * @param $lookup_name
     * @return mixed
     * @throws Exceptions\CapabilityUnavailable
     */
    public function GetLookupValues($resource_id, $lookup_name)
    {
        return $this->MakeMetadataRequest('METADATA-LOOKUP_TYPE', $resource_id . ':' . $lookup_name, 'metadata.lookuptype');
    }

    /**
     * @param $type
     * @param $id
     * @param $parser
     * @param null $keyed_by
     * @throws Exceptions\CapabilityUnavailable
     * @return mixed
     */
    protected function MakeMetadataRequest($type, $id, $parser, $keyed_by = null)
    {
        $response = $this->request(
            'GetMetadata',
            [
                'query' => [
                    'Type' => $type,
                    'ID' => $id,
                    'Format' => 'STANDARD-XML',
                ]
            ]
        );

        $parser = $this->container->make('parser.' . $parser);
        return $parser->parse($this, $response, $keyed_by);
    }

    /**
     * @param $resource_id
     * @param $class_id
     * @param $dmql_query
     * @param array $optional_parameters
     * @return \PHRETS\Models\Search\Results
     * @throws Exceptions\CapabilityUnavailable
     */
    public function Search($resource_id, $class_id, $dmql_query, $optional_parameters = [], $recursive = false)
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

        // if the Select parameter given is an array, format it as it needs to be
        if (array_key_exists('Select', $parameters) and is_array($parameters['Select'])) {
            $parameters['Select'] = implode(',', $parameters['Select']);
        }

        $response = $this->request(
            'Search',
            [
                'query' => $parameters
            ]
        );

        if ($recursive) {
            $parser = $this->container->make('parser.search.recursive');
        } else {
            $parser = $this->container->make('parser.search');
        }
        return $parser->parse($this, $response, $parameters);
    }

    /**
     * @return bool
     * @throws Exceptions\CapabilityUnavailable
     */
    public function Disconnect()
    {
        $response = $this->request('Logout');
        return true;
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

        // user-agent authentication
        if ($this->configuration->getUserAgentPassword()) {
            $ua_a1 = md5($this->configuration->getUserAgent() .':'. $this->configuration->getUserAgentPassword());
            $ua_dig_resp = md5(
                trim($ua_a1) .'::'. trim($this->rets_session_id) .
                ':'. trim($this->configuration->getRetsVersion()->asHeader())
            );
            $options = array_merge($options, ['headers' => ['RETS-UA-Authorization' => 'Digest ' . $ua_dig_resp]]);
        }

        $options = array_merge($options, ['cookies' => $this->cookie_jar]);

        $this->debug("Sending HTTP Request for {$url} ({$capability})", $options);

        if (array_key_exists('query', $options)) {
            $this->last_request_url = $url . '?' . \http_build_query($options['query']);
        } else {
            $this->last_request_url = $url;
        }

        /** @var \GuzzleHttp\Message\ResponseInterface $response */
        $response = $this->client->get($url, $options);
        $this->last_response = $response;

        $cookie = $response->getHeader('Set-Cookie');
        if ($cookie) {
            if (preg_match('/RETS-Session-ID\=(.*?)(\;|\s+|$)/', $cookie, $matches)) {
                $this->rets_session_id = $matches[1];
            }
        }

        $this->debug('Response: HTTP ' . $response->getStatusCode());
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

    /**
     * @param $message
     * @param array $context
     */
    public function debug($message, $context = [])
    {
        if ($this->logger) {
            if (!is_array($context)) {
                $context = [$context];
            }
            $this->logger->debug($message, $context);
        }
    }

    /**
     * @return CookieJarInterface
     */
    public function getCookieJar()
    {
        return $this->cookie_jar;
    }

    /**
     * @param CookieJarInterface $cookie_jar
     * @return $this
     */
    public function setCookieJar(CookieJarInterface $cookie_jar)
    {
        $this->cookie_jar = $cookie_jar;
        return $this;
    }

    /**
     * @return \GuzzleHttp\Event\EmitterInterface
     */
    public function getEventEmitter()
    {
        return $this->client->getEmitter();
    }

    /**
     * @return string
     */
    public function getLastRequestURL()
    {
        return $this->last_request_url;
    }

    /**
     * @return string
     */
    public function getLastResponse()
    {
        return (string)$this->last_response->getBody();
    }
}
