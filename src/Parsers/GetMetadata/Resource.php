<?php namespace PHRETS\Parsers\GetMetadata;

use PHRETS\Http\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class Resource extends Base
{
    public function parse(Session $rets, Response $response)
    {
        $xml = $response->xml();

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-RESOURCE'}->Resource as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\Resource;
                $metadata->setSession($rets);
                /** @var \PHRETS\Models\Metadata\Resource $obj */
                $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-RESOURCE'});
                $collection->put($obj->getResourceID(), $obj);
            }
        }

        return $collection;
    }
}
