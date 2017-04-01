<?php

class GetMetadataIntegrationTest extends BaseIntegration
{
    /**
     * System
     */

    /** @test **/
    public function it_gets_system_data()
    {
        $this->play('GetMetadata/system_1.7.2');
        $this->session->Login();

        $system = $this->session->GetSystemMetadata();
        $this->assertTrue($system instanceof \PHRETS\Models\Metadata\System);
    }

    /** @test **/
    public function it_gets_system_data_for_1_5()
    {
        $this->play('GetMetadata/system_1.5');
        $this->session->getConfiguration()->setRetsVersion('1.5');

        $this->session->Login();

        $system = $this->session->GetSystemMetadata();
        $this->assertTrue($system instanceof \PHRETS\Models\Metadata\System);
        $this->assertSame('demomls', $system->getSystemId());
    }

    /** @test **/
    public function it_makes_a_good_url()
    {
        $this->play('GetMetadata/system_1.7.2');
        $this->session->Login();

        $this->session->GetSystemMetadata();
        $this->assertSame(
            'http://retsgw.flexmls.com:80/rets2_1/GetMetadata?Type=METADATA-SYSTEM&ID=0&Format=STANDARD-XML',
            $this->session->getLastRequestURL()
        );
    }

    /** @test **/
    public function it_sees_some_attributes()
    {
        $this->play('GetMetadata/system_1.7.2');
        $this->session->Login();

        $system = $this->session->GetSystemMetadata();
        $this->assertSame('demomls', $system->getSystemId());
        $this->assertSame('-05:00', $system->getTimeZoneOffset());
    }

    /** @test **/
    public function it_gets_related_resources()
    {
        $this->play('GetMetadata/system_with_resources_compare_1.7.2');
        $this->session->Login();

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
        $this->play('GetMetadata/property_resource_1.7.2');
        $this->session->Login();

        $resource = $this->session->GetResourcesMetadata('Property');
        $this->assertTrue($resource instanceof \PHRETS\Models\Metadata\Resource);
        $this->assertSame('Property', $resource->getStandardName());
        $this->assertSame('7', $resource->getClassCount());
    }

    /** @test **/
    public function it_gets_all_resource_data()
    {
        $this->play('GetMetadata/resources_1.7.2');
        $this->session->Login();

        $resources = $this->session->GetResourcesMetadata();
        $this->assertSame(6, $resources->count());
        $this->assertSame('ActiveAgent', $resources->first()->getResourceId());
        $this->assertSame('Room', $resources->last()->getResourceId());
    }

    /** @test **/
    public function it_gets_keyed_resource_data()
    {
        $this->play('GetMetadata/resources_1.7.2');
        $this->session->Login();

        $resources = $this->session->GetResourcesMetadata();
        $this->assertInstanceOf('\PHRETS\Models\Metadata\Resource', $resources['Property']);
    }

    /**
     * @test
     * @expectedException \PHRETS\Exceptions\MetadataNotFound
     * **/
    public function it_errors_with_bad_resource_name()
    {
        $this->play('GetMetadata/bad_resource_1.7.2');
        $this->session->Login();

        $this->session->GetResourcesMetadata('Bogus');
    }

    /** @test **/
    public function it_gets_related_classes()
    {
        $this->play('GetMetadata/resources_followed_by_classes_1.7.2');
        $this->session->Login();

        $resource_classes = $this->session->GetResourcesMetadata('Property')->getClasses();
        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertEquals($resource_classes, $classes);
    }

    /** @test **/
    public function it_gets_related_object_metadata()
    {
        $this->play('GetMetadata/resources_followed_by_objects_1.7.2');
        $this->session->Login();

        $object_types = $this->session->GetResourcesMetadata('Property')->getObject();
        $this->assertSame('Photo', $object_types->first()->getObjectType());
    }

    /**
     * Classes
     */

    /** @test **/
    public function it_gets_class_data()
    {
        $this->play('GetMetadata/property_classes_1.7.2');
        $this->session->Login();

        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertTrue($classes instanceof \Illuminate\Support\Collection);
        $this->assertSame(7, $classes->count());
        $this->assertSame('A', $classes->first()->getClassName());
    }

