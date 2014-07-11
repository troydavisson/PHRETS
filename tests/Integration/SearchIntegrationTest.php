<?php

class SearchIntegrationTest extends BaseIntegration
{
    /** @test */
    public function it_makes_requests()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Select' => $this->search_select, 'Limit' => 3]);
        $this->assertTrue($results instanceof \PHRETS\Models\Search\Results);
        $this->assertCount(3, $results);
    }

    /** @test **/
    public function it_parses_requests()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Select' => $this->search_select, 'Limit' => 3]);

        $record = $results->first();

        $this->assertSame('20000426143505724628000000', $record->get('LIST_0'));

        $record = $results->last();

        $this->assertTrue(preg_match('/000000$/', $record->get('LIST_1')) === 1);
    }

    /** @test **/
    public function it_counts_records()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Select' => $this->search_select, 'Limit' => 3]);

        $this->assertSame(3, $results->getReturnedResultsCount());
        $this->assertSame(9057, $results->getTotalResultsCount());
    }

    /** @test **/
    public function it_sees_maxrows_reached()
    {
        $results = $this->session->Search('Property', 'A', '*', ['Select' => $this->search_select, 'Limit' => 3]);

        $this->assertTrue($results->isMaxRowsReached());
    }

    /** @test **/
    public function it_limits_fields()
    {
        /** @var PHRETS\Models\Search\Results $results */
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3, 'Select' => 'LIST_1,LIST_105']);
        $this->assertContains('LIST_1', $results->getHeaders());
        $this->assertCount(2, $results->getHeaders());
        $this->assertNotContains('LIST_22', $results->getHeaders());
    }

    /** @test **/
    public function it_limits_fields_with_an_array()
    {
        /** @var PHRETS\Models\Search\Results $results */
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3, 'Select' => ['LIST_1', 'LIST_105']]);
        $this->assertContains('LIST_1', $results->getHeaders());
        $this->assertCount(2, $results->getHeaders());
        $this->assertNotContains('LIST_22', $results->getHeaders());
    }
}
