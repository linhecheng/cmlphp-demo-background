<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统默认Model
 * *********************************************************** */

namespace Cml;

use Cml\Interfaces\Db;

/**
 * 基础Model类，在CmlPHP中负责数据的存取(目前包含db/cache以及为了简化操作db而封装的快捷方法)
 *
 * 以下方法只是为了方便配合Model中的快捷方法(http://doc.cmlphp.com/devintro/model/mysql/fastmethod/readme.html)使用
 * 并没有列出db中的所有方法。其它未列出的方法建议还是通过$this->db()->xxx使用
 * @method Db|Model where(string | array $column, string | int $value = '')  where条件组装-相等
 * @method Db|Model whereColumn(string $column, string $column2 = '')  where条件组装-两个列相等
 * @method Db|Model whereRaw(string $where, array $params = [])  where条件组装-原生条件
 * @method Db|Model whereNot(string $column, string | int $value)  where条件组装-不等
 * @method Db|Model whereGt(string $column, string | int $value = '')  where条件组装-大于
 * @method Db|Model whereLt(string $column, string | int $value = '')  where条件组装-小于
 * @method Db|Model whereGte(string $column, string | int $value = '')  where条件组装-大于等于
 * @method Db|Model whereLte(string $column, string | int $value = '')  where条件组装-小于等于
 * @method Db|Model whereIn(string $column, array $value) where条件组装-IN
 * @method Db|Model whereNotIn(string $column, array $value) where条件组装-NOT IN
 * @method Db|Model whereLike(string $column, bool $leftBlur = false, string $value, bool $rightBlur = false) where条件组装-LIKE
 * @method Db|Model whereNotLike(string $column, bool $leftBlur = false, string $value, bool $rightBlur = false) where条件组装-NOT LIKE
 * @method Db|Model whereRegExp(string $column, string $value) where条件组装-RegExp
 * @method Db|Model whereBetween(string $column, int $value, int $value2 = null) where条件组装-BETWEEN
 * @method Db|Model whereNotBetween(string $column, int $value, int $value2 = null) where条件组装-NotBetween
 * @method Db|Model whereNull(string $column) where条件组装-IS NULL
 * @method Db|Model whereNotNull(string $column) where条件组装-IS NOT NULL
 * @method Db|Model columns(string | array $columns = '*') 选择列
 * @method Db|Model orderBy(string $column, string $order = 'ASC') 排序
 * @method Db|Model groupBy(string $column) 分组
 * @method Db|Model having(string $column, $operator = '=', $value) 分组
 * @method Db|Model paramsAutoReset(bool $autoReset = true, bool $alwaysClearTable = false, bool $alwaysClearColumns = true) orm参数是否自动重置, 默认在执行语句后会重置orm参数, 包含查询的表、字段信息、条件等信息
 * @method Db|Model noCache() 标记本次查询不使用缓存
 * @method Db|Model table(string |array $table = '', string | null $tablePrefix = null) 定义操作的表
 * @method Db|Model lBrackets() where条件增加左括号
 * @method Db|Model rBrackets() where条件增加右括号
 * @method Db|Model join(string | array $table, string $on, string | null $tablePrefix = null) join内联结
 * @method Db|Model leftJoin(string | array $table, string $on, string | null $tablePrefix = null) leftJoin左联结
 * @method Db|Model rightJoin(string | array $table, string $on, string | null $tablePrefix = null) rightJoin右联结
 * @method Db|Model _and(callable $callable = null) and条件操作
 * @method Db|Model _or(callable $callable = null) or条件操作
 *
 * @package Cml
 */
class Model
{
    /**
     * 快捷方法-读是否强制使用主库
     *
     * @var bool
     */
    protected $useMaster = false;

    /**
     * 表前缀
     *
     * @var null|string
     */
    protected $tablePrefix = null;

    /**
     * 数据库配置key
     *
     * @var string
     */
    protected $db = 'default_db';

    /**
     * 表名
     *
     * @var null|string
     */
    protected $table = null;

    /**
     * Db驱动实例
     *
     * @var array
     */
    private $dbInstance = [];

    /**
     * Cache驱动实例
     *
     * @var array
     */
    private static $cacheInstance = [];

    /**
     * 获取db实例
     *
     * @param string $conf 使用的数据库配置;
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | \Cml\Db\Base
     */
    public function db($conf = '')
    {
        $conf == '' && $conf = $this->getDbConf();
        if (is_array($conf)) {
            $config = $conf;
            $conf = md5(json_encode($conf));
        } else {
            $config = Config::get($conf);
        }
        $config['mark'] = $conf;

        if (isset($this->dbInstance[$conf])) {
            return $this->dbInstance[$conf];
        } else {
            $pos = strpos($config['driver'], '.');
            $this->dbInstance[$conf] = Cml::getContainer()->make('db_' . strtolower($pos ? substr($config['driver'], 0, $pos) : $config['driver']), $config);
            return $this->dbInstance[$conf];
        }
    }

