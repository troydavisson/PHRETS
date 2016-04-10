<?php namespace PHRETS\Http;

use Psr\Http\Message\ResponseInterface;

class Response
{
	protected $response = null;

	public function __construct(ResponseInterface $response)
	{
		$this->response = $response;
	}

	public function xml()
	{
		return new \SimpleXMLElement((string) $this->response->getBody());
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
