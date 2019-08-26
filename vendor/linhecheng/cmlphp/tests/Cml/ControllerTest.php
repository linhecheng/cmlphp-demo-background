<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Controller测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Cml;
use Cml\Controller;
use Cml\Lock\File;
use Cml\Model;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    /**
     * @var Controller
     */
    private static $c = null;

    public static function setUpBeforeClass()
    {
        self::$c = new Controller();
    }

    public function testModel()
    {
        $this->assertInstanceOf(Model::class, self::$c->model());
    }

    public function testLock()
    {
        Cml::getContainer()->bind('cml_lock', File::class);
        $this->assertInstanceOf(File::class, self::$c->locker());
    }
}