<?php namespace PHRETS\Parsers\GetObject;

use PHRETS\Http\Response;
use PHRETS\Models\BaseObject;
use PHRETS\Models\RETSError;

class Single
{
    public function parse(Response $response)
    {
        $headers = $response->getHeaders();

        $obj = new BaseObject;
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
            
            if (isset($xml['ReplyCode'])) {
                $error->setCode((string) $xml['ReplyCode']);
            }
            if (isset($xml['ReplyText'])) {
                $error->setMessage((string) $xml['ReplyText']);
            }
            
            $obj->setError($error);
        }

        return $obj;
    }

    protected function isError(Response $response)
    {
        if ($response->getHeader('RETS-Error') == 1) {
            return true;
        }

        $content_type = \array_get($response->getHeaders(), 'Content-Type', [null])[0];
        if ($content_type and strpos($content_type, 'xml') !== false) {
            $xml = $response->xml();

            if (isset($xml['ReplyCode']) and $xml['ReplyCode'] != 0) {
                return true;
            }
        }

        return false;
    }
}
