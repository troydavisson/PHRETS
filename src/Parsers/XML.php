<?php namespace PHRETS\Parsers;

use PHRETS\Http\Response;
use Psr\Http\Message\ResponseInterface;

class XML
{
    public function parse($string)
    {
        if ($string instanceof ResponseInterface or $string instanceof Response) {
            $string = $string->getBody()->__toString();
        }

        return new \SimpleXMLElement((string) $string);
    }
}
