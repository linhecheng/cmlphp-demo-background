<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Db 数据库抽象基类
 * *********************************************************** */

namespace Cml\Db;

use Closure;
use Cml\Config;
use Cml\Entity\Entity;
use Cml\Http\Input;
use Cml\Interfaces\Db;
use Cml\Lang;
use Cml\Model;
use BadMethodCallException;
use InvalidArgumentException;

/**
 * Orm 数据库抽象基类
 *
 * @package Cml\Db
 */
abstract class Base implements Db
{
    use Query;

    /**
     * 多个Model中共享db连接实例
     *
     * @var array
     */
    protected static $dbInst = [
    ];

    /**
     * 启用数据缓存
     *
     * @var bool
     */
    protected $openCache = false;

    /**
     * 单独标记当前的query使不使用缓存
     *
     * @var bool
     */
    protected $currentQueryUseCache = true;

    /**
     * where操作需要加上and/or
     * 0 : 初始化两个都不加
     * 1 : 要加and
     * 2： 要加 or
     *
     * @var int
     */
    protected $whereNeedAddAndOrOr = 0;

    /**
     * 执行sql时绑定的参数
     *
     * @var array
     */
    protected $bindParams = [];

    /**
     * 配置信息
     *
     * @var array
     */
    protected $conf;

    /**
     * 表前缀方便外部读取
     *
     * @var string
     */
    public $tablePrefix;

    /**
     * sql组装
     *
     * @var array
     */
    protected $sql = [
        'where' => '',
        'columns' => '',
        'limit' => '',
        'orderBy' => '',
        'groupBy' => '',
        'having' => '',
    ];

    /**
     * 强制某表使用某索引
     *
     * @var array
     */
    protected $forceIndex = [];

    /**
     * 操作的表
     *
     * @var array
     */
    protected $table = [];

    /**
     * 是否内联 [表名 => 条件]
     *
     * @var array
     */
    protected $join = [];

    /**
     * 是否左联结 写法同内联
     *
     * @var array
     */
    protected $leftJoin = [];

    /**
     * 是否右联 写法同内联
     *
     * @var array
     */
    protected $rightJoin = [];

    /**
     * UNION 写法同内联
     *
     * @var string
     */
    protected $union = '';

    /**
     * orm参数是否自动重置
     *
     * @var bool
     */
    protected $paramsAutoReset = true;

    /**
     * $paramsAutoReset = false 的时候是否清除table.避免快捷方法重复调用table();
     *
     * @var bool
     */
    protected $alwaysClearTable = false;

    /**
     * $paramsAutoReset = false 的时候是否清除查询的字段信息.主要用于按批获取数据不用多次调用columns();
     *
     * @var bool
     */
    protected $alwaysClearColumns = true;

    /**
     * where、columns、orderBy、groupBy等操作自动添加的表别名前缀
     *
     * @var string
     */
    protected $columnsPrefix = '';

    /**
     * where、columns、orderBy、groupBy等操作自动添加的表别名前缀
     *
     * @param string $prefix
     *
     * @return $this;
     */
    public function setColumnsPrefix($prefix = '')
    {
        $this->columnsPrefix = $prefix;
        return $this;
    }

    /**
     * 定义操作的表
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return $this
     */
    public function table($table = '', $tablePrefix = null)
    {
        $hasAlias = is_array($table) ? true : false;
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix . ($hasAlias ? key($table) : $table);

        $this->table[count($this->table) . '_' . $tableName] = $hasAlias ? current($table) : null;
        return $this;
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
        if (isset(self::$dbInst[$this->conf['mark'] . $db])) {
            return self::$dbInst[$this->conf['mark'] . $db];
        }
        return $this->connectDb($db);
    }

