<?php

use GuzzleHttp\Psr7\Response;
use PHRETS\Http\Response as PHRETSResponse;
use PHRETS\Parsers\GetObject\Single;
use PHPUnit\Framework\TestCase;

class SingleTest extends TestCase {

    /** @test **/
    public function it_understands_the_basics()
    {
        $parser = new Single;
        $single = new PHRETSResponse(new Response(200, ['Content-Type' => 'text/plain'], 'Test'));
        $obj = $parser->parse($single);

        $this->assertSame('Test', $obj->getContent());
        $this->assertSame('text/plain', $obj->getContentType());
    }

    /** @test **/
    public function it_detects_and_handles_errors()
    {
        $error = '<RETS ReplyCode="20203" ReplyText="RETS Server: Some error">
        Valid Classes are: A B C E F G H I
        </RETS>';
        $parser = new Single;
        $single = new PHRETSResponse(new Response(200, ['Content-Type' => 'text/xml'], $error));
        $obj = $parser->parse($single);

        $this->assertTrue($obj->isError());
        $this->assertSame(20203, $obj->getError()->getCode());
        $this->assertSame('RETS Server: Some error', $obj->getError()->getMessage());
    }

    /** @test **/
    public function it_sees_the_new_rets_error_header()
    {
        $error = '<RETS ReplyCode="20203" ReplyText="RETS Server: Some error">
        Valid Classes are: A B C E F G H I
        </RETS>';
        $parser = new Single;
        $single = new PHRETSResponse(new Response(200, ['Content-Type' => 'text/plain', 'RETS-Error' => '1'], $error));
        $obj = $parser->parse($single);

        $this->assertTrue($obj->isError());
    }
}
