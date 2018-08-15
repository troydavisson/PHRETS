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

        $string = (string) $string;

        /**
         * Some rets provider(s) return invalid XML data.
         * Here we make sure the returned data are UTF-8 valid chars.
         * I've seen so far:
         *  - windows carriage return (^M at the end of each lines)
         *  - trade mark sign not converted to &trade; and I guess we can have other sigh like this.
         *
         * NOTE:
         *  //IGNORE will discard the invalid UTF-8 chars.
         */
        $string = iconv(mb_detect_encoding($string), 'UTF-8//IGNORE', $string);

        return new \SimpleXMLElement($string);
    }
}
