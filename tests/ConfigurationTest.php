<?php

use PHRETS\Configuration;

class ConfigurationTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_does_the_basics()
    {
        $config = new Configuration;
        $config->setLoginUrl('http://www.reso.org/login'); // not a valid RETS server.  just using for testing
        $config->setUsername('user');
        $config->setPassword('pass');

        $this->assertSame('http://www.reso.org/login', $config->getLoginUrl());
        $this->assertSame('user', $config->getUsername());
        $this->assertSame('pass', $config->getPassword());
    }

    /** @test **/
    public function it_loads_config_from_array()
    {
        $config = Configuration::load([
            'login_url' => 'http://www.reso.org/login',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertSame('http://www.reso.org/login', $config->getLoginUrl());
        $this->assertSame('user', $config->getUsername());
        $this->assertSame('pass', $config->getPassword());
    }

    /** @test **/
    public function it_complains_about_bad_config()
    {
        $this->setExpectedException('PHRETS\\Exceptions\\InvalidConfiguration', "Login URL and Username must be provided");
        $config = Configuration::load();
    }

    /** @test **/
    public function it_loads_default_rets_version()
    {
        $config = new Configuration;

        $this->assertInstanceOf('PHRETS\\Versions\\RETSVersion', $config->getRetsVersion());
        $this->assertTrue($config->getRetsVersion()->is1_5());
    }

    /** @test **/
    public function it_handles_versions_correctly()
    {
        $config = new Configuration;
        $config->setRetsVersion('1.7.2');
        $this->assertInstanceOf('PHRETS\\Versions\\RETSVersion', $config->getRetsVersion());
    }

    /** @test **/
    public function it_handles_user_agents()
    {
        $config = new Configuration;
        $config->setUserAgent('PHRETS/2.0');
        $this->assertSame('PHRETS/2.0', $config->getUserAgent());
    }

    /** @test **/
    public function it_handles_ua_passwords()
    {
        $config = new Configuration;
        $config->setUserAgent('PHRETS/2.0');
        $config->setUserAgentPassword('test12345');
        $this->assertSame('PHRETS/2.0', $config->getUserAgent());
        $this->assertSame('test12345', $config->getUserAgentPassword());
    }

    /** @test **/
    public function it_tracks_options()
    {
        $config = new Configuration;
        $config->setOption('param', true);
        $this->assertTrue($config->readOption('param'));
    }

    /** @test **/
    public function it_loads_a_strategy()
    {
        $config = new Configuration;
        $this->assertInstanceOf('PHRETS\Strategies\Strategy', $config->getStrategy());
        $this->assertInstanceOf('PHRETS\Strategies\StandardStrategy', $config->getStrategy());
    }

    /** @test **/
    public function it_loads_the_default_login_parser_for_1_5()
    {
        $config = new Configuration;
        $this->assertSame('\PHRETS\Parsers\Login\OneFive', $config->getStrategy()->getBindings()['parser.login']);
    }

    /** @test **/
    public function it_adjusts_the_login_parser_with_1_8()
    {
        $config = new Configuration;
        $config->setRetsVersion('1.8');

        $this->assertSame('\PHRETS\Parsers\Login\OneEight', $config->getStrategy()->getBindings()['parser.login']);
    }

    /** @test **/
    public function it_allows_overriding_the_strategy()
    {
        $config = new Configuration;
        $strategy = new \PHRETS\Strategies\StandardStrategy($config);
        $config->setStrategy($strategy);

        $this->assertSame($strategy, $config->getStrategy());
    }

    /** @test **/
    public function it_provides_access_to_the_strategys_container()
    {
        $config = new Configuration;
        $config->setLoginUrl('http://www.reso.org/login');
        $strategy = new \PHRETS\Strategies\StandardStrategy($config);
        $config->setStrategy($strategy);

        $s = new \PHRETS\Session($config);

        $this->assertInstanceOf('\Illuminate\Container\Container', $config->getStrategy()->getContainer());
    }

    /** @test **/
    public function it_generates_user_agent_auth_hashes_correctly()
    {
        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login')
            ->setUserAgent('PHRETS/2.0')
            ->setUserAgentPassword('12345')
            ->setRetsVersion('1.7.2');

        $s = new \PHRETS\Session($c);
        $this->assertSame('123c96e02e514da469db6bc61ab998dc', $c->userAgentDigestHash($s));
    }
}
