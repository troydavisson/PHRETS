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
}
