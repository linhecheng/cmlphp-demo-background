<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql数据库 swoole协程驱动类
 * *********************************************************** */

namespace Cml\Db\MySql\Swoole;

use Cml\Cml;
use Cml\Debug;
use Cml\Exception\PdoConnectException;
use Cml\Log;
use Cml\Plugin;
use mysql_xdevapi\Exception;
use Swoole\Coroutine;
use Swoole\Coroutine\MySQL;
use function Cml\throwException;

/**
 * Orm MySql数据库 swoole协程驱动类
 *
 * @package Cml\Db\MySql\Swoole
 */
class Swoole extends \Cml\Db\MySql\Pdo
{
    /**
     * 当前co内使用的dblink
     *
     * @var array
     */
    private $coMysqlLik = [
        'rlink' => [],
        'rlink' => []
    ];

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
        return new Pool($host, $username, $password, $dbName, $charset, $engine, $pConnect);
    }

    /**
     * 释放某连接
     *
     * @param $link
     */
    public function free()
    {
        return;
        $cid = Coroutine::getCid();

        if ($this->coMysqlLik['rlink'][$cid]) {
            self::$dbInst[$this->conf['mark'] . 'rlink']->free($this->coMysqlLik['rlink'][$cid]);
            unset($this->coMysqlLik['rlink'][$cid]);
        }
        if ($this->coMysqlLik['wlink'][$cid]) {
            self::$dbInst[$this->conf['mark'] . 'wlink']->free($this->coMysqlLik['wlink'][$cid]);
            unset($this->coMysqlLik['wlink'][$cid]);
        }
    }

    /**
     * 设置cache版本号
     *
     * @param string $table
     */
    public function setCacheVer($table)
    {
        $this->free();
        return parent::setCacheVer($table);
    }

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    public function getTables()
    {
        $return = parent::getTables();
        $this->free();
        return $return;
    }

    /**
     * 获取当前数据库中所有表的信息
     *
     * @return array
     */
    public function getAllTableStatus()
    {
        $return = parent::getAllTableStatus();
        $this->free();
        return $return;
    }

    /**
     * 获取表字段
     *
     * @param string $table 表名
     * @param mixed $tablePrefix 表前缀，不传则获取配置中配置的前缀
     * @param int $filter 0 获取表字段详细信息数组 1获取字段以,号相隔组成的字符串
     *
     * @return mixed
     */
    public function getDbFields($table, $tablePrefix = null, $filter = 0)
    {
        $return = parent::getDbFields($table, $tablePrefix, $filter);
        $this->free();
        return $return;
    }

    /**
     * 根据key取出数据
     *
     * @param string $key get('user-uid-123');
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param bool|string $useMaster 是否使用主库 默认读取从库 此选项为字符串时为表前缀$tablePrefix
     * @param null|string $tablePrefix 表前缀
     *
     * @return array
     */
    public function get($key, $and = true, $useMaster = false, $tablePrefix = null)
    {
        $return = parent::get($key, $and, $useMaster, $tablePrefix);
        $this->free();
        return $return;
    }

    public function select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false)
    {
        $return = parent::select($offset, $limit, $useMaster, $fieldAsKey);
        $this->free();
        return $return;
    }


    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        $return = parent::commit();
        $this->free();
        return $return;
    }

    /**
     * 回滚事务
     *
     * @param bool $rollBackTo 是否为还原到某个保存点
     *
     * @return bool
     */
    public function rollBack($rollBackTo = false)
    {
        $return = parent::rollBack($rollBackTo);
        $this->free();
        return $return;
    }

    /**
     * 魔术方法 自动获取相应db实例
     *
     * @param string $db 要连接的数据库类型
     *
     * @return  resource|false 数据库 连接标识
     */
    public function __get($db)
    {
        $cid = Coroutine::getCid();

        if (isset($this->coMysqlLik[$db][$cid])) {
            return $this->coMysqlLik[$db][$cid];
        }

        if (!isset(self::$dbInst[$this->conf['mark'] . $db])) {
            $this->connectDb($db);
        }

        return $this->coMysqlLik[$db][$cid] = self::$dbInst[$this->conf['mark'] . $db]->getLink();
    }

    /**
     * 预处理语句
     *
     * @param string $sql 要预处理的sql语句
     * @param \Swoole\Coroutine\MySQL $link
     * @param bool $resetParams
     *
     * @return Statement
     */

    public function prepare($sql, $link = null, $resetParams = true)
    {
        $resetParams && $this->reset();
        is_null($link) && $link = $this->currentQueryIsMaster ? $this->wlink : $this->rlink;

        $this->currentSql = $sql;
        $this->currentPrepareIsResetParams = $resetParams;

        $sql = vsprintf($sql, array_pad([], count($this->bindParams), '?'));

        if (!$link) {
           var_export([$link]);
        }

        $stmt = $link->prepare($sql);//pdo默认情况prepare出错不抛出异常只返回Pdo::errorInfo
        if ($stmt === false) {
            if (in_array($link->errno, [2006, 2013])) {
                $link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
                $stmt = $link->prepare($sql);
                if ($stmt !== false) {
                    return new Statement($stmt);
                }
            }
            throw new \InvalidArgumentException(
                'Pdo Prepare Sql error! ,【Sql: ' . $this->buildDebugSql() . '】,【Code: ' . $link->errno . '】, 【ErrorInfo!: 
                ' . $link->error . '】 '
            );
        }
        return new Statement($stmt);
    }

    /**
     * 执行预处理语句
     *
     * @param object $stmt Statement
     * @param bool $clearBindParams
     *
     * @return bool
     */
    public function execute($stmt, $clearBindParams = true)
    {
        //empty($param) && $param = $this->bindParams;
        $this->conf['log_slow_sql'] && $startQueryTimeStamp = microtime(true);

        $error = false;

        if (!$stmt->execute(array_values($this->bindParams))) {
            $link = $this->currentQueryIsMaster ? $this->wlink : $this->rlink;
            $error = $link->error;

            if (in_array($link->errno, [2006, 2013])) {
                $link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
                $stmt = $this->prepare($this->currentSql, $link, $this->currentPrepareIsResetParams);

                if (!$stmt->execute($this->bindParams)) {
                    $error = $link->error;
                } else {
                    $error = false;
                }
            }
        }

        if ($error) {
            throw new \InvalidArgumentException('Pdo execute Sql error!,【Sql : ' . $this->buildDebugSql() . '】,【Code: ' . $link->errno . '】,【Error:' . $error . '】');
        }

        $slow = 0;
        if ($this->conf['log_slow_sql']) {
            $queryTime = microtime(true) - $startQueryTimeStamp;
            if ($queryTime > $this->conf['log_slow_sql']) {
                if (Plugin::hook('cml.mysql_query_slow', ['sql' => $this->buildDebugSql(), 'query_time' => $queryTime]) !== false) {
                    Log::notice('slow_sql', ['sql' => $this->buildDebugSql(), 'query_time' => $queryTime]);
                }
                $slow = $queryTime;
            }
        }

        if (Cml::$debug) {
            $this->debugLogSql($slow > 0 ? Debug::SQL_TYPE_SLOW : Debug::SQL_TYPE_NORMAL, $slow);
        }

        $this->currentQueryIsMaster = true;
        $this->currentSql = '';
        $clearBindParams && $this->clearBindParams();
        return true;
    }
}