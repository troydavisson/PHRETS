<?php

use PHRETS\Models\Bulletin;
use PHPUnit\Framework\TestCase;

class BulletinTest extends TestCase {

    /** @test **/
    public function it_holds()
    {
        $this->assertSame('Hello World', (new Bulletin)->setBody('Hello World')->getBody());
    }

    /** @test **/
    public function it_turns_itself_into_a_string()
    {
        $this->assertSame('Hello World', (string)(new Bulletin)->setBody('Hello World'));
    }

    public function testDetailsAreMadeAvailable()
    {
        $bulletin = new Bulletin(['Test' => 'Value']);
        $this->assertSame('Value', $bulletin->getDetail('Test'));
    }
}
