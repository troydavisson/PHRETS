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
        $obj->setContentDescription(\array_get($headers, 'Content-Description', [null])[0]);
        $obj->setContentSubDescription(\array_get($headers, 'Content-Sub-Description', [null])[0]);
        $obj->setContentId(\array_get($headers, 'Content-ID', [null])[0]);
        $obj->setObjectId(\array_get($headers, 'Object-ID', [null])[0]);
        $obj->setContentType(\array_get($headers, 'Content-Type', [null])[0]);
        $obj->setLocation(\array_get($headers, 'Location', [null])[0]);
        $obj->setMimeVersion(\array_get($headers, 'MIME-Version', [null])[0]);
        $obj->setPreferred(\array_get($headers, 'Preferred', [null])[0]);

        return $obj;
    }
}
