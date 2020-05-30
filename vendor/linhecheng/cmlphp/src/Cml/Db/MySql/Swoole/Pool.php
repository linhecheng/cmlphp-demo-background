<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql数据库 swoole协程驱动类-连接池实现
 * *********************************************************** */

namespace Cml\Db\MySql\Swoole;

use Cml\Exception\PdoConnectException;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\MySQL;
use Swoole\Timer;

class Pool
{
    /**
     * 最小池
     *
     * @var int
     */
    private $min = 3;

    /**
     * 最大池
     *
     * @var int
     */
    private $max = 20;

    /**
     * 当前使用量
     *
     * @var
     */
    private $used = 0;

    /**
     * 连接池
     *
     * @var Channel
     */
    public $connections;

    private $dbConfig = [];

    /**
     * 初始化连接池
     *
     * @param string $host 数据库host
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $dbName 数据库名
     * @param string $charset 字符集
     * @param string $engine 引擎
     * @param bool $pConnect 是否为长连接
     */
    public function __construct($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false)
    {
        $this->dbConfig = func_get_args();

        $this->connections = new Channel($this->max + 1);

        for ($i = 0; $i < $this->min; $i++) {
            $this->connections->push([
                'utime' => time(),
                'link' => call_user_func_array([$this, 'connect'], $this->dbConfig),
            ]);
        }

        $this->gcTimer();
    }

    /**
     * 获取连接
     *
     * @return Pdo|null
     */
    public function getLink()
    {
        $link = null;
        if ($this->connections->isEmpty()) {
            if ($this->used < $this->max) {
                $link = call_user_func_array([$this, 'connect'], $this->dbConfig);
            }
        }
        $return = $link ?: $this->connections->pop()['link'];

        return $return;
    }

    /**
     * 释放某连接
     *
     * @param $link
     */
    public function free($link)
    {var_export(['free', Coroutine::getCid()]);
        $link && $this->connections->push([
            'utime' => time(),
            'link' => $link
        ]);
    }

    /**
     * 处理空闲连接
     */
    public function gcTimer()
    {
        Timer::tick(300000, function () {
            $list = [];
            if ($this->connections->length() < intval($this->max * 0.5)) {
                return;
            }
            while (true) {
                if (!$this->connections->isEmpty()) {
                    $obj = $this->connections->pop(0.001);
                    if ($this->count > $this->min && (time() - $obj['utime'] > 7200)) {//回收
                        $this->count--;
                    } else {
                        $this->connections->push($obj);
                    }
                } else {
                    break;
                }
            }
        });
    }

    /**
     * Db连接
     *
     * @param string $host 数据库host
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $dbName 数据库名
     * @param string $charset 字符集
     * @param string $engine 引擎
     * @param bool $pConnect 是否为长连接
     *
     * @return mixed
     */
    public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false)
    {
        $host = explode(':', $host);

        $doConnect = function () use ($host, $pConnect, $charset, $username, $password, $dbName) {
            $swooleMysql = new MySQL();
            if (!$swooleMysql->connect([
                'host' => $host[0],
                'port' => isset($host[1]) ? $host[1] : 3306,
                'user' => $username,
                'password' => $password,
                'database' => $dbName,
                'charset' => $charset,
                'fetch_mode' => true
            ])) {
                throw new PdoConnectException(
                    'Pdo Connect Error! ｛' .
                    $host[0] . (isset($host[1]) ? ':' . $host[1] : '') . ', ' . $dbName .
                    '} Code:' . $swooleMysql->connect_errno . ', ErrorInfo!:' . $swooleMysql->connect_error,
                    0
                );
            }
            return $swooleMysql;
        };
        $link = new Pdo($doConnect());

        //$link->exec("SET names $charset");
        isset($this->conf['sql_mode']) && $link->query('set sql_mode="' . $this->conf['sql_mode'] . '";'); //放数据库配 特殊情况才开
        if (!empty($engine) && $engine == 'InnoDB') {
            $link->query('SET innodb_flush_log_at_trx_commit=2');
        }
        return $link;
    }
}
