<?php

use PHRETS\Models\Search\Record;
use PHRETS\Models\Search\Results;

class ResultsTest extends PHPUnit_Framework_TestCase
{
    /** @var Results */
    protected $rs;

    public function setUp()
    {
        $this->rs = new Results;

        $rc = new Record;
        $rc->set('id', 1);
        $rc->set('name', 'left');
        $rc->set('value', 'up');
        $this->rs->addRecord($rc);

        $rc = new Record;
        $rc->set('id', 2);
        $rc->set('name', 'right');
        $rc->set('value', 'down');
        $this->rs->addRecord($rc);
    }

    /** @test * */
    public function it_holds_records()
    {
        $this->assertCount(2, $this->rs);
    }

    /** @test * */
    public function it_keys_records()
    {
        $this->rs->keyResultsBy('id');

        $this->assertSame('left', $this->rs->find(1)->get('name'));
        $this->assertSame('right', $this->rs->find(2)->get('name'));
        $this->assertNull($this->rs->find(3));
    }

    /** @test * */
    public function it_keys_records_with_closure()
    {
        $this->rs->keyResultsBy(
            function (Record $record) {
                return $record->get('id') . '_' . $record->get('name');
            }
        );

        $this->assertTrue(is_object($this->rs->find('1_left')));
        $this->assertSame('up', $this->rs->find('1_left')->get('value'));
    }

    /** @test **/
    public function it_traverses()
    {
        $found = false;
        foreach ($this->rs as $rs) {
            if ($rs->get('name') == 'right') {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    /** @test **/
    public function it_associates_metadata()
    {
        $metadata = ['test', 'fields'];
        $rs = new Results;
        $rs->setMetadata($metadata);

        $this->assertSame($metadata, $rs->getMetadata());
    }

    /** @test **/
    public function it_tracks_headers()
    {
        $fields = ['A', 'B', 'C', 'D', 'E'];
        $rs = new Results;
        $rs->setHeaders($fields);

        $this->assertSame($fields, $rs->getHeaders());
    }

    /** @test **/
    public function it_tracks_counts()
    {
        $rs = new Results;
        $rs->setTotalResultsCount(1000);
        $rs->setReturnedResultsCount(500);

        $this->assertSame(1000, $rs->getTotalResultsCount());
        $this->assertSame(500, $rs->getReturnedResultsCount());
    }

    /** @test **/
    public function it_tracks_resources_and_classes()
    {
        $rs = new Results;
        $rs->setResource('Property');
        $rs->setClass('A');

        $this->assertSame('Property', $rs->getResource());
        $this->assertSame('A', $rs->getClass());
    }

    /** @test **/
    public function it_allows_array_accessing_keyed_results()
    {
        $this->rs->keyResultsBy('id');

        $this->assertSame('left', $this->rs['1']->get('name'));
    }
}
