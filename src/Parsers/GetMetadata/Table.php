<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class Table extends Base
{
    public function parse(Session $rets, Response $response, $keyed_by)
    {
        $xml = simplexml_load_string($response->getBody());

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-TABLE'}->Field as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\Table;
                $metadata->setSession($rets);
                $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-TABLE'});
                $method = 'get' . $keyed_by;
                $collection->put((string)$metadata->$method(), $metadata);
            }
        }

        return $collection;
    }
}
