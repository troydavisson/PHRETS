<?php

use GuzzleHttp\Psr7\Response;
use PHRETS\Http\Response as PHRETSResponse;
use PHRETS\Configuration;
use PHRETS\Parsers\Search\OneX;
use PHRETS\Session;
use PHPUnit\Framework\TestCase;

class OneXTest extends TestCase
{
    /** @var PHRETS\Models\Search\Results */
    protected $results;

    public function setUp(): void
    {
        $parser = new OneX;

        $parameters = [
            'SearchType' => 'Property',
            'Class' => 'A',
            'RestrictedIndicator' => '#####',
        ];

        $data = "
        <RETS ReplyCode=\"0\" ReplyText=\"Success\">
          <COUNT Records=\"9057\"/>
          <DELIMITER value=\"09\"/>
          <COLUMNS>	LIST_1	LIST_105	</COLUMNS>
          <DATA>	20111007152642181995000000	12-5	</DATA>
          <DATA>	20081003152306903177000000	07-310	</DATA>
          <DATA>	20081216155101459601000000	07-340	</DATA>
          <MAXROWS/>
        </RETS>
        ";

        $c = new Configuration;
        $c->setLoginUrl('http://www.reso.org/login');

        $s = new Session($c);
        $this->results = $parser->parse($s, new PHRETSResponse(new Response(200, [], $data)), $parameters);
    }

    /** @test **/
    public function it_sees_counts()
    {
        $this->assertSame(9057, $this->results->getTotalResultsCount());
    }

    /** @test **/
    public function it_sees_columns()
    {
        $this->assertSame(['LIST_1', 'LIST_105'], $this->results->getHeaders());
    }

    /** @test **/
    public function it_sees_the_first_record()
    {
        $this->assertSame('20111007152642181995000000', $this->results->first()['LIST_1']);
    }

    /** @test **/
    public function it_sees_maxrows()
    {
        $this->assertTrue($this->results->isMaxRowsReached());
    }
}
