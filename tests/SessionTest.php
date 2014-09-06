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

    /** @test **/
    public function it_makes_the_event_emitter_available()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);

        $this->assertInstanceOf('GuzzleHttp\Event\EmitterInterface', $s->getEventEmitter());
    }

    /** @test **/
    public function it_binds_from_a_strategy()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');
        $c->setRetsVersion('1.8');

        $s = new Session($c);

        $this->assertTrue($s->getContainer()->bound('parser.login'));
        $this->assertInstanceOf('\PHRETS\Parsers\Login\OneEight', $s->getContainer()->make('parser.login'));
    }

    /** @test **/
    public function it_disables_redirects_when_desired()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');
        $c->setOption('disable_follow_location', true);

        $s = new Session($c);

        $this->assertFalse($s->getClient()->getDefaultOption('allow_redirects'));
    }

    /** @test **/
    public function it_uses_the_set_logger()
    {
        $logger = $this->getMock('Logger', ['debug']);
        // just expect that a debug message is spit out
        $logger->expects($this->atLeastOnce())->method('debug')->with($this->matchesRegularExpression('/logger/'));

        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $s->setLogger($logger);
    }
}
