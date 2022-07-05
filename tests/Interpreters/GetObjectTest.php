<?php

use PHRETS\Interpreters\GetObject;
use PHPUnit\Framework\TestCase;

class GetObjectTest extends TestCase {

    /** @test **/
    public function it_combines_singles()
    {
        $this->assertEquals(['12345:1'], GetObject::ids(12345, 1));
    }

    /** @test **/
    public function it_combines_multiple_from_string()
    {
        $this->assertEquals(['12345:1','67890:1'], GetObject::ids('12345,67890', 1));
    }

    /** @test **/
    public function it_combines_multiple_from_colon_string()
    {
        $this->assertEquals(['12345:1','67890:1'], GetObject::ids('12345:67890', 1));
    }

    /** @test **/
    public function it_combines_multiple_from_array()
    {
        $this->assertEquals(['12345:1','67890:1'], GetObject::ids([12345, 67890], 1));
    }

    /** @test **/
    public function it_combines_multiple_object_id_strings()
    {
        $this->assertEquals(['12345:1:2:3','67890:1:2:3'], GetObject::ids([12345, 67890], '1,2,3'));
    }

    /** @test **/
    public function it_combines_multiple_object_id_arrays()
    {
        $this->assertEquals(['12345:1:2:3','67890:1:2:3'], GetObject::ids([12345, 67890], [1, 2, 3]));
    }

    /** @test **/
    public function it_parses_ranges()
    {
        $this->assertEquals(['12345:1:2:3:4:5','67890:1:2:3:4:5'], GetObject::ids([12345, 67890], '1-5'));
    }
}
