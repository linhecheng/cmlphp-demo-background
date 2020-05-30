<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-3-1 下午18:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MongoDB数据库MongoDB驱动类 http://php.net/manual/zh/set.mongodb.php
 * *********************************************************** */

namespace Cml\Db\MongoDB;

use Cml\Cml;
use Cml\Config;
use Cml\Db\Base;
use Cml\Debug;
use Cml\Lang;
use Exception;
use InvalidArgumentException;
use MongoDB\BSON\Regex;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception as MongoDBDriverException;
use MongoDB\Driver\ReadPreference;
use \Generator;

/**
 * Orm MongoDB数据库MongoDB实现类
 *
 * @see http://php.net/manual/zh/set.mongodb.php
 *
 * @package Cml\Db\MySql
 */
class MongoDB extends Base
{
    /**
     * 最新插入的数据的id
     *
     * @var null
     */
    private $lastInsertId = null;

    /**
     * @var array sql组装
     */
    protected $sql = [
        'where' => [],
        'columns' => [],
        'limit' => [0, 5000],
        'orderBy' => [],
        'groupBy' => '',
        'having' => '',
    ];

    /**
     * 标识下个where操作为and 还是 or 默认是and操作
     *
     * @var bool
     */
    private $opIsAnd = true;

    /**
     * or操作中一组条件是否有多个条件
     *
     * @var bool
     */
    private $bracketsIsOpen = false;

    /**
     * 数据库连接串
     *
     * @param $conf
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->tablePrefix = isset($this->conf['master']['tableprefix']) ? $this->conf['master']['tableprefix'] : '';
    }

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    public function getTables()
    {
        $tables = [];
        if ($this->serverSupportFeature(3)) {
            $result = $this->runMongoCommand(['listCollections' => 1]);
            foreach ($result as $val) {
                $tables[] = $val['name'];
            }
        } else {
            $result = $this->runMongoQuery('system.namespaces');
            foreach ($result as $val) {
                if (strpos($val['name'], '$') === false) {
                    $tables[] = substr($val['name'], strpos($val['name'], '.') + 1);
                }
            }
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
        $return = [];
        $collections = $this->getTables();
        foreach ($collections as $collection) {
            $res = $this->runMongoCommand(['collStats' => $collection]);
            $return[substr($res[0]['ns'], strrpos($res[0]['ns'], '.') + 1)] = $res[0];
        }
        return $return;
    }

    /**
     * 获取数据库名
     *
     * @return string
     */
    private function getDbName()
    {
        return $this->conf['master']['dbname'];
    }

    /**
     * 返回从库连接
     *
     * @return Manager
     */
    private function getSlave()
    {
        return $this->rlink;
    }

    /**
     * 返回主库连接
     *
     * @return Manager
     */
    private function getMaster()
    {
        return $this->wlink;
    }

