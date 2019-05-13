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

        $string = (string)$string;

        if (!preg_match('/^<\?xml[^>]+?encoding=".+"\?>/', $string)) {
            if (
                mb_detect_encoding($string) !== 'UTF-8' ||
                !mb_check_encoding($string, 'UTF-8')
            ) {
                $string = utf8_encode($string);
            }
        }

        return new \SimpleXMLElement($string);
    }
}
