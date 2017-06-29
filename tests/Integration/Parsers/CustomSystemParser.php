<?php

use PHRETS\Http\Response;
use PHRETS\Session;

class CustomSystemParser
{
    public function parse(Session $rets, Response $response)
    {
        $metadata = new \PHRETS\Models\Metadata\System;

        $metadata->setSession($rets);
        $metadata->setSystemId('custom');

        return $metadata;
    }
}
