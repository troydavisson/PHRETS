<?php

use PHRETS\Configuration;
use PHRETS\Strategies\StandardStrategy;

class StandardStrategyTest extends PHPUnit_Framework_TestCase
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
}
