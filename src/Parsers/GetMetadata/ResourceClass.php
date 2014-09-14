<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Collection;
use PHRETS\Session;

class ResourceClass extends Base
{
    public function parse(Session $rets, ResponseInterface $response)
    {
        $xml = $response->xml();

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
