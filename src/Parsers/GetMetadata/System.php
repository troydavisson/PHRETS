<?php namespace PHRETS\Parsers\GetMetadata;

use PHRETS\Http\Response;
use PHRETS\Session;

class System extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \PHRETS\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\PHRETS\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $base = $xml->METADATA->{'METADATA-SYSTEM'};

        $metadata = new \PHRETS\Models\Metadata\System;
        $metadata->setSession($rets);

        $configuration = $rets->getConfiguration();

        if ($configuration->getRetsVersion()->is1_5()) {
            if (isset($base->System->SystemID)) {
                $metadata->setSystemId((string)$base->System->SystemID);
            }
            if (isset($base->System->SystemDescription)) {
                $metadata->setSystemDescription((string)$base->System->SystemDescription);
            }
        } else {
            if (isset($base->System->attributes()->SystemID)) {
                $metadata->setSystemId((string)$base->SYSTEM->attributes()->SystemID);
            }
            if (isset($base->System->attributes()->SystemDescription)) {
                $metadata->setSystemDescription((string)$base->System->attributes()->SystemDescription);
            }
            if (isset($base->System->attributes()->TimeZoneOffset)) {
                $metadata->setTimezoneOffset((string)$base->System->attributes()->TimeZoneOffset);
            }
        }

        if (isset($base->System->Comments)) {
            $metadata->setComments((string)$base->System->Comments);
        }
        if (isset($base->attributes()->Version)) {
            $metadata->setVersion((string)$xml->METADATA->{'METADATA-SYSTEM'}->attributes()->Version);
        }

        return $metadata;
    }
}
