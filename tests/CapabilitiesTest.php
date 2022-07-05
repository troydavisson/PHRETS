<?php

use PHRETS\Capabilities;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase {

    /** @test **/
    public function it_tracks()
    {
        $cpb = new Capabilities;
        $cpb->add('login', 'http://www.reso.org/login');

        $this->assertNotNull($cpb->get('login'));
        $this->assertNull($cpb->get('test'));
    }

    /**
     * @test
     * **/
    public function it_barfs_when_not_given_enough_information_to_build_absolute_urls()
    {
        $this->expectException(InvalidArgumentException::class);
        $cpb = new Capabilities;
        $cpb->add('Login', '/rets/Login');
    }

    /** @test **/
    public function it_can_build_absolute_urls_from_relative_ones()
    {
        $cpb = new Capabilities;
        $cpb->add('Login', 'http://www.google.com/login');

        $cpb->add('Search', '/search');
        $this->assertSame('http://www.google.com:80/search', $cpb->get('Search'));
    }

    /** @test **/
    public function it_preserves_explicity_ports()
    {
        $cpb = new Capabilities;
        $cpb->add('Login', 'http://www.google.com:8080/login');

        $cpb->add('Search', '/search');
        $this->assertSame('http://www.google.com:8080/search', $cpb->get('Search'));
    }
}
