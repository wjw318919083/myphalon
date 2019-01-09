<?php

use PHPUnit\Framework\TestCase;

class PHPUnitControllerTest extends TestCase
{

    /**
     * 断言:相等
     */
    public function testEquals()
    {
        $m = new PHPUnitController();
        //success
        $this->assertEquals('Equals', $m->Equals());
        //failures
        //$this->assertEquals('notEquals', $m->Equals());

    }

    /**
     * 断言:数组存在指定key
     */
    public function testHasKey()
    {
        $m = new PHPUnitController();
        //success
        $this->assertArrayHasKey('hasKey', $m->HasKey());
        //failures
        //$this->assertEquals('hasValue', $m->HasKey());
    }

    /**
     * 断言:数组不存在指定key
     */
    public function testNotHasKey()
    {
        $m = new PHPUnitController();
        //success
        $this->assertArrayNotHasKey('hasValue', $m->HasKey());
        //failures
        //$this->assertArrayNotHasKey('hasKey', $m->HasKey());
    }

    /**
     * 断言:不为空
     */
    public function testNotEmpty()
    {
        $m = new PHPUnitController();
        //success
        $this->assertNotEmpty($m->NotEmpty());
        //failures
        //$this->assertNotEmpty($m->IsEmpty());
    }

    /**
     * 断言:为空
     */
    public function testEmpty()
    {
        $m = new PHPUnitController();
        //success
        $this->assertEmpty($m->IsEmpty());
        //failures
        //$this->assertEmpty($m->NotEmpty());
    }

    /**
     * 断言:True
     */
    public function testIsTrue()
    {
        $m = new PHPUnitController();
        //success
        $this->assertTrue($m->IsTrue());
        //failures
        //$this->assertTrue($m->isFalse());
    }

    /**
     * 断言:False
     */
    public function testIsFalse()
    {
        $m = new PHPUnitController();
        //success
        $this->assertFalse($m->isFalse());
        //failures
        //$this->assertFalse($m->IsTrue());
    }
}