<?php

use PHRETS\Interpreters\Search;

class SearchTest extends PHPUnit_Framework_TestCase
{
    /** @test **/
    public function it_doesnt_touch_properly_formatted_dmql()
    {
        $this->assertSame('(FIELD=VALUE)', Search::dmql('(FIELD=VALUE)'));
    }
    
    /** @test **/
    public function it_wraps_simplified_dmql_in_parens()
    {
        $this->assertSame('(FIELD=VALUE)', Search::dmql('FIELD=VALUE'));
    }

    /** @test **/
    public function it_doesnt_modify_when_special_characters_are_used()
    {
        $this->assertSame('*', Search::dmql('*'));
        $this->assertSame('', Search::dmql(''));
    }
}
