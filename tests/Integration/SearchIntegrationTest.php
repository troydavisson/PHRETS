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

    /** @test **/
    public function it_provides_access_to_associated_metadata()
    {
        /** @var PHRETS\Models\Search\Results $results */
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3, 'Select' => ['LIST_1', 'LIST_105']]);
        $this->assertInstanceOf('\Illuminate\Support\Collection', $results->getMetadata());
        $this->assertInstanceOf('\PHRETS\Models\Metadata\Table', $results->getMetadata()->first());
    }

    /** @test **/
    public function it_recursively_retrieves_all_results()
    {
        $this->session->Login();

        $results = $this->session->Search(
            'Property',
            'A',
            '(LIST_22=90000000+)',
            ['Limit' => '15', 'Select' => 'LIST_1'],
            true
        );

        $this->assertCount(40, $results);
    }

    /** @test **/
    public function it_recovers_from_missing_delimiter()
    {
        $this->session->Login();

        // this is manually faked in the fixture
        $results = $this->session->Search(
            'Property',
            'BROKENDELIMITER',
            '(LIST_22=90000000+)',
            ['Limit' => '15', 'Select' => 'LIST_1']
        );

        $this->assertCount(1, $results->getHeaders());
    }

    /** @test **/
    public function it_doesnt_die_when_no_count_is_given()
    {
        $this->session->Login();

        // this is manually faked in the fixtures
        $results = $this->session->Search(
            'Property',
            'NOCOUNT',
            '(LIST_22=90000000+)',
            ['Limit' => '15', 'Select' => 'LIST_1'],
            true
        );

        $this->assertCount(40, $results);
    }

    /**
     * @test
     * **/
    public function it_detects_broken_pagination()
    {
        $this->expectException(\PHRETS\Exceptions\AutomaticPaginationError::class);
        $this->session->Login();

        // this is manually faked in the fixture
        $this->session->Search(
            'Property',
            'BROKENPAGINATION',
            '(LIST_22=90000000+)',
            ['Limit' => '15', 'Select' => 'LIST_1'],
            true
        );
    }
}
