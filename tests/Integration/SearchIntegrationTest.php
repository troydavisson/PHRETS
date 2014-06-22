<?php

class SearchIntegrationTest extends BaseIntegration
{
    /** @test */
    public function it_makes_requests()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3]);
        $this->assertTrue($results instanceof \PHRETS\Models\Search\Results);
        $this->assertCount(3, $results);
    }

    /** @test **/
    public function it_parses_requests()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3]);

        $record = $results->first();

        $this->assertSame('20000426143505724628000000', $record->get('LIST_0'));
        $this->assertSame('04-171', $record->get('LIST_105'));

        $record = $results->last();

        $this->assertSame('20020327154323038709000000', $record->get('LIST_1'));
    }

    /** @test **/
    public function it_counts_records()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3]);

        $this->assertSame(3, $results->getReturnedResultsCount());
        $this->assertSame(9051, $results->getTotalResultsCount());
    }

    /** @test **/
    public function it_sees_maxrows_reached()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3]);

        $this->assertTrue($results->isMaxRowsReached());
    }
}
