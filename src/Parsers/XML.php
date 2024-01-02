<?php namespace PHRETS\Parsers;

use PHRETS\Http\Response;
use Psr\Http\Message\ResponseInterface;

class XML
{
    /**
     * Matches xml-encoded character references (decimal or hex) for ASCII control characters group.
     * Will match any string that begins with &# and ends with ; and in between is either
     * (0?[0-9]|[12][0-9]|3[01]) - a decimal integer between 00 and 31 (leading 0 optional)
     * ([xX](0?|1)[0-9A-Fa-f]) - a hex integer between 00 and 1F (upper or lower case, leading 0 optional)
     *
     * @link https://regexr.com/5c41j
     * @link https://en.wikipedia.org/wiki/ASCII#Control_characters
     * @link https://www.liquid-technologies.com/XML/CharRefs.aspx
     */
    const REGEX_ASCII_CONTROL_CHARACTERS = '/(&#((0?[0-9]|[12][0-9]|3[01])|([xX](0?|1)[0-9A-Fa-f]));)/';

    public function parse($string)
    {
        if ($string instanceof ResponseInterface or $string instanceof Response) {
            $string = $string->getBody()->__toString();
        }

        $string = (string)$string;

        $string = preg_replace(self::REGEX_ASCII_CONTROL_CHARACTERS, '', $string);

        return new \SimpleXMLElement($string);
    }
}
