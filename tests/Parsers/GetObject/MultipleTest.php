<?php

use GuzzleHttp\Psr7\Response;
use PHRETS\Parsers\GetObject\Multiple;
use PHRETS\Http\Response as PHRETSResponse;
use PHPUnit\Framework\TestCase;

class MultipleTest extends TestCase
{

    /** @test * */
    public function it_breaks_things_apart()
    {
        $headers = [
                'Server' => [
                        0 => 'Apache-Coyote/1.1',
                ],
                'Cache-Control' => [
                        0 => 'private',
                ],
                'RETS-Version' => [
                        0 => 'RETS/1.5',
                ],
                'MIME-Version' => [
                        0 => '1.0',
                ],
                'Content-Type' => [
                        0 => 'multipart/parallel; boundary="FLEXeTNY6TFTGAwV1agjJsFyFogbnfoS1dm6y489g08F2TjwZWzQEW"',
                ],
                'Content-Length' => [
                        0 => '1249',
                ],
                'Date' => array(
                        0 => 'Mon, 09 Jun 2014 00:10:51 GMT',
                ),
        ];
        $body = json_decode(file_get_contents('tests/Fixtures/GetObject/Multiple1.txt', true));

        $parser = new Multiple;
        $collection = $parser->parse(new PHRETSResponse(new Response(200, $headers, $body)));

        $this->assertSame(5, $collection->count());

        /** @var PHRETS\Models\BaseObject $obj */
        $obj = $collection->first();
        $this->assertSame('Exterior Main View', $obj->getContentDescription());
        $this->assertSame('http://url1.jpg', $obj->getLocation());
    }

    /** @test **/
    public function it_handles_empty_bodies()
    {
        $parser = new Multiple;
        $collection = $parser->parse(new PHRETSResponse(new Response(200, [], null)));

        $this->assertInstanceOf('Illuminate\\Support\\Collection', $collection);
    }

    /** @test **/
    public function it_handles_unquoted_boundaries()
    {
        $headers = [
                'Server' => [
                        0 => 'Apache-Coyote/1.1',
                ],
                'Cache-Control' => [
                        0 => 'private',
                ],
                'RETS-Version' => [
                        0 => 'RETS/1.5',
                ],
                'MIME-Version' => [
                        0 => '1.0',
                ],
                'Content-Type' => [
                        0 => 'multipart/parallel; boundary=FLEXeTNY6TFTGAwV1agjJsFyFogbnfoS1dm6y489g08F2TjwZWzQEW',
                ],
                'Content-Length' => [
                        0 => '1249',
                ],
                'Date' => array(
                        0 => 'Mon, 09 Jun 2014 00:10:51 GMT',
                ),
        ];
        $body = json_decode(file_get_contents('tests/Fixtures/GetObject/Multiple1.txt', true));

        $parser = new Multiple;
        $collection = $parser->parse(new PHRETSResponse(new Response(200, $headers, $body)));

        $this->assertSame(5, $collection->count());

        /** @var PHRETS\Models\BaseObject $obj */
        $obj = $collection->first();
        $this->assertSame('Exterior Main View', $obj->getContentDescription());
        $this->assertSame('http://url1.jpg', $obj->getLocation());
    }
}
