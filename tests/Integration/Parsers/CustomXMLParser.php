<?php

use PHRETS\Http\Response;
use Psr\Http\Message\ResponseInterface;

class CustomXMLParser
{
    public function parse($string)
    {
        if ($string instanceof ResponseInterface or $string instanceof Response) {
            $string = $string->getBody()->__toString();
        }

        $string = str_replace('LIST_1', 'LIST_10000', $string);
        return new \SimpleXMLElement((string) $string);
    }
}
