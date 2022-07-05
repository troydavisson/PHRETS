<?php

use PHRETS\Parsers\Login\OneFive;
use PHPUnit\Framework\TestCase;

class OneFiveTest extends TestCase {

    /** @var OneFive */
    protected $parser;

    public function setUp(): void
    {
        $this->parser = new OneFive;
        $this->parser->parse("
MemberName=UNKNOWN
User=unk,MASTER,4,1234567890
Broker=UNKNOWN
MetadataVersion=01.03.55606
MinMetadataVersion=01.03.55606
Login=/rets1_5/Login
Search=/rets1_5/Search
GetMetadata=/rets1_5/GetMetadata
X-SampleLinks=/rets1_5/Links
GetObject=/rets1_5/GetObject
Logout=/rets1_5/Logout
        ");
    }

    /** @test **/
    public function it_sees_all_transactions()
    {
        $this->assertSame(6, count($this->parser->getCapabilities()));
    }

    /** @test **/
    public function it_sees_core_transactions()
    {
        $this->assertSame('/rets1_5/Search', $this->parser->getCapabilities()['Search']);
        $this->assertSame('/rets1_5/Logout', $this->parser->getCapabilities()['Logout']);
    }

    /** @test **/
    public function it_sees_custom_transactions()
    {
        $this->assertSame('/rets1_5/Links', $this->parser->getCapabilities()['X-SampleLinks']);
    }

    /** @test **/
    public function it_sees_all_details()
    {
        $this->assertSame(5, count($this->parser->getDetails()));
    }

    /** @test **/
    public function it_sees_user_details()
    {
        $this->assertSame('unk,MASTER,4,1234567890', $this->parser->getDetails()['User']);
    }
}