    /**
     * 当程序连接N个db的时候用于释放于用连接以节省内存
     *
     * @param string $conf 使用的数据库配置;
     */
    public function closeDb($conf = 'default_db')
    {
        //$this->db($conf)->close();释放对象时会执行析构回收
        unset(self::$dbInstance[$conf]);
    }

    /**
     * 获取cache实例
     *
     * @param string $conf 使用的缓存配置;
     *
     * @return \Cml\Cache\Redis | \Cml\Cache\Apc | \Cml\Cache\File | \Cml\Cache\Memcache
     */
    public function cache($conf = 'default_cache')
    {
        if (is_array($conf)) {
            $config = $conf;
            $conf = md5(json_encode($conf));
        } else {
            $config = Config::get($conf);
        }

        if (isset(self::$cacheInstance[$conf])) {
            return self::$cacheInstance[$conf];
        } else {
            if ($config['on']) {
                self::$cacheInstance[$conf] = Cml::getContainer()->make('cache_' . strtolower($config['driver']), $config);
                return self::$cacheInstance[$conf];
            } else {
                throw new \InvalidArgumentException(Lang::get('_NOT_OPEN_', $conf));
            }
        }
    }

    /**
     * 初始化一个Model实例
     *
     * @param null|string $table 表名
     * @param null|string $tablePrefix 表前缀
     * @param null|string|array $db db配置，默认default_db
     *
     * @return \Cml\Model | \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | \Cml\Db\Base | $this
     */
    public static function getInstance($table = null, $tablePrefix = null, $db = null)
    {
        static $mInstance = [];
        $class = get_called_class();
        $classKey = $class . '-' . $tablePrefix . $table;
        if (!isset($mInstance[$classKey])) {
            $mInstance[$classKey] = new $class();
            is_null($table) || $mInstance[$classKey]->table = $table;
            is_null($tablePrefix) || $mInstance[$classKey]->tablePrefix = $tablePrefix;
            is_null($db) || $mInstance[$classKey]->db = $db;
        }
        return $mInstance[$classKey];
    }

    /**
     * 获取表名
     *
     * @param bool $addTablePrefix 是否返回带表前缀的完整表名
     * @param bool $addDbName 是否带上dbname
     *
     * @return string
     */
    public function getTableName($addTablePrefix = false, $addDbName = false)
    {
        if (is_null($this->table)) {
            $tmp = get_class($this);
            $this->table = strtolower(substr($tmp, strrpos($tmp, '\\') + 1, -5));
        }

        $dbName = $addDbName ? Config::get($this->getDbConf() . '.master.dbname') . '.' : '';

        if ($addTablePrefix) {
            $tablePrefix = $this->tablePrefix;
            $tablePrefix || $tablePrefix = Config::get($this->getDbConf() . '.master.tableprefix');
            return $dbName . $tablePrefix . $this->table;
        }
        return $dbName . $this->table;
    }

    /**
     * 通过某个字段获取单条数据-快捷方法
     *
     * @param mixed $val 值
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return bool|array
     */
    public function getByColumn($val, $column = null, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
        return $this->db($this->getDbConf())->table($tableName, $tablePrefix)
            ->where($column, $val)
            ->getOne($this->useMaster);
    }

    /**
     * 通过某个字段获取多条数据-快捷方法
     *
     * @param mixed $val 值
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return bool|array
     */
    public function getMultiByColumn($val, $column = null, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
        return $this->db($this->getDbConf())->table($tableName, $tablePrefix)
            ->where($column, $val)
            ->select(null, null, $this->useMaster);
    }

    /**
     * 增加一条数据-快捷方法
     *
     * @param array $data 要新增的数据
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return int
     */
    public function set($data, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->set($tableName, $data, $tablePrefix);
    }

