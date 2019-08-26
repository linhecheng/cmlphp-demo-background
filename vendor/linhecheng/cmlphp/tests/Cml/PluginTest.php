<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Plugin测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Plugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    private static $i = 0;

    public function testHook()
    {
        Plugin::mount('phpunit_hook', function () {
            return 1;
        });

        $this->assertEquals(Plugin::hook('phpunit_hook'), 1);

        self::$i = 0;
        Plugin::mount('phpunit_hook2', [
            '\\tests\\Cml\\PluginTest::Inc',
            '\\tests\\Cml\\PluginTest::Inc'
        ]);
        Plugin::hook('phpunit_hook2');
        $this->assertEquals(self::$i, 2);

        Plugin::mount('phpunit_hook3', '\\tests\\Cml\\PluginTest::Inc');
        Plugin::hook('phpunit_hook3');
        $this->assertEquals(self::$i, 3);
    }

    public static function Inc()
    {
        self::$i++;
    }
}
