<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 19-08-30 下午21:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql数据库 swoole协程驱动类-Pdo适配类
 * *********************************************************** */

namespace Cml\Db\MySql\Swoole;

use Swoole\Coroutine\MySQL;
use Swoole\Coroutine\Mysql\Statement as SwooleStatement;

/**
 * Orm MySql数据库 swoole协程驱动类-Pdo适配类
 *
 * @package Cml\Db\MySql\Swoole
 */
class Pdo
{
    /**
     * @var MySQL
     */
    private $link = null;

    public function __construct($link)
    {
        $this->link = $link;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->link, $name], $arguments);
    }

    public function __get($name)
    {
        return $this->link->name;
    }

    public function lastInsertId()
    {
        return $this->link->insert_id;
    }

    public function beginTransaction()
    {
        return $this->link->begin();
    }
}




