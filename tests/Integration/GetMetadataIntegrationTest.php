<?php

class GetMetadataIntegrationTest extends BaseIntegration
{
    /**
     * System
     */

    /** @test **/
    public function it_gets_system_data()
    {
        $system = $this->session->GetSystemMetadata();
        $this->assertTrue($system instanceof \PHRETS\Models\Metadata\System);
    }

    /** @test **/
    public function it_sees_some_attributes()
    {
        $system = $this->session->GetSystemMetadata();
        $this->assertSame('demomls', $system->getSystemId());
        $this->assertSame('-05:00', $system->getTimeZoneOffset());
    }

    /** @test **/
    public function it_gets_related_resources()
    {
        $system = $this->session->GetSystemMetadata()->getResources();
        $resources = $this->session->GetResourcesMetadata();
        $this->assertEquals($system, $resources);
    }

    /**
     * Resources
     */

    /** @test **/
    public function it_gets_resource_data()
    {
        $resource = $this->session->GetResourcesMetadata('Property');
        $this->assertTrue($resource instanceof \PHRETS\Models\Metadata\Resource);
        $this->assertSame('Property', $resource->getStandardName());
        $this->assertSame('7', $resource->getClassCount());
    }

    /** @test **/
    public function it_gets_all_resource_data()
    {
        $resources = $this->session->GetResourcesMetadata();
        $this->assertSame(9, $resources->count());
        $this->assertSame('ActiveAgent', $resources->first()->getResourceId());
        $this->assertSame('VirtualTour', $resources->last()->getResourceId());
    }

    /** @test **/
    public function it_errors_with_bad_resource_name()
    {
        $this->setExpectedException('PHRETS\\Exceptions\\MetadataNotFound');
        $this->session->GetResourcesMetadata('Bogus');
    }

    /** @test **/
    public function it_gets_related_classes()
    {
        $resource_classes = $this->session->GetResourcesMetadata('Property')->getClasses();
        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertEquals($resource_classes, $classes);
    }

    /**
     * Classes
     */

    /** @test **/
    public function it_gets_class_data()
    {
        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertTrue($classes instanceof \Illuminate\Support\Collection);
        $this->assertSame(7, $classes->count());
        $this->assertSame('A', $classes->first()->getClassName());
    }
}
