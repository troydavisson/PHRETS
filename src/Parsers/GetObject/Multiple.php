<?php namespace PHRETS\Parsers\GetObject;

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

        // go through each part of the multipart message
        foreach ($multi_parts as $part) {
            // default to processing headers
            $on_headers = true;
            $on_body = false;
            $first_body_found = false;
            $body = "";
            $headers = [];

            // go through the multipart chunk line-by-line
            $body_parts = explode("\r\n", $part);
            foreach ($body_parts as $line) {
                if (empty($line) && $on_headers == true) {
                    // blank line.  switching to processing a body and moving on
                    $on_headers = false;
                    $on_body = true;
                    continue;
                }
                if ($on_headers == true) {
                    // non blank line and we're processing headers so save the header
                    $header = null;
                    $value = null;

                    if (strpos($line, ':') !== false) {
                        @list($header, $value) = explode(':', $line, 2);
                    }

                    $header = trim($header);
                    $value = trim($value);
                    if (!empty($header)) {
                        if ($header == "Description") {
                            // for servers where the implementors didn't read the next word in the RETS spec.
                            // 'Description' is the BNF term. Content-Description is the correct header.
                            // fixing for sanity
                            $header = "Content-Description";
                        }
                        // fix case issue if exists
                        if ($header == "Content-type") {
                            $header = "Content-Type";
                        }

                        $headers[$header] = $value;
                    }
                }
                if ($on_body == true) {
                    if ($first_body_found == true) {
                        // here again because a linebreak in the body section which was cut out in the explode
                        // add the CRLF back
                        $body .= "\r\n";
                    }
                    // non blank line and we're processing a body so save the line as part of Data
                    $first_body_found = true;
                    $body .= $line;
                }
            }
            // done with parsing out the multipart response

            $stream = Stream::factory($body);
            $parser = new Single;
            $single = new Response(200, $headers, $stream);
            $obj = $parser->parse($single);

            // add information about this multipart to the returned collection
            $collection->push($obj);
        }

        return $collection;
    }
}
