<?php

use PHRETS\Configuration;
use PHRETS\Session;

class SessionTest extends PHPUnit_Framework_TestCase {
    
    /** @test **/
    public function it_builds()
    {
        $c = new Configuration;
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
        $s = new Session(new Configuration);
        $s->Login();
    }
}
