<?php

use PHRETS\Models\Object;

class ObjectTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_holds()
    {
        $o = new Object;
        $o->setContent('Test Content');

        $this->assertSame('Test Content', $o->getContent());
    }

    /** @test **/
    public function it_returns_a_size()
    {
        $o = new Object;
        $o->setContent('Hello');

        $this->assertSame(5, $o->getSize());
    }

    /** @test **/
    public function it_makes_from_headers()
    {
        $headers = [
            'Content-Type' => 'image/jpeg',
            'Content-ID' => '12345678',
            'Object-ID' => '1',
            'Location' => 'http://blah',
            'Content-Description' => 'Main description',
            'Content-Sub-Description' => 'Sub description',
            'MIME-Version' => 'Mime Version',
        ];

        $o = new Object;
        foreach ($headers as $k => $v) {
            $o->setFromHeader($k, $v);
        }

        $this->assertSame('image/jpeg', $o->getContentType());
        $this->assertSame('12345678', $o->getContentId());
        $this->assertSame('1', $o->getObjectId());
        $this->assertSame('http://blah', $o->getLocation());
        $this->assertSame('Main description', $o->getContentDescription());
        $this->assertSame('Sub description', $o->getContentSubDescription());
        $this->assertSame('Mime Version', $o->getMimeVersion());
    }

    /** @test **/
    public function it_fetches_from_remote()
    {
        $o = new Object;
        $o->setLocation('http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css');

        $this->assertRegExp('/Twitter/', $o->downloadFromURL()->getContent());
    }
}