    /**
     * 自动映射set方法
     *
     * @param string $name
     * @param array $arguments
     *
     * @return $this;
     * @throws BadMethodCallException
     *
     */
    public function __call($name, $arguments)
    {
        switch ($name) {
            case 'set':
                return call_user_func_array([$this, 'insert'], $arguments);
                break;
            case 'setMulti':
                return call_user_func_array([$this, 'insertMulti'], $arguments);
                break;
        }

        throw new BadMethodCallException($name);
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
        if ($reConnect) {
            self::$dbInst[$this->conf['mark'] . $db] = null;
            unset(self::$dbInst[$this->conf['mark'] . $db]);
        }

        if ($db == 'rlink') {
            //如果没有指定从数据库，则使用 master
            if (empty($this->conf['slaves'])) {
                if ($reConnect) {
                    return self::$dbInst[$this->conf['mark'] . $db] = $this->connectDb('wlink', true);
                } else {
                    return self::$dbInst[$this->conf['mark'] . $db] = $this->wlink;
                }
            }

            $n = mt_rand(0, count($this->conf['slaves']) - 1);
            $conf = $this->conf['slaves'][$n];

            return self::$dbInst[$this->conf['mark'] . $db] = $this->connect(
                $conf['host'],
                $conf['username'],
                $conf['password'],
                $conf['dbname'],
                $conf['charset'],
                isset($conf['engine']) ? $conf['engine'] : '',
                $conf['pconnect']
            );
        } elseif ($db == 'wlink') {
            $conf = $this->conf['master'];
            return self::$dbInst[$this->conf['mark'] . $db] = $this->connect(
                $conf['host'],
                $conf['username'],
                $conf['password'],
                $conf['dbname'],
                $conf['charset'],
                isset($conf['engine']) ? $conf['engine'] : '',
                $conf['pconnect']
            );
        }
        return false;
    }

    /**
     * 分页获取数据
     *
     * @param int $limit 每页返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     * @param null|int $page 当前页数-不传则获取配置中var_page配置的request值
     * @param mixed $fieldAsKey 返回以某个字段做为key的数组
     *
     * @return array
     */
    public function paginate($limit, $useMaster = false, $page = null, $fieldAsKey = false)
    {
        is_int($page) || $page = Input::requestInt(Config::get('var_page'), 1);
        $page < 1 && $page = 1;
        return call_user_func_array([$this, 'select'], [($page - 1) * $limit, $limit, $useMaster, $fieldAsKey]);
    }

    /**
     * 获取表主键
     *
     * @param string $table 要获取主键的表名
     * @param string $tablePrefix 表前缀，不传则获取配置中配置的前缀
     *
     * @return string || false
     */
    public function getPk($table, $tablePrefix = null)
    {
        $rows = $this->getDbFields($table, $tablePrefix);
        foreach ($rows as $val) {
            if ($val['primary']) {
                return $val['name'];
            }
        }
        return false;
    }

