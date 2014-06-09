<?php

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use PHRETS\Parsers\GetObject\Single;

class SingleTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_understands_the_basics()
    {
        $parser = new Single;
        $single = new Response(200, ['Content-Type' => 'text/plain'], Stream::factory('Test'));
        $obj = $parser->parse($single);

        $this->assertSame('Test', $obj->getContent());
        $this->assertSame('text/plain', $obj->getContentType());
    }
}
