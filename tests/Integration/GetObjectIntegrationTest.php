<?php

class GetObjectIntegrationTest extends BaseIntegration
{
    /** @test */
    public function it_fetches_objects()
    {
        $this->play('GetObject/it_fetches_objects');
        $this->session->Login();

        $objects = $this->session->GetObject('Property', 'Photo', '14-52', '*', 1);
        $this->assertTrue($objects instanceof \Illuminate\Support\Collection);
        $this->assertSame(5, $objects->count());
    }

    /** @test */
    public function it_fetches_primary_object()
    {
        $this->play('GetObject/it_fetches_primary_object');
        $this->session->Login();

        $objects = $this->session->GetObject('Property', 'Photo', '00-1669', 0, 1);
        $this->assertTrue($objects instanceof \Illuminate\Support\Collection);
        $this->assertSame(1, $objects->count());

        $primary = $objects->first();

        $object = $this->session->GetPreferredObject('Property', 'Photo', '00-1669', 1);
        $this->assertTrue($object instanceof \PHRETS\Models\Object);
        $this->assertEquals($primary, $object);
    }

    /** @test **/
    public function it_sees_primary_as_preferred()
    {
        $this->play('GetObject/it_sees_primary_as_preferred');
        $this->session->Login();

        $object = $this->session->GetPreferredObject('Property', 'Photo', '00-1669', 1);
        $this->assertTrue($object->isPreferred());
    }

    /** @test */
    public function it_sees_locations_despite_xml_being_returned()
    {
        $this->play('GetObject/it_sees_locations_despite_xml_being_returned');
        $this->session->Login();

        $object = $this->session->GetObject('Property', 'Photo', 'URLS-WITH-XML', '*', 1);

        $this->assertCount(1, $object);
        /** @var \PHRETS\Models\Object $first */
        $first = $object->first();
        $this->assertFalse($first->isError());
        $this->assertSame('http://someurl', $first->getLocation());
    }
}
