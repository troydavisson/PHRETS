<?php

use PHRETS\Models\Search\Record;
use PHRETS\Models\Search\Results;
use PHPUnit\Framework\TestCase;

class ResultsTest extends TestCase
{
    /** @var Results */
    protected $rs;

    public function setUp(): void
    {
        $this->rs = new Results;

        $this->rs->setHeaders(['id', 'name', 'value']);

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
        $r = new Record;
        $r->set('id', 'extra');
        $r->set('name', 'test');

        $this->rs['extra'] = $r;

        $r = new Record;
        $r->set('id', 'bonus');
        $r->set('name', 'test');
        $this->rs[] = $r;

        $this->rs->keyResultsBy('id');

        $this->assertSame('left', $this->rs['1']->get('name'));
        $this->assertFalse(isset($this->rs['bogus_record']));
        unset($this->rs['1']);
        $this->assertFalse(isset($this->rs['1']));
        $this->assertTrue(isset($this->rs['extra']));
        $this->assertTrue(isset($this->rs['bonus']));
    }

    /** @test **/
    public function it_holds_errors()
    {
        $rs = new Results;
        $rs->setError('test');
        $this->assertSame('test', $rs->getError());
    }

    /** @test **/
    public function it_holds_a_session()
    {
        $rs = new Results;
        $rs->setSession('test');
        $this->assertSame('test', $rs->getSession());
    }

    /** @test **/
    public function it_gives_a_list()
    {
        $rs = new Results;

        $r = new Record;
        $r->set('id', 'extra');
        $r->set('name', 'test');
        $rs->addRecord($r);

        $r = new Record;
        $r->set('id', 'bonus');
        $r->set('name', 'test');
        $rs->addRecord($r);

        $r = new Record;
        $r->set('id', ''); // this is empty so it won't be included in the resulting list
        $r->set('name', 'another');
        $rs->addRecord($r);

        $this->assertSame(['extra', 'bonus'], $rs->lists('id'));
    }

    /** @test **/
    public function it_gives_a_list_excluding_restricted_values()
    {
        $rs = new Results;
        $rs->setRestrictedIndicator('****');

        $r = new Record;
        $r->set('id', 'extra');
        $r->set('name', 'test');
        $rs->addRecord($r);

        $r = new Record;
        $r->set('id', '****');
        $r->set('name', 'test');
        $rs->addRecord($r);

        $r = new Record;
        $r->set('id', 'bonus');
        $r->set('name', 'test');
        $rs->addRecord($r);

        $this->assertSame(['extra', 'bonus'], $rs->lists('id'));
    }

    /** @test **/
    public function it_converts_object_to_CSV()
    {
        $expected = "id,name,value\n1,left,up\n2,right,down\n";
        $this->assertSame($expected, $this->rs->toCSV());
    }

    /** @test **/
    public function it_converts_object_to_JSON()
    {
        $expected = '[{"id":1,"name":"left","value":"up"},{"id":2,"name":"right","value":"down"}]';
        $this->assertSame($expected, $this->rs->toJSON());
    }

    /** @test **/
    public function it_converts_object_to_array()
    {
        $expected = [
            ['id' => 1, 'name' => 'left', 'value' => 'up'],
            ['id' => 2, 'name' => 'right', 'value' => 'down'],
        ];
        $this->assertSame($expected, $this->rs->toArray());
    }
}
