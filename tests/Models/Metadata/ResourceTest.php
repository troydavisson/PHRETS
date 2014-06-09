<?php

use PHRETS\Models\Metadata\Resource;

class ResourceTest extends PHPUnit_Framework_TestCase {

    /** @test **/
    public function it_holds()
    {
        $metadata = new Resource;
        $metadata->setDescription('Test Description');

        $this->assertSame('Test Description', $metadata->getDescription());
    }
}
