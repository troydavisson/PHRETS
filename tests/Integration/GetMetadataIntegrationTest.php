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
    public function it_gets_system_data_for_1_5()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl('http://retsgw.flexmls.com/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setRetsVersion('1.5');

        $session = new \PHRETS\Session($config);
        $session->Login();

        $system = $session->GetSystemMetadata();
        $this->assertTrue($system instanceof \PHRETS\Models\Metadata\System);
        $this->assertSame('demomls', $system->getSystemId());
    }

    /** @test **/
    public function it_makes_a_good_url()
    {
        $this->session->GetSystemMetadata();
        $this->assertSame(
            'http://retsgw.flexmls.com:80/rets2_1/GetMetadata?Type=METADATA-SYSTEM&ID=0&Format=STANDARD-XML',
            $this->session->getLastRequestURL()
        );
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
    public function it_gets_keyed_resource_data()
    {
        $resources = $this->session->GetResourcesMetadata();
        $this->assertInstanceOf('\PHRETS\Models\Metadata\Resource', $resources['Property']);
    }

    /**
     * @test
     * **/
    public function it_errors_with_bad_resource_name()
    {
        $this->expectException(\PHRETS\Exceptions\MetadataNotFound::class);
        $this->session->GetResourcesMetadata('Bogus');
    }

    /** @test **/
    public function it_gets_related_classes()
    {
        $resource_classes = $this->session->GetResourcesMetadata('Property')->getClasses();
        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertEquals($resource_classes, $classes);
    }

    /** @test **/
    public function it_gets_related_object_metadata()
    {
        $object_types = $this->session->GetResourcesMetadata('Property')->getObject();
        $this->assertSame('Photo', $object_types->first()->getObjectType());
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

    /** @test **/
    public function it_gets_related_table_data()
    {
        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertTrue($classes instanceof \Illuminate\Support\Collection);
        $this->assertSame('LIST_0', $classes->first()->getTable()->first()->getSystemName());
    }

    /** @test **/
    public function it_gets_keyed_class_metadata()
    {
        $classes = $this->session->GetClassesMetadata('Property');
        $this->assertInstanceOf('\PHRETS\Models\Metadata\ResourceClass', $classes['A']);
    }

    /**
     * Table
     */

    /** @test **/
    public function it_gets_table_data()
    {
        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);
        $this->assertTrue($fields->count() > 100, "Verify that a lot of fields came back");
        $this->assertSame('LIST_0', $fields->first()->getSystemName());
    }

    /** @test **/
    public function it_sees_table_attributes()
    {
        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertSame('Property', $fields->first()->getResource());
        $this->assertSame('A', $fields->last()->getClass());
    }

    /** @test **/
    public function it_sees_fields_by_key()
    {
        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);
        $this->assertSame('Listing ID', $fields->get('LIST_105')->getLongName());
    }

    /** @test **/
    public function it_sees_fields_by_standard_key()
    {
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
        $object_types = $this->session->GetObjectMetadata('Property');
        $this->assertTrue($object_types instanceof \Illuminate\Support\Collection);
        $this->assertTrue($object_types->count() > 4, "Verify that a few came back");
        $this->assertSame('Photo', $object_types->first()->getObjectType());
        $this->assertSame('LIST_133', $object_types->first()->getObjectCount());
    }

    /** @test **/
    public function it_gets_keyed_object_metadata()
    {
        $object_types = $this->session->GetObjectMetadata('Property');
        $this->assertInstanceOf('\PHRETS\Models\Metadata\BaseObject', $object_types['Photo']);
    }

    /**
     * Lookups
     */

    /** @test **/
    public function it_gets_lookup_values()
    {
        $values = $this->session->GetLookupValues('Property', '20000426151013376279000000');
        $this->assertTrue($values instanceof \Illuminate\Support\Collection);
        $this->assertSame('Lake/Other', $values->first()->getLongValue());
        $this->assertSame('5PSUX49PM1Q', $values->first()->getValue());
    }

    /** @test **/
    public function it_gets_related_lookup_values()
    {
        $fields = $this->session->GetTableMetadata('Property', 'A');
        $this->assertTrue($fields instanceof \Illuminate\Support\Collection);

        $quick_way = $fields->get('LIST_9')->getLookupValues();
        $manual_way = $this->session->GetLookupValues('Property', '20000426151013376279000000');

        $this->assertEquals($quick_way->first(), $manual_way->first());
    }

    /** @test **/
    public function it_recovers_from_bad_lookuptype_tag()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl('http://retsgw.flexmls.com/lookup/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setRetsVersion('1.5');

        $session = new \PHRETS\Session($config);
        $session->Login();

        $values = $session->GetLookupValues('Property', '20000426151013376279000000');
        $this->assertCount(6, $values);
    }

    /** @test **/
    public function it_handles_incomplete_object_metadata_correctly()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl('http://retsgw.flexmls.com/rets2_1/Login')
            ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
            ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
            ->setRetsVersion('1.5');

        $session = new \PHRETS\Session($config);
        $session->Login();

        $values = $session->GetObjectMetadata('PropertyPowerProduction');
        $this->assertCount(0, $values);
    }
}
