<?php namespace PHRETS;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use PHRETS\Exceptions\CapabilityUnavailable;
use PHRETS\Exceptions\MetadataNotFound;
use PHRETS\Exceptions\MissingConfiguration;
use PHRETS\Exceptions\RETSException;
use PHRETS\Http\Client as PHRETSClient;
use PHRETS\Interpreters\GetObject;
use PHRETS\Interpreters\Search;
use PHRETS\Models\BaseObject;
use PHRETS\Models\Bulletin;
use PHRETS\Strategies\Strategy;
use Psr\Http\Message\ResponseInterface;

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
    /** @var ResponseInterface */
    protected $last_response;

    public function __construct(Configuration $configuration)
    {
        // save the configuration along with this session
        $this->configuration = $configuration;

        $defaults = [];

        // start up our Guzzle HTTP client
        $this->client = PHRETSClient::make($defaults);

        $this->cookie_jar = new CookieJar;

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

        $parser = $this->grab(Strategy::PARSER_LOGIN);
        $xml = new \SimpleXMLElement((string)$response->getBody());
        $parser->parse($xml->{'RETS-RESPONSE'}->__toString());

        foreach ($parser->getCapabilities() as $k => $v) {
            $this->capabilities->add($k, $v);
        }

        $bulletin = new Bulletin($parser->getDetails());
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
     * @return \PHRETS\Models\BaseObject
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
     * @return Collection|BaseObject[]
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

        if (stripos($response->getHeader('Content-Type'), 'multipart') !== false) {
            $parser = $this->grab(Strategy::PARSER_OBJECT_MULTIPLE);
            $collection = $parser->parse($response);
        } else {
            $collection = new Collection;
            $parser = $this->grab(Strategy::PARSER_OBJECT_SINGLE);
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
                /** @var \PHRETS\Models\Metadata\Resource $r */
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
            $parser = $this->grab(Strategy::PARSER_SEARCH_RECURSIVE);
        } else {
            $parser = $this->grab(Strategy::PARSER_SEARCH);
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
     * @param bool $is_retry
     * @return ResponseInterface
     * @throws CapabilityUnavailable
     * @throws RETSException
     */
    protected function request($capability, $options = [], $is_retry = false)
    {
        $url = $this->capabilities->get($capability);

        if (!$url) {
            throw new CapabilityUnavailable("'{$capability}' tried but no valid endpoint was found.  Did you forget to Login()?");
        }

        $options = array_merge($this->getDefaultOptions(), $options);

        // user-agent authentication
        if ($this->configuration->getUserAgentPassword()) {
            $ua_digest = $this->configuration->userAgentDigestHash($this);
            $options['headers'] = array_merge($options['headers'], ['RETS-UA-Authorization' => 'Digest ' . $ua_digest]);
        }

        $this->debug("Sending HTTP Request for {$url} ({$capability})", $options);

        if (array_key_exists('query', $options)) {
            $this->last_request_url = $url . '?' . \http_build_query($options['query']);
        } else {
            $this->last_request_url = $url;
        }

        try {
            /** @var ResponseInterface $response */
            if ($this->configuration->readOption('use_post_method')) {
                $this->debug('Using POST method per use_post_method option');
                $query = (array_key_exists('query', $options)) ? $options['query'] : null;
                $response = $this->client->request('POST', $url, array_merge($options, ['form_params' => $query]));
            } else {
                $response = $this->client->request('GET', $url, $options);
            }
        } catch (ClientException $e) {
            $this->debug("ClientException: " . $e->getCode() . ": " . $e->getMessage());

            if ($e->getCode() != 401) {
                // not an Unauthorized error, so bail
                throw $e;
            }

            if ($capability == 'Login') {
                // unauthorized on a Login request, so bail
                throw $e;
            }

            if ($is_retry) {
                // this attempt was already a retry, so let's stop here
                $this->debug("Request retry failed.  Won't retry again");
                throw $e;
            }

            if ($this->getConfiguration()->readOption('disable_auto_retry')) {
                // this attempt was already a retry, so let's stop here
                $this->debug("Re-logging in disabled.  Won't retry");
                throw $e;
            }

            $this->debug("401 Unauthorized exception returned");
            $this->debug("Logging in again and retrying request");
            // see if logging in again and retrying the request works
            $this->Login();

            return $this->request($capability, $options, true);
        }

        $response = new \PHRETS\Http\Response($response);

        $this->last_response = $response;

        if ($response->getHeader('Set-Cookie')) {
            $cookie = $response->getHeader('Set-Cookie');
            if ($cookie) {
                if (preg_match('/RETS-Session-ID\=(.*?)(\;|\s+|$)/', $cookie, $matches)) {
                    $this->rets_session_id = $matches[1];
                }
            }
        }

        $this->debug('Response: HTTP ' . $response->getStatusCode());

        if (stripos($response->getHeader('Content-Type'), 'text/xml') !== false and $capability != 'GetObject') {
            $parser = $this->grab(Strategy::PARSER_XML);
            $xml = $parser->parse($response);

            if ($xml and isset($xml['ReplyCode'])) {
                $rc = (string)$xml['ReplyCode'];

                if ($rc == "20037" and $capability != 'Login') {
                    // must make Login request again.  let's handle this automatically

                    if ($this->getConfiguration()->readOption('disable_auto_retry')) {
                        // this attempt was already a retry, so let's stop here
                        $this->debug("Re-logging in disabled.  Won't retry");
                        throw new RETSException($xml['ReplyText'], (int)$xml['ReplyCode']);
                    }

                    if ($is_retry) {
                        // this attempt was already a retry, so let's stop here
                        $this->debug("Request retry failed.  Won't retry again");
                        // let this error fall through to the more generic handling below
                    } else {
                        $this->debug("RETS 20037 re-auth requested");
                        $this->debug("Logging in again and retrying request");
                        // see if logging in again and retrying the request works
                        $this->Login();

                        return $this->request($capability, $options, true);
                    }
                }

                // 20201 - No records found - not exception worthy in my mind
                // 20403 - No objects found - not exception worthy in my mind
                if (!in_array($rc, [0, 20201, 20403])) {
                    throw new RETSException($xml['ReplyText'], (int)$xml['ReplyCode']);
                }
            }
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

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        $defaults = [
            'auth' => [
                $this->configuration->getUsername(),
                $this->configuration->getPassword(),
                $this->configuration->getHttpAuthenticationMethod()
            ],
            'headers' => [
                'User-Agent' => $this->configuration->getUserAgent(),
                'RETS-Version' => $this->configuration->getRetsVersion()->asHeader(),
                'Accept-Encoding' => 'gzip',
                'Accept' => '*/*',
            ],
            'curl' => [ CURLOPT_COOKIEFILE => tempnam('/tmp', 'phrets') ]
        ];

        // disable following 'Location' header (redirects) automatically
        if ($this->configuration->readOption('disable_follow_location')) {
            $defaults['allow_redirects'] = false;
        }

        return $defaults;
    }

    public function setParser($parser_name, $parser_object)
    {
        /** @var Container $container */
        $container = $this->getConfiguration()->getStrategy()->getContainer();
        $container->instance($parser_name, $parser_object);
    }
}
