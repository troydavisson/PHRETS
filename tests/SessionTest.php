<?php

use PHRETS\Configuration;
use PHRETS\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase {

    /** @test **/
    public function it_builds()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $this->assertSame($c, $s->getConfiguration());
    }

    /**
     * @test
     */
    public function it_detects_invalid_configurations()
    {
        $this->expectException(\PHRETS\Exceptions\MissingConfiguration::class);
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
    public function it_disables_redirects_when_desired()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');
        $c->setOption('disable_follow_location', true);

        $s = new Session($c);

        $this->assertFalse($s->getDefaultOptions()['allow_redirects']);
    }

    /** @test **/
    public function it_uses_the_set_logger()
    {
        $logger = $this->createMock(\Monolog\Logger::class);

        // expect that the string 'Context' will be changed into an array
        $logger->expects($this->atLeastOnce())->method('debug')->withConsecutive(
            [$this->anything()],
            [$this->equalTo('Message'), $this->equalTo(['Context'])]
        );

        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $s->setLogger($logger);

        $s->debug('Message', 'Context');
    }

    /** @test **/
    public function it_fixes_the_logger_context_automatically()
    {
        $logger = $this->createMock(\Monolog\Logger::class);
        // just expect that a debug message is spit out
        $logger->expects($this->atLeastOnce())->method('debug')->with($this->matchesRegularExpression('/logger/'));

        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $s->setLogger($logger);
    }

    /** @test **/
    public function it_loads_a_cookie_jar()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $this->assertInstanceOf('\GuzzleHttp\Cookie\CookieJarInterface', $s->getCookieJar());
    }

    /** @test **/
    public function it_allows_overriding_the_cookie_jar()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);

        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $s->setCookieJar($jar);

        $this->assertSame($jar, $s->getCookieJar());
    }
}
