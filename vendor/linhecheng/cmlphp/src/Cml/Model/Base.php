<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统Model基础实现
 * *********************************************************** */

namespace Cml\Model;

use Cml\Cml;
use Cml\Config;
use Cml\Db\Base as DbBase;
use Cml\Interfaces\Cache;
use Cml\Interfaces\Db;
use PDO;

/**
 * 基础Model类，在CmlPHP中负责数据的存取(目前包含db/cache)
 *
 * @method string|array buildSql($offset = null, $limit = null, $isSelect = false) 构建sql
 * @method int delete() 删除数据
 * @method int count($field = '*', $isMulti = false, $useMaster = false) 获取 COUNT(字段名或*) 的结果
 * @method float max($field = '*', $isMulti = false, $useMaster = false) 获取 MAX(字段名或*) 的结果
 * @method float min($field = '*', $isMulti = false, $useMaster = false) 获取 MIN(字段名或*) 的结果
 * @method float sum($field = '*', $isMulti = false, $useMaster = false) 获取 SUM(字段名或*) 的结果
 * @method float avg($field = '*', $isMulti = false, $useMaster = false) 获取 AVG(字段名或*) 的结果
 *
 * @package Cml
 */
trait Base
{
    /**
     * 快捷方法-读是否强制使用主库
     *
     * @var bool
     */
    protected $useMaster = false;

    /**
     * 查询数据缓存时间
     *
     *  表数据有变动会自动更新缓存。设置为0表示表数据没变动时缓存不过期。
     * 这边设置为3600意思是即使表数据没变动也让缓存每3600s失效一次,这样可以让缓存空间更合理的利用.
     * 如果不想启用缓存直接配置为false
     * 默认为null： 使用 db配置中的cache_expire
     *
     * @var mixed
     */
    protected $cacheExpire = null;

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
     * 主键-不设置则自动从缓存获取
     *
     * @var null
     */
    protected $primaryKey = null;

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
     * pdo获取数据的方式，默认返回数组
     */
    protected $pdoFetchStyle = PDO::FETCH_ASSOC;

    /**
     * 获取db实例
     *
     * @param string $conf 使用的数据库配置;
     *
     * @return Db | DbBase
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
        $config['pdo_fetch_style'] = $this->pdoFetchStyle;
        $config['entity'] = $this;
        $config['modelTablePrefix'] = $this->tablePrefix;

        if (isset($this->dbInstance[$conf])) {
            return $this->dbInstance[$conf];
        } else {
            $pos = strpos($config['driver'], '.');
            is_null($this->cacheExpire) || $config['cache_expire'] = $this->cacheExpire;
            $this->dbInstance[$conf] = Cml::getContainer()->make('db_' . strtolower($pos ? substr($config['driver'], 0, $pos) : $config['driver']), [$config]);
            return $this->dbInstance[$conf];
        }
    }

    /**
     * clone一个自身用于复杂条件
     *
     * @return $this
     */
    public function cloneSelf()
    {
        return clone $this;
    }

    /**
     * 当程序连接N个db的时候用于释放于用连接以节省内存
     *
     * @param string $conf 使用的数据库配置;
     */
    public function closeDb($conf = 'default_db')
    {
        //$this->db($conf)->close();释放对象时会执行析构回收
        unset($this->dbInstance[$conf]);
    }

    /**
     * 设置查询数据缓存时间
     *
     *  表数据有变动会自动更新缓存。设置为0表示表数据没变动时缓存不过期。
     * 这边设置为3600意思是即使表数据没变动也让缓存每3600s失效一次,这样可以让缓存空间更合理的利用.
     * 如果不想启用缓存直接配置为false
     * 默认为null： 使用 db配置中的cache_expire
     * @param mixed $cacheExpire
     *
     * @return static
     */
    public function setCacheExpire($cacheExpire = null)
    {
        $this->cacheExpire = $cacheExpire;

        return $this;
    }

    /**
     * 获取cache实例
     *
     * @param string $conf 使用的缓存配置;
     *
     * @return Cache
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
            self::$cacheInstance[$conf] = Cml::getContainer()->make('cache_' . strtolower($config['driver']), [$config]);
            return self::$cacheInstance[$conf];
        }
    }

    /**
     * 获取一个Model实例
     *
     * @param null|string $table 表名
     * @param null|string $tablePrefix 表前缀
     * @param null|string|array $db db配置，默认default_db
     *
     * @return static
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
     * 获取当前Model的数据库配置串
     *
     * @return string
     */
    public function getDbConf()
    {
        return $this->db;
    }

    /**
     * 兼容旧版本--已经不再需要
     *
     * @return  Db
     * @deprecated
     */
    public function mapDbAndTable()
    {
        return $this->db($this->getDbConf())->table($this->getTableName(), $this->tablePrefix);
    }

    /**
     * 静态方式获取cache实例
     *
     * @param string $conf 使用的缓存配置;
     *
     * @return Cache
     */
    public static function staticCache($conf = 'default_cache')
    {
        return static::getInstance()->cache($conf);
    }

    /**
     * 当访问model中不存在的方法时直接调用$this->db()的相关方法
     *
     * @param $dbMethod
     * @param $arguments
     *
     * @return static|mixed
     */
    public function __call($dbMethod, $arguments)
    {
        $res = call_user_func_array([$this->db($this->getDbConf()), $dbMethod], $arguments);

        if ($res instanceof Db) {
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
     * @return static
     */
    public static function __callStatic($dbMethod, $arguments)
    {
        if ($dbMethod === 'getInstanceAndRunMapDbAndTable') {
            return call_user_func_array(['self', 'getInstance'], $arguments);
        }
        $res = call_user_func_array([static::getInstance()->db(static::getInstance()->getDbConf()), $dbMethod], $arguments);
        if ($res instanceof Db) {
            return static::getInstance();//不是返回数据直接返回model实例
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
     * @return static
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
     * 获取主键
     *
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return mixed
     */
    public function getPrimaryKey($tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->primaryKey ?: $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
    }

    public function __clone()
    {
        foreach ($this->dbInstance as $key => $db) {
            $this->dbInstance[$key] = clone $db;
        }
    }
}
