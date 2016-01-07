<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class Object extends Base
{
    public function parse(Session $rets, Response $response)
    {
        $xml = simplexml_load_string($response->getBody());

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-OBJECT'}->Object as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\Object;
                $metadata->setSession($rets);
                $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-OBJECT'});
                $collection->put($obj->getObjectType(), $obj);
            }
        }

        return $collection;
    }
}
