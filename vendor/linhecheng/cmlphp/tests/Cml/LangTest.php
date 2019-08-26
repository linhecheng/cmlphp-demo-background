<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Lang测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Lang;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    public function testSetGet()
    {
        Lang::set([
            'not found' => '%s 文件未找到!',
            'my name is {name}' => '我的名字是{name}'
        ]);

        Lang::set('{language} is very powerful', '{language}非常强大');

        $this->assertEquals(Lang::get('not found', 'aa'), 'aa 文件未找到!');
        $this->assertEquals(Lang::get('my name is {name}', ['name' => 'cmlphp']), '我的名字是cmlphp');
        $this->assertEquals(Lang::get('{language} is very powerful', ['language' => 'php']), 'php非常强大');
    }
}
