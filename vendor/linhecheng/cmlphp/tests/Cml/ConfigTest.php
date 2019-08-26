<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Config测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
   public function testConfigSetGet()
   {
       Config::set([
           'a' => 1,
           'b' => [
               'c' => [
                   2,
                   3
               ],
               'd' => 5
           ]
       ]);

       $this->assertEquals(1, Config::get('a'));
       $this->assertEquals([
           'c' => [
               2,
               3
           ],
           'd' => 5
       ], Config::get('b'));

       $this->assertEquals([
           2,
           3
       ], Config::get('b.c'));

       $this->assertEquals(5, Config::get('b.d'));

       Config::set('b.c', 5);

       $this->assertEquals(5, Config::get('b.c'));
       $this->assertEquals([
           'c' => 5,
           'd' => 5
       ], Config::get('b'));

       $this->assertEquals(1, Config::get('a'));
   }
}
