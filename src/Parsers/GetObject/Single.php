<?php namespace PHRETS\Parsers\GetObject;

use GuzzleHttp\Message\ResponseInterface;
use PHRETS\Models\Object;

class Single
{
    public function parse(ResponseInterface $response)
    {
        $headers = $response->getHeaders();

        $obj = new Object;
        $obj->setContent(($response->getBody()) ? $response->getBody()->__toString() : null);
        $obj->setContentDescription(\array_get($headers, 'Content-Description'));
        $obj->setContentSubDescription(\array_get($headers, 'Content-Sub-Description'));
        $obj->setContentId(\array_get($headers, 'Content-ID'));
        $obj->setObjectId(\array_get($headers, 'Object-ID'));
        $obj->setContentType(\array_get($headers, 'Content-Type'));
        $obj->setLocation(\array_get($headers, 'Location'));
        $obj->setMimeVersion(\array_get($headers, 'MIME-Version'));

        return $obj;
    }
}
