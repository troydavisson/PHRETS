<?php namespace PHRETS\Parsers\GetObject;

use GuzzleHttp\Message\ResponseInterface;
use PHRETS\Models\Object;
use PHRETS\Models\RETSError;

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

        if ($this->isError($response)) {
            $xml = $response->xml();
            $error = new RETSError;
            $error->setCode((string)\array_get($xml, 'ReplyCode'));
            $error->setMessage((string)\array_get($xml, 'ReplyText'));
            $obj->setError($error);
        }

        return $obj;
    }

    protected function isError(ResponseInterface $response)
    {
        if (\array_get($response->getHeaders(), 'RETS-Error', [null])[0] == 1) {
            return true;
        }

        $content_type = \array_get($response->getHeaders(), 'Content-Type', [null])[0];
        if ($content_type and strpos($content_type, 'xml') !== false) {
            return true;
        }

        return false;
    }
}
