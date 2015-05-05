<?php namespace PHRETS;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use Illuminate\Support\Collection;
use PHRETS\Exceptions\CapabilityUnavailable;
use PHRETS\Exceptions\MetadataNotFound;
use PHRETS\Exceptions\MissingConfiguration;
use PHRETS\Exceptions\RETSException;
use PHRETS\Http\Client as PHRETSClient;
use PHRETS\Interpreters\GetObject;
use PHRETS\Interpreters\Search;
use PHRETS\Models\Bulletin;

class Session
{
    /** @var Configuration */
    protected $configuration;
    /** @var Capabilities */
    protected $capabilities;
    /** @var Client */
    protected $client;
    /** @var \PSR\Log\LoggerInterface */
    protected $logger;
    protected $rets_session_id;
    protected $cookie_jar;
    protected $last_request_url;
    /** @var \GuzzleHttp\Message\ResponseInterface */
    protected $last_response;

    public function __construct(Configuration $configuration)
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
                        $configuration->getHttpAuthenticationMethod()
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
    }

    /**
     * PSR-3 compatible logger can be attached here
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        $this->debug("Loading " . get_class($logger) . " logger");
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

        $parser = $this->grab('parser.login');
        $parser->parse($response->xml()->{'RETS-RESPONSE'}->__toString());

        foreach ($parser->getCapabilities() as $k => $v) {
            $this->capabilities->add($k, $v);
        }

        $bulletin = new Bulletin;
        if ($this->capabilities->get('Action')) {
            $response = $this->request('Action');
            $bulletin->setBody($response->getBody()->__toString());
            return $bulletin;
        } else {
            return $bulletin;
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
            $parser = $this->grab('parser.object.multiple');
            $collection = $parser->parse($response);
        } else {
            $collection = new Collection;
            $parser = $this->grab('parser.object.single');
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
     * @return \Illuminate\Support\Collection|\PHRETS\Models\Metadata\Table[]
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

        $parser = $this->grab('parser.' . $parser);
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
        $dmql_query = Search::dmql($dmql_query);

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
            $parser = $this->grab('parser.search.recursive');
        } else {
            $parser = $this->grab('parser.search');
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
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\RETSException
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    protected function request($capability, $options = [])
    {
        $url = $this->capabilities->get($capability);

        if (!$url) {
            throw new CapabilityUnavailable("'{$capability}' tried but no valid endpoint was found.  Did you forget to Login()?");
        }

        if (!array_key_exists('headers', $options)) {
            $options['headers'] = [];
        }

        // Guzzle 5 changed the order that headers are added to the request, so we'll do this manually
        $options['headers'] = array_merge($this->client->getDefaultOption('headers'), $options['headers']);

        // user-agent authentication
        if ($this->configuration->getUserAgentPassword()) {
            $ua_digest = $this->configuration->userAgentDigestHash($this);
            $options['headers'] = array_merge($options['headers'], ['RETS-UA-Authorization' => 'Digest ' . $ua_digest]);
        }

        $options = array_merge($options, ['cookies' => $this->cookie_jar]);

        $this->debug("Sending HTTP Request for {$url} ({$capability})", $options);

        if (array_key_exists('query', $options)) {
            $this->last_request_url = $url . '?' . \http_build_query($options['query']);
        } else {
            $this->last_request_url = $url;
        }

        /** @var \GuzzleHttp\Message\ResponseInterface $response */
        if ($this->configuration->readOption('use_post_method')) {
            $this->debug('Using POST method per use_post_method option');
            $query = (array_key_exists('query', $options)) ? $options['query'] : null;
            $response = $this->client->post($url, array_merge($options, ['body' => $query]));
        } else {
            $response = $this->client->get($url, $options);
        }

        $this->last_response = $response;

        $cookie = $response->getHeader('Set-Cookie');
        if ($cookie) {
            if (preg_match('/RETS-Session-ID\=(.*?)(\;|\s+|$)/', $cookie, $matches)) {
                $this->rets_session_id = $matches[1];
            }
        }

        if ($response->getHeader('Content-Type') == 'text/xml' and $capability != 'GetObject') {
            $xml = $response->xml();
            if ($xml and isset($xml['ReplyCode'])) {
                $rc = (string)$xml['ReplyCode'];
                // 20201 - No records found - not exception worthy in my mind
                if ($rc != "0" and $rc != "20201") {
                    throw new RETSException($xml['ReplyText'], (int)$xml['ReplyCode']);
                }
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

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getRetsSessionId()
    {
        return $this->rets_session_id;
    }

    /**
     * @param $component
     * @return mixed
     */
    protected function grab($component)
    {
        return $this->configuration->getStrategy()->provide($component);
    }
}
