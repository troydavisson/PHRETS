<?php namespace PHRETS\Parsers\GetMetadata;

use PHRETS\Http\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class BaseObject extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \PHRETS\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\PHRETS\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $collection = new Collection;

        if ($xml->METADATA) {
            if ($xml->METADATA->{'METADATA-OBJECT'}) {
                foreach ($xml->METADATA->{'METADATA-OBJECT'}->Object as $key => $value) {
                    $metadata = new \PHRETS\Models\Metadata\BaseObject;
                    $metadata->setSession($rets);
                    $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-OBJECT'});
                    $collection->put($obj->getObjectType(), $obj);
                }
            }
        }

        return $collection;
    }
}
