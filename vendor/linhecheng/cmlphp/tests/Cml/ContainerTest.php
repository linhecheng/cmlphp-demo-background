<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Container测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private static $container = null;

    public static function setUpBeforeClass()
    {
        self::$container = new Container();
    }

    public static function tearDownAfterClass()
    {
        self::$container = null;
    }

    public function testBind()
    {
        self::$container->bind('container', Container::class);

        self::$container->singleton('singleton', Container::class);

        $this->assertTrue(self::$container->isBind('container'));
        $this->assertTrue(self::$container->isBind('singleton'));
    }

    /**
     * @depends testBind
     */
    public function testAlias()
    {
        self::$container->alias('container', 'c');

        $this->assertTrue(self::$container->isExistAlias('c'));

        $this->assertEquals('container', self::$container->getAlias('c'));
    }

    public function testMake()
    {
        $instance1 = self::$container->make('container');
        $instance1->a = 1;

        $instance2 = self::$container->make('container');
        $instance2->a = 2;
        $this->assertNotEquals($instance1, $instance2);

        $instance1 = self::$container->make('singleton');
        $instance1->a = 1;

        $instance2 = self::$container->make('singleton');
        $instance2->a = 2;
        $this->assertEquals($instance1, $instance2);


    }
}