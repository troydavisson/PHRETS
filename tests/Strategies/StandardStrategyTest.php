<?php

use PHRETS\Configuration;
use PHRETS\Strategies\StandardStrategy;
use PHPUnit\Framework\TestCase;

class StandardStrategyTest extends TestCase
{
    /** @test **/
    public function it_provides_defaults()
    {
        $config = new Configuration;
        $strategy = new StandardStrategy;
        $strategy->initialize($config);

        $this->assertInstanceOf('\PHRETS\Parsers\Login\OneFive', $strategy->provide('parser.login'));
    }

    /** @test **/
    public function it_provides_a_1_8_login_parser()
    {
        $config = new Configuration;
        $config->setRetsVersion('1.8');
        $strategy = new StandardStrategy;
        $strategy->initialize($config);

        $this->assertInstanceOf('\PHRETS\Parsers\Login\OneEight', $strategy->provide('parser.login'));
    }

    /** @test **/
    public function it_provides_singletons()
    {
        $config = new Configuration;
        $strategy = new StandardStrategy;
        $strategy->initialize($config);

        $parser = $strategy->provide('parser.login');
        $another_parser = $strategy->provide('parser.login');

        $this->assertSame($parser, $another_parser);
    }

    /** @test **/
    public function it_uses_the_container()
    {
        $config = new Configuration;
        $strategy = new StandardStrategy;
        $strategy->initialize($config);

        $this->assertInstanceOf('\Illuminate\Container\Container', $strategy->getContainer());
        // get the default login parser
        $this->assertInstanceOf('\PHRETS\Parsers\Login\OneFive', $strategy->getContainer()->make('parser.login'));
    }
}
