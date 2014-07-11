<?php

class GetObjectIntegrationTest extends BaseIntegration
{
    /** @test */
    public function it_fetches_objects()
    {
        $objects = $this->session->GetObject('Property', 'Photo', '14-52', '*', 0);
        $this->assertTrue($objects instanceof \Illuminate\Support\Collection);
        $this->assertSame(22, $objects->count());
    }

    /** @test */
    public function it_fetches_primary_object()
    {
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
        $object = $this->session->GetPreferredObject('Property', 'Photo', '00-1669', 1);
        $this->assertTrue($object->isPreferred());
    }
}
