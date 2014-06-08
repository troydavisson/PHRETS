<?php

class ClientTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_makes()
    {
        $this->assertInstanceOf('GuzzleHttp\\Client', \PHRETS\Http\Client::make());
    }

    /** @test **/
    public function it_allows_overrides()
    {
        $gc = new GuzzleHttp\Client;
        \PHRETS\Http\Client::set($gc);

        $this->assertSame($gc, \PHRETS\Http\Client::make());
    }
}
