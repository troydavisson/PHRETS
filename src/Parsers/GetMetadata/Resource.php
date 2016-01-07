<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class Resource extends Base
{
    public function parse(Session $rets, Response $response)
    {
        $xml = simplexml_load_string($response->getBody());

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-RESOURCE'}->Resource as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\Resource;
                $metadata->setSession($rets);
                $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-RESOURCE'});
                $collection->put($obj->getResourceId(), $obj);
            }
        }

        return $collection;
    }
}
