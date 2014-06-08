<?php

use PHRETS\Capabilities;

class CapabilitiesTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_tracks()
    {
        $cpb = new Capabilities;
        $cpb->add('login', 'http://www.reso.org/login');

        $this->assertNotNull($cpb->get('login'));
        $this->assertNull($cpb->get('test'));
    }
}
