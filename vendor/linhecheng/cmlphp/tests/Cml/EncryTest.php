<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Encry测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Encry;
use PHPUnit\Framework\TestCase;

class EncryTest extends TestCase
{
    public function testEncryptDecrypt()
    {
        $this->assertEquals(Encry::decrypt(Encry::encrypt('abc123', 'hahaha'), 'hahaha'), 'abc123');
    }
}
