<?php namespace PHRETS\Parsers\GetObject;

use GuzzleHttp\Message\MessageParser;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\Stream;
use Illuminate\Support\Collection;
use PHRETS\Models\Object;

class Multiple
{
    public function parse(ResponseInterface $response)
    {
        $collection = new Collection;

        if (!$response->getBody()) {
            return $collection;
        }

        // help bad responses be more multipart compliant
        $body = "\r\n" . $response->getBody()->__toString() . "\r\n";

        // multipart
        preg_match('/boundary\=\"(.*?)\"/', $response->getHeader('Content-Type'), $matches);
        if (isset($matches[1])) {
            $boundary = $matches[1];
        } else {
            preg_match('/boundary\=(.*?)(\s|$|\;)/', $response->getHeader('Content-Type'), $matches);
            $boundary = $matches[1];
        }
        // strip quotes off of the boundary
        $boundary = preg_replace('/^\"(.*?)\"$/', '\1', $boundary);

        // clean up the body to remove a reamble and epilogue
        $body = preg_replace('/^(.*?)\r\n--' . $boundary . '\r\n/', "\r\n--{$boundary}\r\n", $body);
        // make the last one look like the rest for easier parsing
        $body = preg_replace('/\r\n--' . $boundary . '--/', "\r\n--{$boundary}\r\n", $body);

        // cut up the message
        $multi_parts = explode("\r\n--{$boundary}\r\n", $body);
        // take off anything that happens before the first boundary (the preamble)
        array_shift($multi_parts);
        // take off anything after the last boundary (the epilogue)
        array_pop($multi_parts);

        $message_parser = new MessageParser;
        $parser = new Single;

        // go through each part of the multipart message
        foreach ($multi_parts as $part) {
            // get Guzzle to parse this multipart section as if it's a whole HTTP message
            $parts = $message_parser->parseResponse("HTTP/1.1 200 OK\r\n" . $part);

            // now throw this single faked message through the Single GetObject response parser
            $single = new Response($parts['code'], $parts['headers'], Stream::factory($parts['body']));
            $obj = $parser->parse($single);

            // add information about this multipart to the returned collection
            $collection->push($obj);
        }

        return $collection;
    }
}
