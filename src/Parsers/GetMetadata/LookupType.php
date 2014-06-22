<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Collection;
use PHRETS\Session;

class LookupType extends Base
{
    public function parse(Session $rets, ResponseInterface $response)
    {
        $xml = $response->xml();

        $collection = new Collection;

        if ($xml->METADATA) {

            // some servers don't name this correctly for the version of RETS used, so play nice with either way
            if (!empty($xml->METADATA->{'METADATA-LOOKUP_TYPE'}->LookupType)) {
                $base = $xml->METADATA->{'METADATA-LOOKUP_TYPE'}->LookupType;
            } else {
                $base = $xml->METADATA->{'METADATA-LOOKUP_TYPE'}->Lookup;
            }

            foreach ($base as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\LookupType;
                $metadata->setSession($rets);
                $collection->push($this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-LOOKUP_TYPE'}));
            }
        }

        return $collection;
    }
}