    /**
     * 获取一条数据
     *
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return array | bool
     */
    public function getOne($useMaster = false)
    {
        $result = $this->select(0, 1, $useMaster);
        if (isset($result[0])) {
            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * 根据条件是否成立执行对应的闭包
     *
     * @param bool $condition 条件
     * @param callable $trueCallback 条件成立执行的闭包
     * @param callable|null $falseCallback 条件不成立执行的闭包
     *
     * @return $this
     */
    public function when($condition, callable $trueCallback, callable $falseCallback = null)
    {
        if ($condition) {
            call_user_func($trueCallback, $this);
        } else {
            is_callable($falseCallback) && call_user_func($falseCallback, $this);
        }
        return $this;
    }

    /**
     * where 用户输入过滤
     *
     * @param string $val
     *
     * @return string
     */
    protected function filterLike($val)
    {
        return str_replace(['_', '%'], ['\_', '\%'], $val);
    }

    /**
     * where条件组装 WHERE EXISTS
     *
     * @param string | Closure $subSql 语句或闭包
     * @param array $subSqlBindParams 子句的PDO绑定参数
     *
     * @return $this;
     */
    public function whereExists($subSql, $subSqlBindParams = [])
    {
        $subSql instanceof Closure && $subSql = $subSql();
        is_array($subSql) && list($subSql, $subSqlBindParams) = $subSql;
        $this->conditionFactory('', $subSql, 'EXISTS');
        $this->bindParams = array_merge($this->bindParams, $subSqlBindParams);
        return $this;
    }

    /**
     * where条件组装 WHERE NOT EXISTS
     *
     * @param string | Closure $subSql 语句或闭包
     * @param array $subSqlBindParams 子句的PDO绑定参数
     *
     * @return $this;
     */
    public function whereNotExists($subSql, $subSqlBindParams = [])
    {
        $subSql instanceof Closure && $subSql = $subSql();
        is_array($subSql) && list($subSql, $subSqlBindParams) = $subSql;
        $this->conditionFactory('', $subSql, 'NOT EXISTS');
        $this->bindParams = array_merge($this->bindParams, $subSqlBindParams);
        return $this;
    }

    /**
     * 格式化查询字段
     *
     * @param string $column
     *
     * @return string
     */
    protected function formatColumnKey($column)
    {
        if ($this->haveMysqlFunction($column)) {
            return $column;
        }
        return implode(',', array_map(function ($col) {
            return implode('.', array_map(function ($field) {
                return implode(' ', array_map(function ($item) {
                    $item = trim(trim($item, '`'));
                    switch (strtoupper($item)) {
                        case '*':
                            return '*';
                        case 'DISTINCT':
                        case 'ASC':
                        case 'DESC':
                        case 'AS':
                        case 'GROUP':
                        case 'ORDER':
                        case 'HAVING':
                        case 'AND':
                        case 'OR':
                        case 'IS':
                        case 'NOT':
                        case 'NULL':
                        case '':
                            return $item;
                        default:
                            return "`{$item}`";
                    }
                }, explode(' ', $field)));
            }, explode('.', $col)));
        }, explode(',', $column)));
    }

    protected function addColumnPrefix($column)
    {
        return $this->columnsPrefix ? "{$this->columnsPrefix}.{$column}" : $column;
    }

    /**
     * where 语句组装工厂
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array|int|string $value 值
     * @param string $operator 操作符
     *
     * @return $this
     */
    public function conditionFactory($column, $value, $operator = '=')
    {
        $operator && $operator = strtoupper($operator);
        $column = $this->addColumnPrefix($column);

        if ($this->sql['where'] == '') $this->sql['where'] = 'WHERE ';

        if ($this->whereNeedAddAndOrOr === 1) {
            $this->sql['where'] .= ' AND ';
        } else if ($this->whereNeedAddAndOrOr === 2) {
            $this->sql['where'] .= ' OR ';
        }

        //下一次where操作默认加上AND
        $this->whereNeedAddAndOrOr = 1;

        if ($operator != 'RAW') {
            $column = $this->formatColumnKey($column);
        }

        if ($operator == 'IN' || $operator == 'NOT IN') {
            //empty($value) && $value = [0];
            if (empty($value)) {
                $this->sql['where'] .= ' 1 =  -1 ';//强制返回一个空的结果
                return $this;
            }
            $inValue = '(';
            foreach ($value as $val) {
                $inValue .= '%s ,';
                $this->bindParams[] = $val;
            }
            $this->sql['where'] .= "{$column} {$operator} " . rtrim($inValue, ',') . ') ';
        } elseif ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
            $betweenValue = '%s AND %s ';
            $this->bindParams[] = $value[0];
            $this->bindParams[] = $value[1];
            $this->sql['where'] .= "{$column} {$operator} {$betweenValue} ";
        } else if ($operator == 'IS NULL' || $operator == 'IS NOT NULL') {
            $this->sql['where'] .= "{$column} {$operator} ";
        } else if ($operator == 'COLUMN') {
            $value = $this->formatColumnKey($value);
            $this->sql['where'] .= "{$column} = {$value} ";
        } else if ($operator == 'RAW') {
            $this->sql['where'] .= str_replace('?', '%s', $column) . ' ';
            $value && $this->bindParams = array_merge($this->bindParams, $value);
        } else if ($operator == 'EXISTS' || $operator == 'NOT EXISTS') {
            $value = trim($value, ' ()');
            $this->sql['where'] .= "{$operator} ($value) ";
        } else {
            $this->sql['where'] .= "{$column} {$operator} ";
            if ($operator) {//兼容类式find_in_set()这类的函数查询
                $this->sql['where'] .= "%s ";
                $this->bindParams[] = $value;
            }

        }
        return $this;
    }

    /**
     * 获取columns属性中的值并清空
     *
     * @return mixed
     */
    public function getColumnsAndClear()
    {
        $columns = $this->sql['columns'];
        $this->sql['columns'] = '';
        return $columns;
    }

    /**
     * join内联结
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function join($table, $on, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

        $this->table($table, $tablePrefix);
        $hasAlias = is_array($table) ? true : false;

        $tableName = $tablePrefix . ($hasAlias ? key($table) : $table);
        $this->join[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
        return $this;
    }

    /**
     * leftJoin左联结
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function leftJoin($table, $on, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

        $this->table($table, $tablePrefix);
        $hasAlias = is_array($table) ? true : false;

        $tableName = $tablePrefix . ($hasAlias ? key($table) : $table);
        $this->leftJoin[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
        return $this;
    }

    /**
     * rightJoin右联结
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function rightJoin($table, $on, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

        $this->table($table, $tablePrefix);
        $hasAlias = is_array($table) ? true : false;

        $tableName = $tablePrefix . ($hasAlias ? key($table) : $table);
        $this->rightJoin[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
        return $this;
    }

    protected function filterUnionSql($sql)
    {
        return str_ireplace([
            'insert', "update", "delete", "\/\*", "\.\.\/", "\.\/", "union", "into", "load_file", "outfile"
        ],
            ["", "", "", "", "", "", "", "", "", ""],
            $sql);
    }

    /**
     * 解析联结的on参数
     *
     * @param string $table 要联结的表名
     * @param array $on ['on条件1', 'on条件2' => true] on条件为数字索引时多条件默认为and为非数字引时 条件=>true为and 条件=>false为or
     *
     * @return string
     */
    protected function parseOn(&$table, $on)
    {
        if (empty($on)) {
            throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_ON_', $table));
        }
        $result = '';
        foreach ($on as $key => $val) {
            if (is_numeric($key)) {
                $result == '' || $result .= ' AND ';
                $result .= $val;
            } else {
                $result == '' || $result .= ($val === true ? ' AND ' : ' OR ');
                $result .= $key;
            }
        }
        return addslashes($result); //on条件是程序员自己写死的表字段名不存在注入以防万一还是过滤一下
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
            $this->alwaysClearColumns && $this->sql['columns'] = '';
            if ($this->alwaysClearTable) {
                $this->table = []; //操作的表
                $this->join = []; //是否内联
                $this->leftJoin = []; //是否左联结
                $this->rightJoin = []; //是否右联
            }
            return;
        }

        $this->sql = [  //sql组装
            'where' => '',
            'columns' => '',
            'limit' => '',
            'orderBy' => '',
            'groupBy' => '',
            'having' => '',
        ];

        $this->forceIndex = [];//强制索引
        $this->table = []; //操作的表
        $this->join = []; //是否内联
        $this->leftJoin = []; //是否左联结
        $this->rightJoin = []; //是否右联
        $this->whereNeedAddAndOrOr = 0;
    }

