<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHRETS\Configuration;
use PHRETS\Session;

class SessionTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        // put back a default client so mocked handlers don't leak into other tests
        \PHRETS\Http\Client::set(new GuzzleHttp\Client);
    }

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
     * @expectedException \PHRETS\Exceptions\MissingConfiguration
     */
    public function it_detects_invalid_configurations()
    {
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

    /** @test **/
    public function it_sends_requests_with_the_cookie_jar()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);

        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $s->setCookieJar($jar);

        $options = $s->getDefaultOptions();
        $this->assertSame($jar, $options['cookies']);
        $this->assertArrayNotHasKey('curl', $options);
    }

    /** @test **/
    public function it_retries_login_when_the_401_sets_a_cookie()
    {
        $history = [];
        $this->mockResponses([
            new Response(401, ['Set-Cookie' => 'JSESSIONID=abc123; Path=/']),
            new Response(200, ['Content-Type' => 'text/xml'], $this->loginResponseBody()),
        ], $history);

        $s = new Session($this->loginConfiguration());
        $s->Login();

        $this->assertCount(2, $history);
        $this->assertSame('', $history[0]['request']->getHeaderLine('Cookie'));
        $this->assertSame('JSESSIONID=abc123', $history[1]['request']->getHeaderLine('Cookie'));
    }

    /**
     * @test
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function it_doesnt_retry_login_when_the_401_sets_no_cookies()
    {
        $history = [];
        $this->mockResponses([
            new Response(401),
            new Response(200, ['Content-Type' => 'text/xml'], $this->loginResponseBody()),
        ], $history);

        $s = new Session($this->loginConfiguration());
        $s->Login();
    }

    /**
     * @test
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function it_only_retries_login_once()
    {
        $history = [];
        $this->mockResponses([
            new Response(401, ['Set-Cookie' => 'JSESSIONID=abc123; Path=/']),
            new Response(401, ['Set-Cookie' => 'JSESSIONID=def456; Path=/']),
            new Response(200, ['Content-Type' => 'text/xml'], $this->loginResponseBody()),
        ], $history);

        $s = new Session($this->loginConfiguration());
        $s->Login();
    }

    protected function loginConfiguration()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login')
            ->setUsername('user')
            ->setPassword('pass');

        return $c;
    }

    protected function mockResponses(array $responses, array &$history)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        \PHRETS\Http\Client::set(new GuzzleHttp\Client(['handler' => $stack]));
    }

    protected function loginResponseBody()
    {
        return '<RETS ReplyCode="0" ReplyText="Success"><RETS-RESPONSE>' . "\n" .
            'MemberName=UNKNOWN' . "\n" .
            'Login=/rets2_1/Login' . "\n" .
            'Search=/rets2_1/Search' . "\n" .
            '</RETS-RESPONSE></RETS>';
    }
}