    /**
     * 获取表字段-因为mongodb中collection对字段是没有做强制一制的。这边默认获取第一条数据的所有字段返回
     *
     * @param string $table 表名
     * @param mixed $tablePrefix 表前缀，不传则获取配置中配置的前缀
     * @param int $filter 在MongoDB中此选项无效
     *
     * @return mixed
     */
    public function getDbFields($table, $tablePrefix = null, $filter = 0)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $one = $this->runMongoQuery($tablePrefix . $table, [], ['limit' => 1]);
        return empty($one) ? [] : array_keys($one[0]);
    }

    /**
     * 查询语句条件组装
     *
     * @param string $key eg: 'forum-fid-1-uid-2'
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param bool $noCondition 是否为无条件操作  set/delete/update操作的时候 condition为空是正常的不报异常
     * @param bool $noTable 是否可以没有数据表 当delete/update等操作的时候已经执行了table() table为空是正常的
     *
     * @return array eg: ['forum', "fid = '1' AND uid = '2'"]
     */
    protected function parseKey($key, $and = true, $noCondition = false, $noTable = false)
    {
        $keys = explode('-', $key);
        $table = strtolower(array_shift($keys));
        $len = count($keys);
        $condition = [];
        for ($i = 0; $i < $len; $i += 2) {
            $val = is_numeric($keys[$i + 1]) ? intval($keys[$i + 1]) : $keys[$i + 1];
            $and ? $condition[$keys[$i]] = $val : $condition['$or'][][$keys[$i]] = $val;
        }

        if (empty($table) && !$noTable) {
            throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'table'));
        }
        if (empty($condition) && !$noCondition) {
            throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'condition'));
        }

        return [$table, $condition];
    }

    /**
     * 根据key取出数据
     *
     * @deprecated
     *
     * @param string $key get('user-uid-123');
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param bool|string $useMaster 是否使用主库,此选项为字符串时为表前缀$tablePrefix
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

        $filter = [];
        isset($this->sql['limit'][0]) && $filter['skip'] = $this->sql['limit'][0];
        isset($this->sql['limit'][1]) && $filter['limit'] = $this->sql['limit'][1];

        return $this->runMongoQuery($tablePrefix . $tableName, $condition, $filter, $useMaster);
    }


    /**
     * 执行mongoQuery命令
     *
     * @param string $tableName 执行的mongoCollection名称
     * @param array $condition 查询条件
     * @param array $queryOptions 查询的参数
     * @param bool|string $useMaster 是否使用主库
     *
     * @return array
     */
    public function runMongoQuery($tableName, $condition = [], $queryOptions = [], $useMaster = false)
    {
        Cml::$debug && $this->debugLogSql('Query', $tableName, $condition, $queryOptions);

        $this->reset();
        $db = $useMaster ?
            $this->getMaster()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED))
            : $this->getSlave()->selectServer(new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED));
        $cursor = $db->executeQuery($this->getDbName() . ".{$tableName}", new Query($condition, $queryOptions));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $result = [];
        foreach ($cursor as $collection) {
            $result[] = $collection;
        }
        return $result;
    }

    /**
     * orm参数重置
     *
     * @param bool $must 是否强制重置
     *
     */
    public function reset($must = false)
    {
        $must && $this->paramsAutoReset();
        if (!$this->paramsAutoReset) {
            $this->alwaysClearColumns && $this->sql['columns'] = [];
            if ($this->alwaysClearTable) {
                $this->table = []; //操作的表
                $this->join = []; //是否内联
                $this->leftJoin = []; //是否左联结
                $this->rightJoin = []; //是否右联
            }
            return;
        }

        $this->sql = [
            'where' => [],
            'columns' => [],
            'limit' => [],
            'orderBy' => [],
            'groupBy' => '',
            'having' => '',
        ];

        $this->forceIndex = [];//强制索引
        $this->table = []; //操作的表
        $this->join = []; //是否内联
        $this->leftJoin = []; //是否左联结
        $this->rightJoin = []; //是否右联
        $this->whereNeedAddAndOrOr = 0;
        $this->opIsAnd = true;
    }

    /**
     * 执行mongoBulkWrite命令
     *
     * @param string $tableName 执行的mongoCollection名称
     * @param BulkWrite $bulk The MongoDB\Driver\BulkWrite to execute.
     *
     * @return \MongoDB\Driver\WriteResult
     */
    public function runMongoBulkWrite($tableName, BulkWrite $bulk)
    {
        $this->reset();
        $return = false;

        try {
            $return = $this->getMaster()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED))
                ->executeBulkWrite($this->getDbName() . ".{$tableName}", $bulk);
        } catch (BulkWriteException $e) {
            $result = $e->getWriteResult();

            // Check if the write concern could not be fulfilled
            if ($writeConcernError = $result->getWriteConcernError()) {
                throw new \RuntimeException(sprintf("%s (%d): %s\n",
                    $writeConcernError->getMessage(),
                    $writeConcernError->getCode(),
                    var_export($writeConcernError->getInfo(), true)
                ), 0, $e);
            }

            $errors = [];
            // Check if any write operations did not complete at all
            foreach ($result->getWriteErrors() as $writeError) {
                $errors[] = sprintf("Operation#%d: %s (%d)\n",
                    $writeError->getIndex(),
                    $writeError->getMessage(),
                    $writeError->getCode()
                );
            }
            throw new \RuntimeException(var_export($errors, true), 0, $e);
        } catch (MongoDBDriverException $e) {
            throw new \UnexpectedValueException(sprintf("Other error: %s\n", $e->getMessage()), 0, $e);
        }

        return $return;
    }

    /**
     * Debug模式记录查询语句显示到控制台
     *
     * @param string $type 查询的类型
     * @param string $tableName 查询的Collection
     * @param array $condition 条件
     * @param array $options 额外参数
     */
    private function debugLogSql($type = 'Query', $tableName, $condition = [], $options = [])
    {
        if (Cml::$debug) {
            Debug::addSqlInfo(sprintf(
                "［MongoDB {$type}］ Collection: %s, Condition: %s, Other: %s",
                $this->getDbName() . ".{$tableName}",
                json_encode($condition, JSON_UNESCAPED_UNICODE),
                json_encode($options, JSON_UNESCAPED_UNICODE)
            ));
        }
    }

    /**
     * 执行命令
     *
     * @param array $cmd 要执行的Command
     * @param bool $runOnMaster 使用主库还是从库执行 默认使用主库执行
     * @param bool $returnCursor 返回数据还是cursor 默认返回结果数据
     *
     * @return array|Cursor
     */
    public function runMongoCommand($cmd = [], $runOnMaster = true, $returnCursor = false)
    {
        Cml::$debug && $this->debugLogSql('Command', '', $cmd);

        $this->reset();
        $db = $runOnMaster ?
            $this->getMaster()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED))
            : $this->getSlave()->selectServer(new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED));
        $cursor = $db->executeCommand($this->getDbName(), new Command($cmd));

        if ($returnCursor) {
            return $cursor;
        } else {
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            $result = [];
            foreach ($cursor as $collection) {
                $result[] = $collection;
            }
            return $result;
        }
    }

    /**
     * 根据key 新增 一条数据
     *
     * @param string $table 表名
     * @param array $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return bool|int
     */
    public function insert($table, $data, $tablePrefix = null)
    {
        if (is_array($data)) {
            is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

            $bulk = new BulkWrite();
            $insertId = $bulk->insert($data);
            $result = $this->runMongoBulkWrite($tablePrefix . $table, $bulk);

            Cml::$debug && $this->debugLogSql('BulkWrite INSERT', $tablePrefix . $table, [], $data);

            if ($result->getInsertedCount() > 0) {
                $this->lastInsertId = sprintf('%s', $insertId);
            }
            return $this->insertId();
        } else {
            return false;
        }
    }

    /**
     * 新增多条数据
     *
     * @param string $table 表名
     * @param array $field mongodb中本参数无效
     * @param array $data eg: 多条数据的值 [['标题1', '内容1', 1, '2017'], ['标题2', '内容2', 1, '2017']]
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     * @param bool $openTransAction 是否开启事务 mongodb中本参数无效
     
     * @throws InvalidArgumentException
     *
     * @return bool|array
     */
    public function insertMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true)
    {
        $idArray = [];
        foreach ($data as $row) {
            $idArray[] = $this->insert($table, $row, $tablePrefix);
        }
        return $idArray;
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
        return $this->upSet($table, $data, $data, $tablePrefix);
    }

    /**
     * 插入或更新一条记录，当UNIQUE index or PRIMARY KEY存在的时候更新，不存在的时候插入
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param string $table 表名
     * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param array $up mongodb中此项无效
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     * @param array $upIgnoreField 更新的时候要忽略的的字段
     *
     * @return int
     */
    public function upSet($table, array $data = [], array $up = [], $tablePrefix = null, $upIgnoreField = [])
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $table;
        if (empty($tableName)) {
            throw new InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'upSet'));
        }
        $condition = $this->sql['where'];
        if (empty($condition)) {
            throw new InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'upSet'));
        }

        $up = array_merge($data, $up);
        foreach ($upIgnoreField as $key) {
            unset($up[$key]);
        }
        $bulk = new BulkWrite();
        $bulk->update($condition, ['$set' => $up], ['multi' => true, 'upsert' => true]);
        $result = $this->runMongoBulkWrite($tableName, $bulk);

        Cml::$debug && $this->debugLogSql('BulkWrite upSet', $tableName, $condition, $data);

        return $result->getModifiedCount();
    }

    /**
     * 更新数据
     *
     * @param string|array $key eg 'user-uid-$uid' 如果条件是通用whereXX()、表名是通过table()设定。这边可以直接传$data的数组
     * @param array | null $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return int
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

        $tableName = empty($tableName) ? $this->getRealTableName(key($this->table)) : $tablePrefix . $tableName;
        if (empty($tableName)) {
            throw new InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'update'));
        }
        $condition += $this->sql['where'];
        if (empty($condition)) {
            throw new InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'update'));
        }

        $bulk = new BulkWrite();
        $bulk->update($condition, ['$set' => $data], ['multi' => true]);
        $result = $this->runMongoBulkWrite($tableName, $bulk);

        Cml::$debug && $this->debugLogSql('BulkWrite UPDATE', $tableName, $condition, $data);

        return $result->getModifiedCount();
    }

    /**
     * 根据key值删除数据
     *
     * @param string $key eg: 'user-uid-$uid'
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return int
     */
    public function delete($key = '', $and = true, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $condition = '';

        empty($key) || list($tableName, $condition) = $this->parseKey($key, $and, true, true);

        $tableName = empty($tableName) ? $this->getRealTableName(key($this->table)) : $tablePrefix . $tableName;
        if (empty($tableName)) {
            throw new InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'delete'));
        }
        $condition += $this->sql['where'];
        if (empty($condition)) {
            throw new InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'delete'));
        }

        $bulk = new BulkWrite();
        $bulk->delete($condition);
        $result = $this->runMongoBulkWrite($tableName, $bulk);

        Cml::$debug && $this->debugLogSql('BulkWrite DELETE', $tableName, $condition);

        return $result->getDeletedCount();
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
     * 清空集合 这个操作太危险所以直接屏蔽了
     *
     * @param string $tableName 要清空的表名
     *
     * @return bool | $this
     */
    public function truncate($tableName)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 获取表主键 mongo直接返回 '_id'
     *
     * @param string $table 要获取主键的表名
     * @param string $tablePrefix 表前缀
     *
     * @return string || false
     */
    public function getPk($table, $tablePrefix = null)
    {
        return '_id';
    }

    /**
     * where 语句组装工厂
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array|int|string $value 值
     * @param string $operator 操作符
     
     * @throws Exception
     *
     * @return $this
     */
    public function conditionFactory($column, $value, $operator = '=')
    {
        $operator && $operator = strtoupper($operator);
        $currentOrIndex = isset($this->sql['where']['$or']) ? count($this->sql['where']['$or']) - 1 : 0;

        if ($this->opIsAnd) {
            if (isset($this->sql['where'][$column][$operator])) {
                throw new InvalidArgumentException('Mongodb Where Op key Is Exists[' . $column . $operator . ']');
            }
        } else if ($this->bracketsIsOpen) {
            if (isset($this->sql['where']['$or'][$currentOrIndex][$column][$operator])) {
                throw new InvalidArgumentException('Mongodb Where Op key Is Exists[' . $column . $operator . ']');
            }
        }

        switch ($operator) {
            case 'IN':
                // no break
            case 'NOT IN':
                empty($value) && $value = [0];
                //这边可直接跳过不组装sql，但是为了给用户提示无条件 便于调试还是加上where field in(0)
                if ($this->opIsAnd) {
                    $this->sql['where'][$column][$operator == 'IN' ? '$in' : '$nin'] = $value;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column][$operator == 'IN' ? '$in' : '$nin'] = $value;
                } else {
                    $this->sql['where']['$or'][][$column] = $operator == 'IN' ? ['$in' => $value] : ['$nin' => $value];
                }
                break;
            case 'BETWEEN':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$gt'] = $value[0];
                    $this->sql['where'][$column]['$lt'] = $value[1];
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$gt'] = $value[0];
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$lt'] = $value[1];
                } else {
                    $this->sql['where']['$or'][][$column] = ['$gt' => $value[0], '$lt' => $value[1]];
                }
                break;
            case 'NOT BETWEEN':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$lt'] = $value[0];
                    $this->sql['where'][$column]['$gt'] = $value[1];
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$lt'] = $value[0];
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$gt'] = $value[1];
                } else {
                    $this->sql['where']['$or'][][$column] = ['$lt' => $value[0], '$gt' => $value[1]];
                }
                break;
            case 'IS NULL':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$in'] = [null];
                    $this->sql['where'][$column]['$exists'] = true;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$in'] = [null];
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$exists'] = true;
                } else {
                    $this->sql['where']['$or'][][$column] = ['$in' => [null], '$exists' => true];
                }
                break;
            case 'IS NOT NULL':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$ne'] = null;
                    $this->sql['where'][$column]['$exists'] = true;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$ne'] = null;
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$exists'] = true;
                } else {
                    $this->sql['where']['$or'][][$column] = ['$ne' => null, '$exists' => true];
                }
                break;
            case '>':
                //no break;
            case '<':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column][$operator == '>' ? '$gt' : '$lt'] = $value;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column][$operator == '>' ? '$gt' : '$lt'] = $value;
                } else {
                    $this->sql['where']['$or'][][$column] = $operator == '>' ? ['$gt' => $value] : ['$lt' => $value];
                }
                break;
            case '>=':
                //no break;
            case '<=':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column][$operator == '>=' ? '$gte' : '$lte'] = $value;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column][$operator == '>=' ? '$gte' : '$lte'] = $value;
                } else {
                    $this->sql['where']['$or'][][$column] = $operator == '>=' ? ['$gte' => $value] : ['$lte' => $value];
                }
                break;
            case 'NOT LIKE':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$not'] = new Regex($value, 'i');
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$not'] = new Regex($value, 'i');
                } else {
                    $this->sql['where']['$or'][][$column] = ['$not' => new Regex($value, 'i')];
                }
                break;
            case 'LIKE':
                //no break;
            case 'REGEXP':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$regex'] = $value;
                    $this->sql['where'][$column]['$options'] = '$i';
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$regex'] = $value;
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$options'] = '$i';
                } else {
                    $this->sql['where']['$or'][][$column] = ['$regex' => $value, '$options' => '$i'];
                }
                break;
            case '!=':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column]['$ne'] = $value;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column]['$ne'] = $value;
                } else {
                    $this->sql['where']['$or'][][$column] = ['$ne' => $value];
                }
                break;
            case '=':
                if ($this->opIsAnd) {
                    $this->sql['where'][$column] = $value;
                } else if ($this->bracketsIsOpen) {
                    $this->sql['where']['$or'][$currentOrIndex][$column] = $value;
                } else {
                    $this->sql['where']['$or'][][$column] = $value;
                }
                break;
            case 'COLUMN':
                $this->sql['where']['$where'] = "this.{$column} = this.{$value}";
                break;
            case 'RAW':
                $this->sql['where']['$where'] = $column;
                break;
        }
        return $this;
    }

    /**
     * where条件组装 LIKE
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param bool $leftBlur 是否开始左模糊匹配
     * @param string |int $value
     * @param bool $rightBlur 是否开始右模糊匹配
     *
     * @return $this
     */
    public function whereLike($column, $leftBlur = false, $value, $rightBlur = false)
    {
        $this->conditionFactory(
            $column,
            ($leftBlur ? '' : '^') . preg_quote($this->filterLike($value)) . ($rightBlur ? '' : '$'),
            'LIKE'
        );
        return $this;
    }

    /**
     * where条件组装 LIKE
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param bool $leftBlur 是否开始左模糊匹配
     * @param string |int $value
     * @param bool $rightBlur 是否开始右模糊匹配
     *
     * @return $this
     */
    public function whereNotLike($column, $leftBlur = false, $value, $rightBlur = false)
    {
        $this->conditionFactory(
            $column,
            ($leftBlur ? '' : '^') . preg_quote($this->filterLike($value)) . ($rightBlur ? '' : '$'),
            'NOT LIKE'
        );
        return $this;
    }

    /**
     * 选择列
     *
     * @param string|array $columns 默认选取所有 ['id, 'name'] 选取id,name两列
     *
     * @return $this
     */
    public function columns($columns = '*')
    {
        if (false === is_array($columns) && $columns != '*') {
            $columns = func_get_args();
        }
        foreach ($columns as $column) {
            $this->sql['columns'][$column] = 1;
        }
        return $this;
    }

    /**
     * 排序
     *
     * @param string $column 要排序的字段
     * @param string $order 方向,默认为正序
     *
     * @return $this
     */
    public function orderBy($column, $order = 'ASC')
    {
        $this->sql['orderBy'][$column] = strtoupper($order) === 'ASC' ? 1 : -1;
        return $this;
    }

    /**
     * 分组 MongoDB中的聚合方式跟 sql不一样。这个操作屏蔽。如果要使用聚合直接使用MongoDB Command
     *
     * @param string $column 要设置分组的字段名
     *
     * @return $this
     */
    public function groupBy($column)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * having语句 MongoDB不支持此命令
     *
     * @param string $column 字段名
     * @param string $operator 操作符
     * @param string $value 值
     *
     * @return $this
     */
    public function having($column, $operator = '=', $value)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * join内联结 MongoDB不支持此命令
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function join($table, $on, $tablePrefix = null)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * leftJoin左联结 MongoDB不支持此命令
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function leftJoin($table, $on, $tablePrefix = null)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * rightJoin右联结 MongoDB不支持此命令
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function rightJoin($table, $on, $tablePrefix = null)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * union联结 MongoDB不支持此命令
     *
     * @param string|array $sql 要union的sql
     * @param bool $all 是否为union all
     *
     * @return $this
     */
    public function union($sql, $all = false)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 设置后面的where以and连接
     *
     * @param callable $callable 如果传入函数则函数内执行的条件会被()包围
     *
     * @return $this
     */
    public function _and(callable $callable = null)
    {
        $history = $this->opIsAnd;
        $this->opIsAnd = true;

        if (is_callable($callable)) {
            $this->lBrackets();
            $callable();
            $this->rBrackets();
            $this->opIsAnd = $history;
        }

        return $this;
    }

    /**
     * 设置后面的where以or连接
     *
     * @param callable $callable mongodb中元首
     *
     * @return $this
     */
    public function _or(callable $callable = null)
    {
        $history = $this->opIsAnd;
        $this->opIsAnd = false;

        if (is_callable($callable)) {
            $this->lBrackets();
            $callable();
            $this->rBrackets();
            $this->opIsAnd = $history;
        }

        return $this;
    }

    /**
     * 在$or操作中让一组条件支持多个条件
     *
     * @return $this
     */
    public function lBrackets()
    {
        $this->bracketsIsOpen = true;
        return $this;
    }

    /**
     * $or操作中关闭一组条件支持多个条件，启动另外一组条件
     *
     * @return $this
     */
    public function rBrackets()
    {
        $this->bracketsIsOpen = false;
        return $this;
    }

    /**
     * LIMIT
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     *
     * @return $this
     */
    public function limit($offset = 0, $limit = 10)
    {
        $limit < 1 && $limit = 100;
        $this->sql['limit'] = [$offset, $limit];
        return $this;
    }

    /**
     * 获取count(字段名或*)的结果
     *
     * @param string $field Mongo中此选项无效
     * @param bool $isMulti Mongo中此选项无效
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function count($field = '*', $isMulti = false, $useMaster = false)
    {
        $cmd = [
            'count' => $this->getRealTableName(key($this->table)),
            'query' => $this->sql['where']
        ];

        $count = $this->runMongoCommand($cmd, $useMaster);
        return intval($count[0]['n']);
    }

    /**
     * 获取 $max 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时此参数为要$group的字段
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function max($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, '$max', $useMaster);
    }

    /**
     * 获取 $min 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时此参数为要$group的字段
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function min($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, '$min', $useMaster);
    }

    /**
     * 获取 $sum的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时此参数为要$group的字段
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function sum($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, '$sum', $useMaster);
    }

    /**
     * 获取 $avg 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时此参数为要$group的字段
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function avg($field = 'id', $isMulti = false, $useMaster = false)
    {
        return $this->aggregation($field, $isMulti, '$avg', $useMaster);
    }

    /**
     * 获取聚合的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时此参数为要$group的字段
     * @param string $operation 聚合操作
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    private function aggregation($field, $isMulti = false, $operation = '$max', $useMaster = false)
    {
        $pipe = [];
        empty($this->sql['where']) || $pipe[] = [
            '$match' => $this->sql['where']
        ];
        $pipe[] = [
            '$group' => [
                '_id' => $isMulti ? '$' . $isMulti : '0',
                'count' => [$operation => '$' . $field]
            ]
        ];
        $res = $this->mongoDbAggregate($pipe, [], $useMaster);
        if ($isMulti === false) {
            return $res[0]['count'];
        } else {
            return $res;
        }
    }

    /**
     * MongoDb的distinct封装
     *
     * @param string $field 指定不重复的字段值
     *
     * @return mixed
     */
    public function mongoDbDistinct($field = '')
    {
        $cmd = [
            'distinct' => $this->getRealTableName(key($this->table)),
            'key' => $field,
            'query' => $this->sql['where']
        ];

        $data = $this->runMongoCommand($cmd, false);
        return $data[0]['values'];
    }

    /**
     * MongoDb的aggregate封装
     *
     * @param array $pipeline List of pipeline operations
     * @param array $options Command options
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function mongoDbAggregate($pipeline = [], $options = [], $useMaster = false)
    {
        $cmd = $options + [
                'aggregate' => $this->getRealTableName(key($this->table)),
                'pipeline' => $pipeline
            ];

        $data = $this->runMongoCommand($cmd, $useMaster);
        return $data[0]['result'];
    }

    /**
     * 获取自增id-需要先初始化数据 如:
     * db.mongoinckeycol.insert({id:0, 'table' : 'post'}) 即初始化帖子表(post)自增初始值为0
     *
     * @param string $collection 存储自增的collection名
     *
     * @param string $table 表的名称
     *
     * @return int
     */
    public function getMongoDbAutoIncKey($collection = 'mongoinckeycol', $table = 'post')
    {
        $res = $this->runMongoCommand([
            'findandmodify' => $collection,
            'update' => [
                '$inc' => ['id' => 1]
            ],
            'query' => [
                'table' => $table
            ],
            'new' => true
        ]);
        return intval($res[0]['value']['id']);
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
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 获取多条数据
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     * @param mixed $fieldAsKey 返回以某个字段做为key的数组
     *
     * @return array
     */
    public function select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false)
    {
        is_null($offset) || $this->limit($offset, $limit);

        $filter = [];
        count($this->sql['orderBy']) > 0 && $filter['sort'] = $this->sql['orderBy'];
        count($this->sql['columns']) > 0 && $filter['projection'] = $this->sql['columns'];
        isset($this->sql['limit'][0]) && $filter['skip'] = $this->sql['limit'][0];
        isset($this->sql['limit'][1]) && $filter['limit'] = $this->sql['limit'][1];

        $tableName = $this->getRealTableName(key($this->table));
        $this->forceIndex[$tableName] && $filter['hint'] = $this->forceIndex[$tableName];

        $return = $this->runMongoQuery(
            $tableName,
            $this->sql['where'],
            $filter,
            $useMaster
        );

        if ($fieldAsKey) {
            $result = [];
            foreach ($return as $row) {
                $result[$row[$fieldAsKey]] = $row;
            }
            $return = $result;
        }
        return $return;
    }

    /**
     * 返回一个迭代器
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return Generator
     */
    public function cursor($offset = null, $limit = null, $useMaster = false)
    {
        $this->logNotSupportMethod(__METHOD__);
    }

    /**
     * 返回INSERT，UPDATE 或 DELETE 查询所影响的记录行数
     *
     * @param \MongoDB\Driver\WriteResult $handle
     * @param int $type 执行的类型1:insert、2:update、3:delete
     *
     * @return int
     */
    public function affectedRows($handle, $type)
    {
        switch ($type) {
            case 1:
                return $handle->getInsertedCount();
                break;
            case 2:
                return $handle->getModifiedCount();
                break;
            case 3:
                return $handle->getDeletedCount();
                break;
            default:
                return false;
        }
    }

    /**
     * 获取上一INSERT的主键值
     *
     * @param mixed $link MongoDdb中此选项无效
     *
     * @return int
     */
    public function insertId($link = null)
    {
        return $this->lastInsertId;
    }

    /**
     * 连接数据库
     *
     * @param string $db rlink/wlink
     * @param bool $reConnect 是否重连--用于某些db如mysql.长连接被服务端断开的情况
     *
     * @return bool|false|mixed|resource
     */
    protected function connectDb($db, $reConnect = false)
    {
        if ($db == 'rlink') {
            //如果没有指定从数据库，则使用 master
            if (!isset($this->conf['slaves']) || empty($this->conf['slaves'])) {
                $this->rlink = $this->wlink;
                return $this->rlink;
            }

            $n = mt_rand(0, count($this->conf['slaves']) - 1);
            $conf = $this->conf['slaves'][$n];
            $this->rlink = $this->connect(
                $conf['host'],
                $conf['username'],
                $conf['password'],
                $conf['dbname'],
                isset($conf['replicaSet']) ? $conf['replicaSet'] : ''
            );
            return $this->rlink;
        } elseif ($db == 'wlink') {
            $conf = $this->conf['master'];
            $this->wlink = $this->connect(
                $conf['host'],
                $conf['username'],
                $conf['password'],
                $conf['dbname'],
                isset($conf['replicaSet']) ? $conf['replicaSet'] : ''
            );
            return $this->wlink;
        }
        return false;
    }

    /**
     * Db连接
     *
     * @param string $host 数据库host
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $dbName 数据库名
     * @param string $replicaSet replicaSet名称
     * @param string $engine 无用
     * @param bool $pConnect 无用
     *
     * @return mixed
     */
    public function connect($host, $username, $password, $dbName, $replicaSet = '', $engine = '', $pConnect = false)
    {
        $authString = "";
        if ($username && $password) {
            $authString = "{$username}:{$password}@";
        }

        $replicaSet && $replicaSet = '?replicaSet=' . $replicaSet;
        $dsn = "mongodb://{$authString}{$host}/{$dbName}{$replicaSet}";

        return new Manager($dsn);
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
            return false;
        }
        $val = abs(intval($val));
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $tableName;

        $bulk = new BulkWrite();
        $bulk->update($condition, ['$inc' => [$field => $val]], ['multi' => true]);
        $result = $this->runMongoBulkWrite($tableName, $bulk);

        Cml::$debug && $this->debugLogSql('BulkWrite INC', $tableName, $condition, ['$inc' => [$field => $val]]);

        return $result->getModifiedCount();
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
            return false;
        }
        $val = abs(intval($val));

        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . $tableName;

        $bulk = new BulkWrite();
        $bulk->update($condition, ['$inc' => [$field => -$val]], ['multi' => true]);
        $result = $this->runMongoBulkWrite($tableName, $bulk);

        Cml::$debug && $this->debugLogSql('BulkWrite DEC', $tableName, $condition, ['$inc' => [$field => -$val]]);

        return $result->getModifiedCount();
    }

    /**
     * 关闭连接
     *
     */
    public function close()
    {
        if (!empty($this->wlink)) {
            Config::get('session_user') || $this->wlink = null; //开启会话自定义保存时，不关闭防止会话保存失败
        }
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
        $cursor = $this->getMaster()->executeCommand(
            $this->getDbName(),
            new Command(['buildInfo' => 1])
        );

        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $info = current($cursor->toArray());
        return $info['version'];
    }

    /**
     * 开启事务-MongoDb不支持
     *
     * @return bool | $this
     */
    public function startTransAction()
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 提交事务-MongoDb不支持
     *
     * @return bool | $this
     */
    public function commit()
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 设置一个事务保存点-MongoDb不支持
     *
     * @param string $pointName
     *
     * @return bool | $this
     */
    public function savePoint($pointName)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 回滚事务-MongoDb不支持
     *
     * @param bool $rollBackTo 是否为还原到某个保存点
     *
     * @return bool | $this
     */
    public function rollBack($rollBackTo = false)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 调用存储过程-MongoDb不支持
     *
     * @param string $procedureName 要调用的存储过程名称
     * @param array $bindParams 绑定的参数
     * @param bool|true $isSelect 是否为返回数据集的语句
     *
     * @return array|int | $this
     */
    public function callProcedure($procedureName = '', $bindParams = [], $isSelect = true)
    {
        $this->logNotSupportMethod(__METHOD__);
        return $this;
    }

    /**
     * 判断当前mongod服务是否支持某个版本的特性
     *
     * @param int $version 要判断的版本
     *
     * @return bool
     */
    public function serverSupportFeature($version = 3)
    {
        $info = $this->getSlave()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY))->getInfo();
        $maxWireVersion = isset($info['maxWireVersion']) ? (integer)$info['maxWireVersion'] : 0;
        $minWireVersion = isset($info['minWireVersion']) ? (integer)$info['minWireVersion'] : 0;

        return ($minWireVersion <= $version && $maxWireVersion >= $version);
    }

    /**
     * 记录不支持的方法
     *
     * @param int $method
     */
    private function logNotSupportMethod($method)
    {
        Cml::$debug && Debug::addTipInfo('MongoDb NotSupport [' . $method . '] Method', Debug::TIP_INFO_TYPE_INFO, 'red');
    }
}
