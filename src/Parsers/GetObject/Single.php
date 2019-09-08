<?php namespace PHRETS\Parsers\GetObject;

use Illuminate\Support\Arr;
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
        $obj->setContentDescription(Arr::get($headers, 'Content-Description', [null])[0]);
        $obj->setContentSubDescription(Arr::get($headers, 'Content-Sub-Description', [null])[0]);
        $obj->setContentId(Arr::get($headers, 'Content-ID', [null])[0]);
        $obj->setObjectId(Arr::get($headers, 'Object-ID', [null])[0]);
        $obj->setContentType(Arr::get($headers, 'Content-Type', [null])[0]);
        $obj->setLocation(Arr::get($headers, 'Location', [null])[0]);
        $obj->setMimeVersion(Arr::get($headers, 'MIME-Version', [null])[0]);
        $obj->setPreferred(Arr::get($headers, 'Preferred', [null])[0]);

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

        $content_type = Arr::get($response->getHeaders(), 'Content-Type', [null])[0];
        if ($content_type and strpos($content_type, 'xml') !== false) {
            $xml = $response->xml();

            if (isset($xml['ReplyCode']) and $xml['ReplyCode'] != 0) {
                return true;
            }
        }

        return false;
    }
}
