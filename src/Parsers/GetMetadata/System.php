<?php namespace PHRETS\Parsers\GetMetadata;

use GuzzleHttp\Message\ResponseInterface;
use PHRETS\Session;

class System extends Base
{
    public function parse(Session $rets, ResponseInterface $response)
    {
        $xml = $response->xml();
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
            if (isset($base->SYSTEM->attributes()->SystemID)) {
                $metadata->setSystemId((string)$base->SYSTEM->attributes()->SystemID);
            }
            if (isset($base->SYSTEM->attributes()->SystemDescription)) {
                $metadata->setSystemDescription((string)$base->SYSTEM->attributes()->SystemDescription);
            }
            if (isset($base->SYSTEM->attributes()->TimeZoneOffset)) {
                $metadata->setTimezoneOffset((string)$base->SYSTEM->attributes()->TimeZoneOffset);
            }
        }

        if (isset($base->SYSTEM->Comments)) {
            $metadata->setComments((string)$base->SYSTEM->Comments);
        }
        if (isset($base->attributes()->Version)) {
            $metadata->setVersion((string)$xml->METADATA->{'METADATA-SYSTEM'}->attributes()->Version);
        }

        return $metadata;
    }
}