    /**
     * 清空绑定的参数
     *
     */
    protected function clearBindParams()
    {
        if ($this->paramsAutoReset) {
            $this->bindParams = [];
        }
    }

    /**
     * 获取pdo绑定的参数
     *
     * @return array
     */
    public function getBindParams()
    {
        return $this->bindParams;
    }

    /**
     * 重置所有orm参数及绑定
     *
     * @return $this
     */
    public function resetAndClear()
    {
        $this->reset();
        $this->clearBindParams();
        return $this;
    }

    /**
     * SQL语句条件组装
     *
     * @param array $arr 要组装的数组
     *
     * @return string
     */
    protected function arrToCondition($arr)
    {
        $s = $p = '';
        $params = [];
        foreach ($arr as $k => $v) {
            $k = $this->formatColumnKey($k);
            if (is_array($v)) { //自增或自减
                switch (key($v)) {
                    case '+':
                    case 'inc':
                        $p = "{$k}= {$k} + " . abs(current($v));
                        break;
                    case '-':
                    case 'dec':
                        $p = "{$k}= {$k} - " . abs(current($v));
                        break;
                    case 'func':
                        $func = strtoupper(key(current($v)));
                        $funcParams = current(current($v));
                        foreach ($funcParams as $key => $val) {
                            if (substr($val, 0, 1) !== '`') {
                                $funcParams[$key] = '%s';
                                $params[] = $val;
                            }
                        }
                        $p = "{$k} = {$func}(" . implode($funcParams, ',') . ')';
                        break;
                    case 'column':
                        $p = "{$k} = " . $this->formatColumnKey(current($v));
                        break;
                    case 'raw':
                        $p = "{$k} = " . addslashes(current($v));//flags = (flags | 2) ^ 3
                        break;
                    default ://计算类型
                        $conKey = $this->formatColumnKey(key($v));
                        if (!in_array(key(current($v)), ['+', '-', '*', '/', '%', '^', '&', '|', '<<', '>>', '~'])) {
                            throw new InvalidArgumentException(Lang::get('_PARSE_UPDATE_SQL_PARAMS_ERROR_'));
                        }
                        $p = "{$k} = {$conKey}" . key(current($v)) . abs(current(current($v)));
                        break;
                }
            } else {
                $p = $this->formatColumnKey($k) . "= %s";
                $params[] = $v;
            }

            $s .= (empty($s) ? '' : ',') . $p;
        }
        $this->bindParams = array_merge($params, $this->bindParams);
        return $s;
    }

