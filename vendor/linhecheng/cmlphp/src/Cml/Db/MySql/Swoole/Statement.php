<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 19-08-30 下午21:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql数据库 swoole协程驱动类-Statement适配类
 * *********************************************************** */
namespace Cml\Db\MySql\Swoole;

use Swoole\Coroutine\Mysql\Statement as SwooleStatement;

/**
 * Orm MySql数据库 swoole协程驱动类-Statement适配类
 *
 * @package Cml\Db\MySql\Swoole
 */
class Statement
{
    /**
     * @var SwooleStatement
     */
    private $stmt = null;

    public function __construct($stmt)
    {
        $this->stmt = $stmt;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->stmt, $name], $arguments);
    }

    public function __get($name)
    {
        return $this->stmt->name;
    }

    /**
     * @return int
     */
    public function rowCount()
    {
        return $this->stmt->affected_rows;
    }
}