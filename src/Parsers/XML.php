<?php namespace PHRETS\Parsers;

use PHRETS\Http\Response;
use Psr\Http\Message\ResponseInterface;

class XML
{
    const REGEX_ASCII_CONTROL_CHARACTERS = '/(&#[xX](0?[0-9]|[12][0-9]|3[01]);)/'; // '&#xN;' where 0 <= N < 32

    public function parse($string)
    {
        if ($string instanceof ResponseInterface or $string instanceof Response) {
            $string = $string->getBody()->__toString();
        }

        $string = (string)$string;

        if (!preg_match('/^<\?xml[^>]+?encoding=".+"\?>/', $string)) {
            if (
                mb_detect_encoding($string) !== 'UTF-8' ||
                !mb_check_encoding($string, 'UTF-8')
            ) {
                $string = utf8_encode($string);
            }
        }

        $string = preg_replace(self::REGEX_ASCII_CONTROL_CHARACTERS, '', $string);

        return new \SimpleXMLElement($string);
    }
}
