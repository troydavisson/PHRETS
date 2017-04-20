<?php namespace PHRETS\Parsers\GetMetadata;

use PHRETS\Http\Response;
use Illuminate\Support\Collection;
use PHRETS\Session;

class ResourceClass extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \PHRETS\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\PHRETS\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-CLASS'}->Class as $key => $value) {
                $metadata = new \PHRETS\Models\Metadata\ResourceClass;
                $metadata->setSession($rets);
                /** @var \PHRETS\Models\Metadata\ResourceClass $obj */
                $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-CLASS'});
                $collection->put($obj->getClassName(), $obj);
            }
        }

        return $collection;
    }
}
