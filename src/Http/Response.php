<?php namespace PHRETS\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 * @package PHRETS\Http
 *
 * @method ResponseInterface|StreamInterface getBody
 * @method array getHeaders
 */
class Response
{
	protected $response = null;

	public function __construct(ResponseInterface $response)
	{
		$this->response = $response;
	}

	public function xml()
	{
	    // remove any carriage return / newline in XML response
        $data = (string) $this->response->getBody();
        $data = trim(preg_replace('/[\r\n]+/','', $data));

        try {
            $r = new \SimpleXMLElement($data);
        } catch (\Exception $e) {
            throw new \Exception("Could not create SimpleXMLElement from {$data}");
        }

        return $r;
	}

	public function __call($method, $args = [])
	{
		return call_user_func_array([$this->response, $method], $args);
	}

	public function getHeader($name)
	{
		$headers = $this->response->getHeader($name);
		
		if ($headers) {
			return implode('; ', $headers);
		} else {
			return null;
		}
	}
}