    /**
     * SQL语句条件组装
     *
     * @param string $key eg: 'forum-fid-1-uid-2'
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param bool $noCondition 是否为无条件操作  set/delete/update操作的时候 condition为空是正常的不报异常
     * @param bool $noTable 是否可以没有数据表 当delete/update等操作的时候已经执行了table() table为空是正常的
     *
     * @return array eg: ['forum', "id = '1' AND uid= '2'"]
     */
    protected function parseKey($key, $and = true, $noCondition = false, $noTable = false)
    {
        $condition = '';
        $arr = explode('-', $key);
        $len = count($arr);

        for ($i = 1; $i < $len; $i += 2) {
            isset($arr[$i + 1]) && $condition .= ($condition ? ($and ? ' AND ' : ' OR ') : '') . $this->formatColumnKey($arr[$i]) . " = %s";
            $this->bindParams[] = $arr[$i + 1];
        }
        $table = strtolower($arr[0]);
        if (empty($table) && !$noTable) {
            throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'table'));
        }
        if (empty($condition) && !$noCondition) {
            throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'condition'));
        }
        empty($condition) || $condition = "($condition)";
        return [$table, $condition];
    }

    /**
     * 根据表名获取cache版本号
     *
     * @param string $table
     *
     * @return mixed
     */
    public function getCacheVer($table)
    {
        if (!$this->openCache) {
            return '';
        }

        $version = Model::getInstance()->cache()->get($this->conf['mark'] . '_db_cache_version_' . $table);
        if (!$version) {
            $version = microtime(true);
            Model::getInstance()->cache()->set($this->conf['mark'] . '_db_cache_version_' . $table, $version, $this->conf['cache_expire']);
        }
        return $version;
    }

    /**
     * 设置cache版本号
     *
     * @param string $table
     */
    public function setCacheVer($table)
    {
        if (!$this->openCache) {
            return;
        }

        $isOpenEmergencyMode = Config::get('emergency_mode_not_real_time_refresh_mysql_query_cache');
        if ($isOpenEmergencyMode !== false && $isOpenEmergencyMode > 0) {//开启了紧急模式
            $expireTime = Model::getInstance()->cache()->get("emergency_mode_not_real_time_refresh_mysql_query_cache_{$table}");
            if ($expireTime && $isOpenEmergencyMode + $expireTime > time()) {
                return;
            }
            Model::getInstance()->cache()->set("emergency_mode_not_real_time_refresh_mysql_query_cache_{$table}", time(), 3600);
        }

        Model::getInstance()->cache()->set($this->conf['mark'] . '_db_cache_version_' . $table, microtime(true), $this->conf['cache_expire']);
    }

    /**
     * 析构函数
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 判断当前查询是否属于实体模型
     *
     * @return bool
     */
    protected function isEntityModel()
    {
        return $this->conf['entity'] instanceof Entity;
    }
}
