<?php

use PHRETS\Configuration;
use PHRETS\Http\Client;
use PHRETS\Session;

class SessionTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_builds()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $this->assertSame($c, $s->getConfiguration());
    }

    /** @test **/
    public function it_detects_invalid_configurations()
    {
        $this->setExpectedException(
            'PHRETS\\Exceptions\\MissingConfiguration',
            "Cannot issue Login without a valid configuration loaded"
        );
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $s->Login();
    }

    /** @test **/
    public function it_gives_back_the_login_url()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);

        $this->assertSame('http://www.reso.org/login', $s->getLoginUrl());
    }
}