    /**
     * 增加多条数据-快捷方法
     *
     * @param array $field 要插入的字段 eg: ['title', 'msg', 'status', 'ctime’]
     * @param array $data 多条数据的值 eg:  [['标题1', '内容1', 1, '2017'], ['标题2', '内容2', 1, '2017']]
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     * @param bool $openTransAction 是否开启事务 默认开启
     *
     * @return bool | array
     */
    public function setMulti($field, $data, $tableName = null, $tablePrefix = null, $openTransAction = true)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->setMulti($tableName, $field, $data, $tablePrefix, $openTransAction);
    }

    /**
     * 插入或更新一条记录，当UNIQUE index or PRIMARY KEY存在的时候更新，不存在的时候插入
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param array $up 更新的值-会自动merge $data中的数据
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return int
     */
    public function upSet(array $data, array $up = [], $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->upSet($tableName, $data, $up, $tablePrefix);
    }

    /**
     * 插入或替换一条记录
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param array $data 插入/更新的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return int
     */
    public function replaceInto(array $data, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->replaceInto($tableName, $data, $tablePrefix);
    }

    /**
     * 通过字段更新数据-快捷方法
     *
     * @param int $val 字段值
     * @param array $data 更新的数据
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return bool
     */
    public function updateByColumn($val, $data, $column = null, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
        return $this->db($this->getDbConf())->where($column, $val)
            ->update($tableName, $data, true, $tablePrefix);
    }

    /**
     * 通过主键删除数据-快捷方法
     *
     * @param mixed $val
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return bool
     */
    public function delByColumn($val, $column = null, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
        return $this->db($this->getDbConf())->where($column, $val)
            ->delete($tableName, true, $tablePrefix);
    }

    /**
     * 获取数据的总数
     *
     * @param null $pkField 主键的字段名
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return mixed
     */
    public function getTotalNums($pkField = null, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_null($pkField) && $pkField = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
        return $this->db($this->getDbConf())->table($tableName, $tablePrefix)->count($pkField, false, $this->useMaster);
    }

    /**
     * 获取数据列表
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param string|array $order 传asc 或 desc 自动取主键 或 ['id'=>'desc', 'status' => 'asc']
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return array
     */
    public function getList($offset = 0, $limit = 20, $order = 'DESC', $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_array($order) || $order = [$this->db($this->getDbConf())->getPk($tableName, $tablePrefix) => $order];

        $dbInstance = $this->db($this->getDbConf())->table($tableName, $tablePrefix);
        foreach ($order as $key => $val) {
            $dbInstance->orderBy($key, $val);
        }
        return $dbInstance->limit($offset, $limit)
            ->select(null, null, $this->useMaster);
    }

    /**
     * 以分页的方式获取数据列表
     *
     * @param int $limit 每页返回的条数
     * @param string|array $order 传asc 或 desc 自动取主键 或 ['id'=>'desc', 'status' => 'asc']
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return array
     */
    public function getListByPaginate($limit = 20, $order = 'DESC', $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        is_array($order) || $order = [$this->db($this->getDbConf())->getPk($tableName, $tablePrefix) => $order];

        $dbInstance = $this->db($this->getDbConf())->table($tableName, $tablePrefix);
        foreach ($order as $key => $val) {
            $dbInstance->orderBy($key, $val);
        }
        return $dbInstance->paginate($limit, $this->useMaster);
    }

    /**
     * 获取当前Model的数据库配置串
     *
     * @return string
     */
    public function getDbConf()
    {
        return $this->db;
    }

    /**
     * 自动根据 db属性执行$this->db(xxx)方法; table/tablePrefix属性执行$this->db('xxx')->table('tablename', 'tablePrefix')方法
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | \Cml\Db\Base
     */
    public function mapDbAndTable()
    {
        return $this->db($this->getDbConf())->table($this->getTableName(), $this->tablePrefix);
    }

    /**
     * 获取model实例并同时执行mapDbAndTable
     *
     * @param null|string $table 表名
     * @param null|string $tablePrefix 表前缀
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | \Cml\Db\Base
     */
    public static function getInstanceAndRunMapDbAndTable($table = null, $tablePrefix = null)
    {
        return self::getInstance($table, $tablePrefix)->mapDbAndTable();
    }

    /**
     * 静态方式获取cache实例
     *
     * @param string $conf 使用的缓存配置;
     *
     * @return \Cml\Cache\Redis | \Cml\Cache\Apc | \Cml\Cache\File | \Cml\Cache\Memcache
     */
    public static function staticCache($conf = 'default_cache')
    {
        return self::getInstance()->cache($conf);
    }

    /**
     * 当访问model中不存在的方法时直接调用$this->db()的相关方法
     *
     * @param $dbMethod
     * @param $arguments
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | $this
     */
    public function __call($dbMethod, $arguments)
    {
        $res = call_user_func_array([$this->db($this->getDbConf()), $dbMethod], $arguments);
        if ($res instanceof Interfaces\Db) {
            return $this;//不是返回数据直接返回model实例
        } else {
            return $res;
        }
    }

    /**
     * 当访问model中不存在的方法时直接调用相关model中的db()的相关方法
     *
     * @param $dbMethod
     * @param $arguments
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | self
     */
    public static function __callStatic($dbMethod, $arguments)
    {
        $res = call_user_func_array([static::getInstance()->db(static::getInstance()->getDbConf()), $dbMethod], $arguments);
        if ($res instanceof Interfaces\Db) {
            return self::getInstance();//不是返回数据直接返回model实例
        } else {
            return $res;
        }
    }

    /**
     * 根据条件是否成立执行对应的闭包
     *
     * @param bool $condition 条件
     * @param callable $trueCallback 条件成立执行的闭包
     * @param callable|null $falseCallback 条件不成立执行的闭包
     *
     * @return Db | \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | $this
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
}
