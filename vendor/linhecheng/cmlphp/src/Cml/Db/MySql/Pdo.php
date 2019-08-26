<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql数据库 Pdo驱动类
 * *********************************************************** */

namespace Cml\Db\MySql;

use Cml\Cml;
use Cml\Config;
use Cml\Db\Base;
use Cml\Debug;
use Cml\Exception\PdoConnectException;
use Cml\Lang;
use Cml\Log;
use Cml\Model;
use Cml\Plugin;

/**
 * Orm MySql数据库Pdo实现类
 *
 * @package Cml\Db\MySql
 */
class Pdo extends Base
{
    /**
     * 启用数据缓存
     *
     * @var bool
     */
    protected $openCache = true;

    /**
     * 当前查询使用的是否是主库
     *
     * @var \PDO
     */
    private $currentQueryIsMaster = true;

    /**
     * 当前执行的sql 异常情况用来显示在错误页/日志
     *
     * @var string
     */
    private $currentSql = '';

    /**
     * 强制某表使用某索引
     *
     * @var array
     */
    private $forceIndex = [];

    /**
     * 数据库连接串
     *
     * @param $conf
     */
    public function __construct($conf)
    {
        isset($conf['mark']) || $conf['mark'] = md5(json_encode($conf));
        $this->conf = $conf;
        isset($this->conf['log_slow_sql']) || $this->conf['log_slow_sql'] = false;
        $this->tablePrefix = $this->conf['master']['tableprefix'];
        $this->conf['cache_expire'] === false && $this->openCache = false;
    }

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    public function getTables()
    {
        $this->currentQueryIsMaster = false;
        $stmt = $this->prepare('SHOW TABLES;', $this->rlink);
        $this->execute($stmt);

        $tables = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row['Tables_in_' . $this->conf['master']['dbname']];
        }
        return $tables;
    }

    /**
     * 获取当前数据库中所有表的信息
     *
     * @return array
     */
    public function getAllTableStatus()
    {
        $this->currentQueryIsMaster = false;
        $stmt = $this->prepare('SHOW TABLE STATUS FROM ' . $this->conf['master']['dbname'], $this->rlink);
        $this->execute($stmt);
        $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $return = [];
        foreach ($res as $val) {
            $return[$val['Name']] = $val;
        }
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
        static $dbFieldCache = [];

        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        if ($filter == 1 && Cml::$debug) return '*'; //debug模式时直接返回*
        $table = strtolower($tablePrefix . $table);

        $info = false;

        if (isset($dbFieldCache[$table])) {
            $info = $dbFieldCache[$table];
        } else {
            Config::get('db_fields_cache') && $info = \Cml\simpleFileCache($this->conf['master']['dbname'] . '.' . $table);
            if (!$info || Cml::$debug) {
                $this->currentQueryIsMaster = false;
                $stmt = $this->prepare("SHOW COLUMNS FROM $table", $this->rlink, false);
                $this->execute($stmt, false);
                $info = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $info[$row['Field']] = [
                        'name' => $row['Field'],
                        'type' => $row['Type'],
                        'notnull' => (bool)($row['Null'] === ''), // not null is empty, null is yes
                        'default' => $row['Default'],
                        'primary' => (strtolower($row['Key']) == 'pri'),
                        'autoinc' => (strtolower($row['Extra']) == 'auto_increment'),
                    ];
                }

                count($info) > 0 && \Cml\simpleFileCache($this->conf['master']['dbname'] . '.' . $table, $info);
            }
            $dbFieldCache[$table] = $info;
        }

        if ($filter) {
            if (count($info) > 0) {
                $info = implode('`,`', array_keys($info));
                $info = '`' . $info . '`';
            } else {
                return '*';
            }
        }
        return $info;
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
        if (is_string($useMaster) && is_null($tablePrefix)) {
            $tablePrefix = $useMaster;
            $useMaster = false;
        }
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

        list($tableName, $condition) = $this->parseKey($key, $and);
        $tableName = $tablePrefix . $tableName;
        $sql = "SELECT * FROM {$tableName} WHERE {$condition} LIMIT 0, 1000";

        if ($this->openCache && $this->currentQueryUseCache) {
            $cacheKey = md5($sql . json_encode($this->bindParams)) . $this->getCacheVer($tableName);
            $return = Model::getInstance()->cache()->get($cacheKey);
        } else {
            $return = false;
        }

        if ($return === false) { //cache中不存在这条记录
            $this->currentQueryIsMaster = $useMaster;
            $stmt = $this->prepare($sql, $useMaster ? $this->wlink : $this->rlink);
            $this->execute($stmt);
            $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->openCache && $this->currentQueryUseCache && Model::getInstance()->cache()->set($cacheKey, $return, $this->conf['cache_expire']);
            $this->currentQueryUseCache = true;
        } else {
            if (Cml::$debug) {
                $this->currentSql = $sql;
                $this->debugLogSql(Debug::SQL_TYPE_FROM_CACHE);
                $this->currentSql = '';
            }

            $this->clearBindParams();
        }

        return $return;
    }

    /**
     * 新增 一条数据
     *
     * @param string $table 表名
     * @param array $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return bool|int
     */
    public function set($table, $data, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $table;
        if (is_array($data)) {
            $s = $this->arrToCondition($data);
            $this->currentQueryIsMaster = true;
            $stmt = $this->prepare("INSERT INTO {$tableName} SET {$s}", $this->wlink);
            $this->execute($stmt);

            $this->setCacheVer($tableName);
            return $this->insertId();
        } else {
            return false;
        }
    }

    /**
     * 新增多条数据
     *
     * @param string $table 表名
     * @param array $field 字段 eg: ['title', 'msg', 'status', 'ctime‘]
     * @param array $data eg: 多条数据的值 [['标题1', '内容1', 1, '2017'], ['标题2', '内容2', 1, '2017']]
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     * @param bool $openTransAction 是否开启事务 默认开启
     * @throws \InvalidArgumentException
     *
     * @return bool|array
     */
    public function setMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $table;
        if (is_array($data) && is_array($field)) {
            $field = array_flip(array_values($field));
            foreach ($field as $key => $val) {
                $field[$key] = $data[0][$val];
            }
            $s = $this->arrToCondition($field);

            try {
                $openTransAction && $this->startTransAction();
                $this->currentQueryIsMaster = true;
                $stmt = $this->prepare("INSERT INTO {$tableName} SET {$s}", $this->wlink);
                $idArray = [];
                foreach ($data as $row) {
                    $this->bindParams = array_values($row);
                    $this->execute($stmt);
                    $idArray[] = $this->insertId();
                }
                $openTransAction && $this->commit();
            } catch (\InvalidArgumentException $e) {
                $openTransAction && $this->rollBack();

                throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            $this->setCacheVer($tableName);
            return $idArray;
        } else {
            return false;
        }
    }

    /**
     * 插入或替换一条记录
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param string $table 表名
     * @param array $data 插入/更新的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return int
     */
    public function replaceInto($table, array $data, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $table;
        if (is_array($data)) {
            $s = $this->arrToCondition($data);
            $this->currentQueryIsMaster = true;
            $stmt = $this->prepare("REPLACE INTO {$tableName} SET {$s}", $this->wlink);
            $this->execute($stmt);

            $this->setCacheVer($tableName);
            return $this->insertId();
        } else {
            return false;
        }
    }

    /**
     * 插入或更新一条记录，当UNIQUE index or PRIMARY KEY存在的时候更新，不存在的时候插入
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param string $table 表名
     * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param array $up 更新的值-会自动merge $data中的数据
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return int
     */
    public function upSet($table, array $data, array $up = [], $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $table;
        if (is_array($data)) {
            $up = $this->arrToCondition(array_merge($data, $up));
            $s = $this->arrToCondition($data);
            $this->currentQueryIsMaster = true;
            $stmt = $this->prepare("INSERT INTO {$tableName} SET {$s} ON DUPLICATE KEY UPDATE {$up}", $this->wlink);
            $this->execute($stmt);

            $this->setCacheVer($tableName);
            return $this->insertId();
        } else {
            return false;
        }
    }

    /**
     * 根据key更新一条数据
     *
     * @param string|array $key eg: 'user'(表名)、'user-uid-$uid'(表名+条件) 、['xx'=>'xx' ...](即:$data数组如果条件是通用whereXX()、表名是通过table()设定。这边可以直接传$data的数组)
     * @param array | null $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com'] 可以直接通过$key参数传递
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return boolean
     */
    public function update($key, $data = null, $and = true, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $condition = '';

        if (is_array($data)) {
            list($tableName, $condition) = $this->parseKey($key, $and, true, true);
        } else {
            $data = $key;
        }

        if (empty($tableName)) {
            $tableAndCacheKey = $this->tableFactory(false);
            $tableName = $tableAndCacheKey[0];
            $upCacheTables = $tableAndCacheKey[1];
        } else {
            $tableName = $tablePrefix . $tableName;
            $upCacheTables = [$tableName];
            isset($this->forceIndex[$tableName]) && $tableName .= ' force index(' . $this->forceIndex[$tableName] . ') ';
        }

        if (empty($tableName)) {
            throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'update'));
        }
        $s = $this->arrToCondition($data);
        $whereCondition = $this->sql['where'];
        $whereCondition .= empty($condition) ? '' : (empty($whereCondition) ? 'WHERE ' : '') . $condition;
        if (empty($whereCondition)) {
            throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'update'));
        }
        $this->currentQueryIsMaster = true;
        $stmt = $this->prepare("UPDATE {$tableName} SET {$s} {$whereCondition}", $this->wlink);
        $this->execute($stmt);

        foreach ($upCacheTables as $tb) {
            $this->setCacheVer($tb);
        }
        return $stmt->rowCount();
    }

    /**
     * 根据key值删除数据
     *
     * @param string|int $key eg: 'user'(表名，即条件通过where()传递)、'user-uid-$uid'(表名+条件)、啥也不传(即通过table传表名)
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return boolean
     */
    public function delete($key = '', $and = true, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $condition = '';

        empty($key) || list($tableName, $condition) = $this->parseKey($key, $and, true, true);

        if (empty($tableName)) {
            $tableAndCacheKey = $this->tableFactory(false);
            $tableName = $tableAndCacheKey[0];
            $upCacheTables = $tableAndCacheKey[1];
        } else {
            $tableName = $tablePrefix . $tableName;
            $upCacheTables = [$tableName];
            isset($this->forceIndex[$tableName]) && $tableName .= ' force index(' . $this->forceIndex[$tableName] . ') ';
        }

        if (empty($tableName)) {
            throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'delete'));
        }
        $whereCondition = $this->sql['where'];
        $whereCondition .= empty($condition) ? '' : (empty($whereCondition) ? 'WHERE ' : '') . $condition;
        if (empty($whereCondition)) {
            throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'delete'));
        }
        $this->currentQueryIsMaster = true;
        $limit = '';
        if ($this->sql['limit']) {
            $limit = explode(',', $this->sql['limit']);
            $limit = 'LIMIT ' . $limit[1];
        }
        $stmt = $this->prepare("DELETE FROM {$tableName} {$whereCondition} {$limit}", $this->wlink);
        $this->execute($stmt);

        foreach ($upCacheTables as $tb) {
            $this->setCacheVer($tb);
        }
        return $stmt->rowCount();
    }

    /**
     * 获取处理后的表名
     *
     * @param string $table 表名
     *
     * @return string
     */
    private function getRealTableName($table)
    {
        return substr($table, strpos($table, '_') + 1);
    }

    /**
     * 根据表名删除数据 这个操作太危险慎用。不过一般情况程序也没这个权限
     *
     * @param string $tableName 要清空的表名
     *
     * @return bool
     */
    public function truncate($tableName)
    {
        $tableName = $this->tablePrefix . $tableName;
        $this->currentQueryIsMaster = true;
        $stmt = $this->prepare("TRUNCATE {$tableName}");

        $this->setCacheVer($tableName);
        return $stmt->execute();//不存在会报错，但无关紧要
    }

    /**
     * 获取 COUNT(字段名或*) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function count($field = '*', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, $useMaster, 'COUNT');
    }

    /**
     * 获取 MAX(字段名) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function max($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, $useMaster, 'MAX');
    }

    /**
     * 获取 MIN(字段名) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function min($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, $useMaster, 'MIN');
    }

    /**
     * 获取 SUM(字段名) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function sum($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, $useMaster, 'SUM');
    }

    /**
     * 获取 AVG(字段名) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function avg($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, $useMaster, 'AVG');
    }

    /**
     * 获取max(字段名)的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     * @param string $operation 聚合操作
     *
     * @return mixed
     */
    private function aggregation($field, $isMulti = false, $useMaster = false, $operation = 'COUNT')
    {
        is_string($isMulti) && $this->groupBy($isMulti)->columns($isMulti);
        $count = $this->columns(["{$operation}({$field})" => '__res__'])->select(null, null, $useMaster);
        if ($isMulti) {
            $return = [];
            foreach ($count as $val) {
                $return[$val[$isMulti]] = $operation === 'COUNT' ? intval($val['__res__']) : floatval($val['__res__']);
            }
            return $return;
        } else {
            return $operation === 'COUNT' ? intval($count[0]['__res__']) : floatval($count[0]['__res__']);
        }
    }

    /**
     * table组装工厂
     *
     * @param bool $isRead 是否为读操作
     *
     * @return array
     */
    private function tableFactory($isRead = true)
    {
        $table = $operator = '';
        $cacheKey = [];
        foreach ($this->table as $key => $val) {
            $realTable = $this->getRealTableName($key);
            $cacheKey[] = $isRead ? $this->getCacheVer($realTable) : $realTable;

            $on = null;
            if (isset($this->join[$key])) {
                $operator = ' INNER JOIN';
                $on = $this->join[$key];
            } elseif (isset($this->leftJoin[$key])) {
                $operator = ' LEFT JOIN';
                $on = $this->leftJoin[$key];
            } elseif (isset($this->rightJoin[$key])) {
                $operator = ' RIGHT JOIN';
                $on = $this->rightJoin[$key];
            } else {
                empty($table) || $operator = ' ,';
            }
            if (is_null($val)) {
                $table .= "{$operator} {$realTable}";
            } else {
                $table .= "{$operator} {$realTable} AS `{$val}`";
            }
            isset($this->forceIndex[$realTable]) && $table .= ' force index(' . $this->forceIndex[$realTable] . ') ';
            is_null($on) || $table .= " ON {$on}";
        }

        if (empty($table)) {
            throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', $isRead ? 'select' : 'update/delete'));
        }
        return [$table, $cacheKey];
    }

    /**
     * 强制使用索引
     *
     * @param string $table 要强制索引的表名(不带前缀)
     * @param string $index 要强制使用的索引
     * @param string $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return $this
     */
    public function forceIndex($table, $index, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $this->forceIndex[$tablePrefix . $table] = $index;
        return $this;
    }

    /**
     * 构建sql
     *
     * @param null $offset 偏移量
     * @param null $limit 返回的条数
     * @param bool $isSelect 是否为select调用， 是则不重置查询参数并返回cacheKey/否则直接返回sql并重置查询参数
     *
     * @return string|array
     */
    public function buildSql($offset = null, $limit = null, $isSelect = false)
    {
        is_null($offset) || $this->limit($offset, $limit);

        $this->sql['columns'] == '' && ($this->sql['columns'] = '*');

        $columns = $this->sql['columns'];

        $tableAndCacheKey = $this->tableFactory();

        empty($this->sql['limit']) && ($this->sql['limit'] = "LIMIT 0, 100");

        $sql = "SELECT $columns FROM {$tableAndCacheKey[0]} " . $this->sql['where'] . $this->sql['groupBy'] . $this->sql['having']
            . $this->sql['orderBy'] . $this->union . $this->sql['limit'];
        if ($isSelect) {
            return [$sql, $tableAndCacheKey[1]];
        } else {
            $this->currentSql = $sql;
            $sql = $this->buildDebugSql();
            $this->reset();
            $this->clearBindParams();
            $this->currentSql = '';
            return " ({$sql}) ";
        }
    }

    /**
     * 获取多条数据
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return array
     */
    public function select($offset = null, $limit = null, $useMaster = false)
    {
        list($sql, $cacheKey) = $this->buildSql($offset, $limit, true);

        if ($this->openCache && $this->currentQueryUseCache) {
            $cacheKey = md5($sql . json_encode($this->bindParams)) . implode('', $cacheKey);
            $return = Model::getInstance()->cache()->get($cacheKey);
        } else {
            $return = false;
        }

        if ($return === false) {
            $this->currentQueryIsMaster = $useMaster;
            $stmt = $this->prepare($sql, $useMaster ? $this->wlink : $this->rlink);
            $this->execute($stmt);
            $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->openCache && $this->currentQueryUseCache && Model::getInstance()->cache()->set($cacheKey, $return, $this->conf['cache_expire']);
            $this->currentQueryUseCache = true;
        } else {
            if (Cml::$debug) {
                $this->currentSql = $sql;
                $this->debugLogSql(Debug::SQL_TYPE_FROM_CACHE);
                $this->currentSql = '';
            }

            $this->reset();
            $this->clearBindParams();
        }
        return $return;
    }

    /**
     * 返回INSERT，UPDATE 或 DELETE 查询所影响的记录行数。
     *
     * @param $handle \PDOStatement
     * @param int $type 执行的类型1:insert、2:update、3:delete
     *
     * @return int
     */
    public function affectedRows($handle, $type)
    {
        return $handle->rowCount();
    }

    /**
     * 获取上一INSERT的主键值
     *
     * @param \PDO $link
     *
     * @return int
     */
    public function insertId($link = null)
    {
        is_null($link) && $link = $this->wlink;
        return $link->lastInsertId();
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
        $link = '';
        try {
            $host = explode(':', $host);
            if (substr($host[0], 0, 11) === 'unix_socket') {
                $dsn = "mysql:dbname={$dbName};unix_socket=" . substr($host[0], 12);
            } else {
                $dsn = "mysql:host={$host[0]};" . (isset($host[1]) ? "port={$host[1]};" : '') . "dbname={$dbName}";
            }

            if ($pConnect) {
                $link = new \PDO($dsn, $username, $password, [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
                ]);
            } else {
                $link = new \PDO($dsn, $username, $password, [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
                ]);
            }
        } catch (\PDOException $e) {
            throw new PdoConnectException(
                'Pdo Connect Error! ｛' .
                $host[0] . (isset($host[1]) ? ':' . $host[1] : '') . ', ' . $dbName .
                '} Code:' . $e->getCode() . ', ErrorInfo!:' . $e->getMessage(),
                0,
                $e
            );
        }
        //$link->exec("SET names $charset");
        isset($this->conf['sql_mode']) && $link->exec('set sql_mode="' . $this->conf['sql_mode'] . '";'); //放数据库配 特殊情况才开
        if (!empty($engine) && $engine == 'InnoDB') {
            $link->exec('SET innodb_flush_log_at_trx_commit=2');
        }
        return $link;
    }

    /**
     * 指定字段的值+1
     *
     * @param string $key 操作的key user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return bool
     */
    public function increment($key, $val = 1, $field = null, $tablePrefix = null)
    {
        list($tableName, $condition) = $this->parseKey($key, true);
        if (is_null($field) || empty($tableName) || empty($condition)) {
            $this->clearBindParams();
            return false;
        }
        $val = abs(intval($val));
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $tableName;

        $this->currentQueryIsMaster = true;
        $stmt = $this->prepare('UPDATE  `' . $tableName . "` SET  `{$field}` =  `{$field}` + {$val}  WHERE  $condition", $this->wlink);

        $this->execute($stmt);
        $this->setCacheVer($tableName);
        return $stmt->rowCount();
    }

    /**
     * 指定字段的值-1
     *
     * @param string $key 操作的key user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return bool
     */
    public function decrement($key, $val = 1, $field = null, $tablePrefix = null)
    {
        list($tableName, $condition) = $this->parseKey($key, true);
        if (is_null($field) || empty($tableName) || empty($condition)) {
            $this->clearBindParams();
            return false;
        }
        $val = abs(intval($val));

        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $tableName;
        $this->currentQueryIsMaster = true;
        $stmt = $this->prepare('UPDATE  `' . $tableName . "` SET  `$field` =  `$field` - $val  WHERE  $condition", $this->wlink);

        $this->execute($stmt);
        $this->setCacheVer($tableName);
        return $stmt->rowCount();
    }

    /**
     * 预处理语句
     *
     * @param string $sql 要预处理的sql语句
     * @param \PDO $link
     * @param bool $resetParams
     *
     * @return \PDOStatement
     */

    public function prepare($sql, $link = null, $resetParams = true)
    {
        $resetParams && $this->reset();
        is_null($link) && $link = $this->currentQueryIsMaster ? $this->wlink : $this->rlink;

        $sqlParams = [];
        foreach ($this->bindParams as $key => $val) {
            $sqlParams[] = ':param' . $key;
        }

        $this->currentSql = $sql;
        $sql = vsprintf($sql, $sqlParams);

        $stmt = $link->prepare($sql);//pdo默认情况prepare出错不抛出异常只返回Pdo::errorInfo
        if ($stmt === false) {
            $error = $link->errorInfo();
            if (in_array($error[1], [2006, 2013])) {
                $link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
                $stmt = $link->prepare($sql);
                if ($stmt === false) {
                    $error = $link->errorInfo();
                } else {
                    return $stmt;
                }
            }
            throw new \InvalidArgumentException(
                'Pdo Prepare Sql error! ,【Sql: ' . $this->buildDebugSql() . '】,【Code: ' . $link->errorCode() . '】, 【ErrorInfo!: 
                ' . $error[2] . '】 '
            );
        }
        return $stmt;
    }

    /**
     * 执行预处理语句
     *
     * @param object $stmt PDOStatement
     * @param bool $clearBindParams
     *
     * @return bool
     */
    public function execute($stmt, $clearBindParams = true)
    {
        foreach ($this->bindParams as $key => $val) {
            is_int($val) ? $stmt->bindValue(':param' . $key, $val, \PDO::PARAM_INT) : $stmt->bindValue(':param' . $key, $val, \PDO::PARAM_STR);
        }

        //empty($param) && $param = $this->bindParams;
        $this->conf['log_slow_sql'] && $startQueryTimeStamp = microtime(true);
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new \InvalidArgumentException('Pdo execute Sql error!,【Sql : ' . $this->buildDebugSql() . '】,【Error:' . $error[2] . '】');
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

    /**
     * Debug模式记录查询语句显示到控制台
     *
     * @param int $type
     * @param int $other $other type = SQL_TYPE_SLOW时带上执行时间
     */
    private function debugLogSql($type = Debug::SQL_TYPE_NORMAL, $other = 0)
    {
        Debug::addSqlInfo($this->buildDebugSql(), $type, $other);
    }

    /**
     * 组装sql用于DEBUG
     *
     * @return string
     */
    private function buildDebugSql()
    {
        $bindParams = $this->bindParams;
        foreach ($bindParams as $key => $val) {
            $bindParams[$key] = str_replace('\\\\', '\\', addslashes($val));
        }
        return vsprintf(str_replace('%s', "'%s'", $this->currentSql), $bindParams);
    }

    /**
     * 关闭连接
     *
     */
    public function close()
    {
        if (!Config::get('session_user')) {
            //开启会话自定义保存时，不关闭防止会话保存失败
            $this->wlink = null;
            unset($this->wlink);
        }

        $this->rlink = null;
        unset($this->rlink);
    }

    /**
     *获取mysql 版本
     *
     * @param \PDO $link
     *
     * @return string
     */
    public function version($link = null)
    {
        is_null($link) && $link = $this->wlink;
        return $link->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * 开启事务
     *
     * @return bool
     */
    public function startTransAction()
    {
        return $this->wlink->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        return $this->wlink->commit();
    }

    /**
     * 设置一个事务保存点
     *
     * @param string $pointName
     *
     * @return bool
     */
    public function savePoint($pointName)
    {
        return $this->wlink->exec("SAVEPOINT {$pointName}");
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
        if ($rollBackTo === false) {
            return $this->wlink->rollBack();
        } else {
            return $this->wlink->exec("ROLLBACK TO {$rollBackTo}");
        }
    }

    /**
     * 调用存储过程
     *
     * @param string $procedureName 要调用的存储过程名称
     * @param array $bindParams 绑定的参数
     * @param bool|true $isSelect 是否为返回数据集的语句
     *
     * @return array|int
     */
    public function callProcedure($procedureName = '', $bindParams = [], $isSelect = true)
    {
        $this->bindParams = $bindParams;
        $this->currentQueryIsMaster = true;
        $stmt = $this->prepare("exec {$procedureName}", $this->wlink);
        $this->execute($stmt);
        if ($isSelect) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return $stmt->rowCount();
        }
    }
}
