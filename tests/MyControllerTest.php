<?php

use PHPUnit\Framework\TestCase;

class MyControllerTest extends TestCase
{
    public function additionProvider()
    {
        return [
            'a' => [
                ['id' => '000f9e6077209d75b0385c73ed58c1a0']

            ],
        ];
    }


    /**
     * @dataProvider additionProvider
     */
    public function testIndex($data)
    {
        $m      = new MyController();
        $result = $m->index($data);
        $this->assertArrayHasKey('id', $result);

    }


    public function testDemo()
    {
        $m      = new MyController();

        $this->assertEquals(123,$m->demo());
    }
}