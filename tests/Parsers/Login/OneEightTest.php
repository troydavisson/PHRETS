<?php

use PHRETS\Parsers\Login\OneEight;
use PHPUnit\Framework\TestCase;

class OneEightTest extends TestCase {

    /** @var OneEight */
    protected $parser;

    public function setUp(): void
    {
        $this->parser = new OneEight;
        $this->parser->parse("
Info=MEMBERNAME;Character;
Info=USERID;Character;1234567890
Info=USERLEVEL;Int;25
Info=USERCLASS;Character;RT
Info=AGENTCODE;Character;RESOWG
Info=BROKERCODE;Character;Test
Info=BROKERBRANCH;Character;Test01
Info=METADATAID;Character;12_34_56_78_ABCD_EFG
Info=METADATAVERSION;Character;37.86.72100
Info=METADATATIMESTAMP;DateTime;2014-06-30T18:41:40Z
Info=MINMETADATATIMESTAMP;DateTime;2014-06-30T18:41:40Z
Info=BOARD;Character;
Info=BROKERRECIPFLAG;Boolean;0
Info=MAINOFF;Character;test test
Info=OFFICE;Character;Test00
Info=SUL;Int;11
Info=UC;Character;RT
Info=USER;Character;RESOWG
ChangePassword=/ChangePassword.asmx/ChangePassword
GetObject=/GetObject.asmx/GetObject
Login=/Login.asmx/Login
Logout=/Logout.asmx/Logout
Search=/Search.asmx/Search
GetMetadata=/GetMetadata.asmx/GetMetadata
GetPayloadList=/GetPayloadList.asmx/GetPayloadList
        ");
    }

    /** @test **/
    public function it_sees_all_transactions()
    {
        $this->assertSame(7, count($this->parser->getCapabilities()));
    }

    /** @test **/
    public function it_sees_core_transactions()
    {
        $this->assertSame('/Search.asmx/Search', $this->parser->getCapabilities()['Search']);
        $this->assertSame('/Logout.asmx/Logout', $this->parser->getCapabilities()['Logout']);
    }

    /** @test **/
    public function it_sees_all_details()
    {
        $this->assertSame(18, count($this->parser->getDetails()));
    }

    /** @test **/
    public function it_sees_user_details()
    {
        $this->assertSame('RESOWG', $this->parser->getDetails()['USER']);
    }

    /** @test **/
    public function it_casts_details()
    {
        $this->assertIsBool($this->parser->getDetails()['BROKERRECIPFLAG']);
        $this->assertIsInt($this->parser->getDetails()['SUL']);
    }
}
