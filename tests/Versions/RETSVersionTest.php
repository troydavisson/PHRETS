<?php

use PHRETS\Versions\RETSVersion;
use PHPUnit\Framework\TestCase;

class RETSVersionTest extends TestCase {

    /** @test **/
    public function it_loads()
    {
        $this->assertSame('1.7.2', (new RETSVersion)->setVersion('1.7.2')->getVersion());
    }

    /** @test **/
    public function it_cleans()
    {
        $this->assertSame('1.7.2', (new RETSVersion)->setVersion('RETS/1.7.2')->getVersion());
    }

    /** @test **/
    public function it_makes_the_header()
    {
        $this->assertSame('RETS/1.7.2', (new RETSVersion)->setVersion('1.7.2')->asHeader());
    }

    /** @test **/
    public function it_is_15()
    {
        $v = new RETSVersion;
        $v->setVersion('RETS/1.5');

        $this->assertTrue($v->is1_5());
        $this->assertTrue($v->isAtLeast1_5());
    }

    /** @test **/
    public function it_is_17()
    {
        $v = new RETSVersion;
        $v->setVersion('RETS/1.7');

        $this->assertTrue($v->is1_7());
        $this->assertFalse($v->is1_5());
        $this->assertFalse($v->is1_7_2());
        $this->assertTrue($v->isAtLeast1_7());
        $this->assertFalse($v->isAtLeast1_7_2());
    }

    /** @test **/
    public function it_is_172()
    {
        $v = new RETSVersion;
        $v->setVersion('RETS/1.7.2');

        $this->assertFalse($v->is1_7());
        $this->assertFalse($v->is1_5());
        $this->assertTrue($v->is1_7_2());
        $this->assertTrue($v->isAtLeast1_7());
        $this->assertTrue($v->isAtLeast1_7_2());
        $this->assertFalse($v->isAtLeast1_8());
    }

    /** @test **/
    public function it_is_18()
    {
        $v = new RETSVersion;
        $v->setVersion('RETS/1.8');

        $this->assertTrue($v->is1_8());
        $this->assertFalse($v->is1_7());
        $this->assertFalse($v->is1_5());
        $this->assertFalse($v->is1_7_2());
        $this->assertTrue($v->isAtLeast1_7());
        $this->assertTrue($v->isAtLeast1_7_2());
        $this->assertTrue($v->isAtLeast1_8());
    }

    /** @test **/
    public function it_compares()
    {
        $v = new RETSVersion;
        $v->setVersion('RETS/1.8');

        $this->assertTrue($v->isAtLeast('1.5'));
        $this->assertTrue($v->isAtLeast('1.7'));
        $this->assertTrue($v->isAtLeast('1.7.2'));
    }

    /**
     * @test
     * **/
    public function it_fails_bad_versions()
    {
        $this->expectException(\PHRETS\Exceptions\InvalidRETSVersion::class);
        $v = new RETSVersion;
        $v->setVersion('2.0');
    }

    /** @test **/
    public function it_converts_to_string()
    {
        $v = new RETSVersion;
        $v->setVersion('1.7.2');

        $this->assertSame('RETS/1.7.2', (string)$v);
    }
}
