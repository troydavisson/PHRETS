<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class ResourceClass extends Base
{
    public function parse(Session $rets, Response $response)
    {
        $xml = simplexml_load_string($response->getBody());

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-CLASS'}->Class as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\ResourceClass;
                $metadata->setSession($rets);
                $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-CLASS'});
                $collection->put($obj->getClassName(), $obj);
            }
        }

        return $collection;
    }
}