    /** @test **/
    public function it_gets_related_table_data()
    {
        $this->play('GetMetadata/property_classes_followed_by_table_1.7.2');
        $this->session->Login();

        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertTrue($classes instanceof \Illuminate\Support\Collection);
        $this->assertSame('LIST_0', $classes->first()->getTable()->first()->getSystemName());
    }

    /** @test **/
    public function it_gets_keyed_class_metadata()
    {
        $this->play('GetMetadata/property_classes_1.7.2');
        $this->session->Login();

        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertInstanceOf('\PHRETS\Models\Metadata\ResourceClass', $classes['A']);
    }

    /**
     * Table
     */

    /** @test **/
    public function it_gets_table_data()
    {
        $this->play('GetMetadata/table_a_1.7.2');
        $this->session->Login();

        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);
        $this->assertTrue($fields->count() > 100, "Verify that a lot of fields came back");
        $this->assertSame('LIST_0', $fields->first()->getSystemName());
    }

    /** @test **/
    public function it_sees_table_attributes()
    {
        $this->play('GetMetadata/table_a_1.7.2');
        $this->session->Login();

        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertSame('Property', $fields->first()->getResource());
        $this->assertSame('A', $fields->last()->getClass());
    }

    /** @test **/
    public function it_sees_fields_by_key()
    {
        $this->play('GetMetadata/table_a_1.7.2');
        $this->session->Login();

        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);
        $this->assertSame('Listing ID', $fields->get('LIST_105')->getLongName());
    }

    /** @test **/
    public function it_sees_fields_by_standard_key()
    {
        $this->play('GetMetadata/table_a_1.7.2');
        $this->session->Login();

        $fields = $this->session->GetTableMetadata('Property', 'A', 'StandardName');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);
        $this->assertSame('Listing ID', $fields->get('ListingID')->getLongName());
    }

    /**
     * Object
     */

    /** @test **/
    public function it_gets_object_metadata()
    {
        $this->play('GetMetadata/object_metadata_1.7.2');
        $this->session->Login();

        $object_types = $this->session->GetObjectMetadata('Property');
        $this->assertTrue($object_types instanceof \Illuminate\Support\Collection);
        $this->assertTrue($object_types->count() > 4, "Verify that a few came back");
        $this->assertSame('Photo', $object_types->first()->getObjectType());
        $this->assertSame('LIST_133', $object_types->first()->getObjectCount());
    }

    /** @test **/
    public function it_gets_keyed_object_metadata()
    {
        $this->play('GetMetadata/object_metadata_1.7.2');
        $this->session->Login();

        $object_types = $this->session->GetObjectMetadata('Property');
        $this->assertInstanceOf('\PHRETS\Models\Metadata\Object', $object_types['Photo']);
    }

    /**
     * Lookups
     */

    /** @test **/
    public function it_gets_lookup_values()
    {
        $this->play('GetMetadata/lookup_metadata_1.7.2');
        $this->session->Login();

        $values = $this->session->GetLookupValues('Property', '20000426151013376279000000');
        $this->assertTrue($values instanceof \Illuminate\Support\Collection);
        $this->assertSame('Fractional Ownership', $values->first()->getLongValue());
        $this->assertSame('RNKXIIVU9UW', $values->first()->getValue());
    }

    /** @test **/
    public function it_gets_related_lookup_values()
    {
        $this->play('GetMetadata/table_followed_by_lookup_1.7.2');
        $this->session->Login();

        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);

        $quick_way = $fields->get('LIST_9')->getLookupValues();
        $manual_way = $this->session->GetLookupValues('Property', '20000426151013376279000000');

        $this->assertEquals($quick_way->first(), $manual_way->first());
    }

    /** @test **/
    public function it_recovers_from_bad_lookuptype_tag()
    {
        $this->play('GetMetadata/it_recovers_from_bad_lookuptype_tag');
        $this->session->Login();

        $values = $this->session->GetLookupValues('Property', '20000426151013376279000000');
        $this->assertCount(6, $values);
    }

    /** @test **/
    public function it_handles_incomplete_object_metadata_correctly()
    {
        $this->play('GetMetadata/it_handles_incomplete_object_metadata_correctly');
        $this->session->Login();

        $values = $this->session->GetObjectMetadata('PropertyPowerProduction');
        $this->assertCount(0, $values);
    }
}
