<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Cml测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Cml;
use Cml\Container;
use PHPUnit\Framework\TestCase;

class CmlTest extends TestCase
{
    public function testGetContainer()
    {
        $this->assertInstanceOf(Container::class, Cml::getContainer());
    }

    public function testDoteToArr()
    {
        $array = [
            'a' => [
                'b' => 1,
                'c' => [
                    'd' => 2
                ]
            ],
            'e' => 5
        ];
        $this->assertEquals(Cml::doteToArr('a.b', $array), 1);
        $this->assertEquals(Cml::doteToArr('a.c', $array), ['d' => 2]);
        $this->assertEquals(Cml::doteToArr('a.c.d', $array), 2);
        $this->assertEquals(Cml::doteToArr('e', $array), 5);
    }

    public function testSetAndGetApplicationDir()
    {
        Cml::setApplicationDir([
           'dir1' => 1,
            'dir2' => 2
        ]);

        $this->assertEquals(Cml::getApplicationDir('dir1'), 1);
        $this->assertEquals(Cml::getApplicationDir('dir2'), 2);
    }
}
