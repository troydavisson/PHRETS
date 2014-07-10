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

    /** @test **/
    public function it_uses_the_container()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);

        $container = $s->getContainer();
        $this->assertInstanceOf('Illuminate\Container\Container', $container);
    }

    /** @test **/
    public function it_registers_default_parsers()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);

        $container = $s->getContainer();
        $parser = $container->make('parser.login');
        $this->assertInstanceOf('PHRETS\Parsers\Login\OneFive', $parser);
    }

    /** @test **/
    public function it_tracks_capabilities()
    {
        $login_url = 'http://www.reso.org/login';
        $c = new Configuration;
        $c->setLoginUrl($login_url);

        $s = new Session($c);
        $capabilities = $s->getCapabilities();
        $this->assertInstanceOf('PHRETS\Capabilities', $capabilities);
        $this->assertSame($login_url, $capabilities->get('Login'));
    }
}
