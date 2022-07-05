<?php

use PHRETS\Models\BaseObject;
use PHPUnit\Framework\TestCase;

class ObjectTest extends TestCase {

    /** @test **/
    public function it_holds()
    {
        $o = new BaseObject;
        $o->setContent('Test Content');

        $this->assertSame('Test Content', $o->getContent());
    }

    /** @test **/
    public function it_returns_a_size()
    {
        $o = new BaseObject;
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

        $o = new BaseObject;
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
    public function it_marks_preferred_objects()
    {
        $o = new BaseObject;
        $this->assertFalse($o->isPreferred());
        $o->setPreferred(1);
        $this->assertTrue($o->isPreferred());
        $this->assertSame(1, $o->getPreferred());
    }

    /** @test **/
    public function it_marks_errors()
    {
        $e = new \PHRETS\Models\RETSError;
        $e->setCode(1234);
        $e->setMessage('Test Error Message');

        $o = new BaseObject;
        $this->assertFalse($o->isError());
        $o->setError($e);
        $this->assertTrue($o->isError());
        $this->assertSame(1234, $o->getError()->getCode());
        $this->assertSame('Test Error Message', $o->getError()->getMessage());
    }
}
