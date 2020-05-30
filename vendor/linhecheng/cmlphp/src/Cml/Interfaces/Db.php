<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 缓存驱动抽象接口
 * *********************************************************** */

namespace Cml\Interfaces;

use Cml\Entity\Collection;
use Generator;
use InvalidArgumentException;

/**
 * Orm 数据库抽象接口
 *
 * @package Cml\Interfaces
 */
interface Db
{
    /**
     * Db constructor.
     *
     * @param $conf
     */
    public function __construct($conf);

    /**
     * 定义操作的表
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return $this
     */
    public function table($table = '', $tablePrefix = null);

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    public function getTables();

    /**
     * 获取当前数据库中所有表的信息
     *
     * @return array
     */
    public function getAllTableStatus();

    /**
     * 获取表字段
     *
     * @param string $table 表名
     * @param mixed $tablePrefix 表前缀，不传则获取配置中配置的前缀
     * @param int $filter 0 获取表字段详细信息数组 1获取字段以,号相隔组成的字符串
     *
     * @return mixed
     */
    public function getDbFields($table, $tablePrefix = null, $filter = 0);


    /**
     * 根据key取出数据
     *
     * @deprecated
     *
     * @param string $key get('user-uid-123');
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param bool|string $useMaster 是否使用主库 默认读取从库 此选项为字符串时为表前缀$tablePrefix
     * @param null|string $tablePrefix 表前缀
     *
     * @return array|Collection
     */
    public function get($key, $and = true, $useMaster = false, $tablePrefix = null);

    /**
     * 根据key 新增 一条数据
     *
     * @param string $table
     * @param array $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return bool|int
     */
    public function insert($table, $data, $tablePrefix = null);

    /**
     * 新增多条数据
     *
     * @param string $table
     * @param array $field 字段 eg: ['title', 'msg', 'status', 'ctime‘]
     * @param array $data eg: 多条数据的值 [['标题1', '内容1', 1, '2017'], ['标题2', '内容2', 1, '2017']]
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     * @param bool $openTransAction 是否开启事务 默认开启
     * @return bool|array
     * @throws InvalidArgumentException
     *
     */
    public function insertMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true);

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
    public function replaceInto($table, array $data, $tablePrefix = null);

    /**
     * 插入或更新一条记录
     *
     * @param string $table 表名
     * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param array $up 更新的值-会自动merge $data中的数据
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     * @param array $upIgnoreField 更新的时候要忽略的的字段
     *
     * @return int
     */
    public function upSet($table, array $data, array $up = [], $tablePrefix = null, $upIgnoreField = []);

    /**
     * 更新数据
     *
     * @param string|array $key eg: 'user'(表名)、'user-uid-$uid'(表名+条件) 、['xx'=>'xx' ...](即:$data数组如果条件是通用whereXX()、表名是通过table()设定。这边可以直接传$data的数组)
     * @param array | null $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com'] 可以直接通过$key参数传递
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return int
     */
    public function update($key, $data = null, $and = true, $tablePrefix = null);

    /**
     * 根据key值删除数据
     *
     * @param string $key eg: 'user'(表名，即条件通过where()传递)、'user-uid-$uid'(表名+条件)、啥也不传(即通过table传表名)
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return int
     */
    public function delete($key = '', $and = true, $tablePrefix = null);

    /**
     * 根据表名删除数据
     *
     * @param string $tableName 要清空的表名
     *
     * @return boolean
     */
    public function truncate($tableName);

    /**
     * 构建sql
     *
     * @param null $offset 偏移量
     * @param null $limit 返回的条数
     * @param bool $isSelect 是否为select调用， 是则不重置查询参数并返回cacheKey/否则直接返回sql并重置查询参数
     *
     * @return string|array
     */
    public function buildSql($offset = null, $limit = null, $isSelect = false);

    /**
     * 获取多条数据
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     * @param mixed $fieldAsKey 返回以某个字段做为key的数组
     *
     * @return array|Collection
     */
    public function select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false);


    /**
     * 返回一个迭代器
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return Generator
     */
    public function cursor($offset = null, $limit = null, $useMaster = false);

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
    public function paginate($limit, $useMaster = false, $page = null, $fieldAsKey = false);

    /**
     * 获取表主键
     *
     * @param string $table 要获取主键的表名
     * @param string $tablePrefix 表前缀
     *
     * @return string || false
     */
    public function getPk($table, $tablePrefix = null);

    /**
     * 获取一条数据
     *
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return array | bool
     */
    public function getOne($useMaster = false);

    /**
     * 获取一列
     *
     * @param string $column 列名
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return bool|mixed
     */
    public function getOneValue($column, $useMaster = false);

    /**
     * 获取数据列值列表
     *
     * @param string $column 列名
     * @param null $key 返回数组中为列值指定自定义键（该自定义键必须是该表的其它字段列名）
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return array
     */
    public function pluck($column, $key = null, $limit = null, $useMaster = false);

    /**
     * 组块结果集-此方法前调用paramsAutoReset无效
     *
     * @param int $num 每次获取的条数
     * @param callable $func 结果集处理函数
     */
    public function chunk($num = 100, callable $func);

    /**
     * where条件组装 相等
     *
     * @param string|array $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名) 当$column为数组时 批量设置
     * @param string |int $value 当$column为数组时  此时$value为false时条件为or 否则为and
     *
     * @return $this
     */
    public function where($column, $value = '');

    /**
     * where条件组装 两个列相等
     *
     * @param string $column eg：username | user.username
     * @param string $column2 eg: nickname | user.nickname
     *
     * @return $this
     */
    public function whereColumn($column, $column2);

    /**
     * where条件原生条件
     *
     * @param string $where eg：utime > ctime + ?
     * @param array $params eg: [10]
     *
     * @return $this
     */
    public function whereRaw($where, $params);

    /**
     * 根据条件是否成立执行对应的闭包
     *
     * @param bool $condition 条件
     * @param callable $trueCallback 条件成立执行的闭包
     * @param callable|null $falseCallback 条件不成立执行的闭包
     *
     * @return $this
     */
    public function when($condition, callable $trueCallback, callable $falseCallback = null);

    /**
     * where条件组装 不等
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereNot($column, $value);

    /**
     * where条件组装 大于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereGt($column, $value);

    /**
     * where条件组装 小于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereLt($column, $value);

    /**
     * where条件组装 大于等于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereGte($column, $value);

    /**
     * where条件组装 小于等于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereLte($column, $value);

    /**
     * where条件组装 in
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array $value
     *
     * @return $this
     */
    public function whereIn($column, $value);

    /**
     * where条件组装 not in
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array $value [1,2,3]
     *
     * @return $this
     */
    public function whereNotIn($column, $value);

    /**
     * where条件组装 REGEXP
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereRegExp($column, $value);

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
    public function whereLike($column, $leftBlur = false, $value, $rightBlur = false);

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
    public function whereNotLike($column, $leftBlur = false, $value, $rightBlur = false);


    /**
     * where条件组装 BETWEEN
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int | array $value
     * @param string |int | null $value2
     *
     * @return $this
     */
    public function whereBetween($column, $value, $value2 = null);

    /**
     * where条件组装 NOT BETWEEN
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int | array $value
     * @param string |int | null $value2
     *
     * @return $this
     */
    public function whereNotBetween($column, $value, $value2 = null);

    /**
     * where条件组装 IS NULL
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *
     * @return $this
     */
    public function whereNull($column);

    /**
     * where条件组装 IS NOT NULL
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *
     * @return $this
     */
    public function whereNotNull($column);

    /**
     * where 语句组装工厂
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array|int|string $value 值
     * @param string $operator 操作符
     *
     * @return $this
     */
    public function conditionFactory($column, $value, $operator = '=');

    /**
     * 增加 and条件操作符
     *
     * @param callable $callable 如果传入函数则函数内执行的条件会被()包围
     *
     * @return $this
     */
    public function _and(callable $callable = null);

    /**
     * 增加or条件操作符
     *
     * @param callable $callable 如果传入函数则函数内执行的条件会被()包围
     *
     * @return $this
     */
    public function _or(callable $callable = null);

    /**
     * where条件增加左括号
     *
     * @return $this
     */
    public function lBrackets();

    /**
     * where条件增加右括号
     *
     * @return $this
     */
    public function rBrackets();

    /**
     * 选择列
     *
     * @param string|array $columns 默认选取所有 ['id, 'name']
     * 选取id,name两列，['article.id' => 'aid', 'article.title' =>　'article_title'] 别名
     *
     * @return $this
     */
    public function columns($columns = '*');

    /**
     * LIMIT
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     *
     * @return $this
     */
    public function limit($offset = 0, $limit = 10);

    /**
     * 排序
     *
     * @param string $column 要排序的字段
     * @param string $order 方向,默认为正序
     *
     * @return $this
     */
    public function orderBy($column, $order = 'ASC');

    /**
     * 分组
     *
     * @param string $column 要设置分组的字段名
     *
     * @return $this
     */
    public function groupBy($column);

    /**
     * having语句
     *
     * @param string $column 字段名
     * @param string $operator 操作符
     * @param string $value 值
     *
     * @return $this
     */
    public function having($column, $operator = '=', $value);

    /**
     * join内联结
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function join($table, $on, $tablePrefix = null);

    /**
     * leftJoin左联结
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function leftJoin($table, $on, $tablePrefix = null);

    /**
     * rightJoin右联结
     *
     * @param string|array $table 表名 要取别名时使用 [不带前缀表名 => 别名]
     * @param string $on 联结的条件 如：'c.cid = a.cid'
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function rightJoin($table, $on, $tablePrefix = null);

    /**
     * union联结
     *
     * @param string|array $sql 要union的sql
     * @param bool $all 是否为union all
     *
     * @return $this
     */
    public function union($sql, $all = false);

    /**
     * 获取 COUNT(字段名或*) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool $isMulti 结果集是否为多条 默认只有一条
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function count($field = '*', $isMulti = false, $useMaster = false);

    /**
     * 数据是否存在
     *
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function exists($useMaster = false);

    /**
     * 数据是否不存在
     *
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function doesntExist($useMaster = false);

    /**
     * 获取 MAX(字段名或*) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function max($field = '*', $isMulti = false, $useMaster = false);

    /**
     * 获取 MIN(字段名或*) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function min($field = '*', $isMulti = false, $useMaster = false);

    /**
     * 获取 SUM(字段名或*) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function sum($field = '*', $isMulti = false, $useMaster = false);

    /**
     * 获取 AVG(字段名或*) 的结果
     *
     * @param string $field 要统计的字段名
     * @param bool|string $isMulti 结果集是否为多条 默认只有一条。传字符串时相当于执行了 groupBy($isMulti)
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function avg($field = '*', $isMulti = false, $useMaster = false);

    /**
     * 强制使用索引
     *
     * @param string $table 要强制索引的表名(不带前缀)
     * @param string $index 要强制使用的索引
     * @param string $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return $this
     */
    public function forceIndex($table, $index, $tablePrefix = null);

    /**
     * 返回INSERT，UPDATE 或 DELETE 查询所影响的记录行数。
     *
     * @param resource $handle mysql link
     * @param int $type 执行的类型1:insert、2:update、3:delete
     *
     * @return int
     */
    public function affectedRows($handle, $type);

    /**
     *获取上一INSERT的主键值
     *
     * @param resource $link
     *
     * @return int
     */
    public function insertId($link = null);

    /**
     * 指定字段的值+1
     *
     * @param string $key 操作的key eg: user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     * @param mixed $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return bool
     */
    public function increment($key, $val = 1, $field = null, $tablePrefix = null);

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
    public function decrement($key, $val = 1, $field = null, $tablePrefix = null);

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
    public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false);

    /**
     * 析构函数
     *
     */
    public function __destruct();

    /**
     * 获取数据库 版本
     *
     * @param resource $link
     *
     * @return string
     */
    public function version($link = null);

    /**
     * 开启事务
     *
     * @return bool
     */
    public function startTransAction();

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit();

    /**
     * 设置一个事务保存点
     *
     * @param string $pointName 保存点名称
     *
     * @return bool
     */
    public function savePoint($pointName);

    /**
     * 回滚事务
     *
     * @param bool $rollBackTo 是否为还原到某个保存点
     *
     * @return bool
     */
    public function rollBack($rollBackTo = false);

    /**
     * 调用存储过程
     * 如 : callProcedure('user_check ?,?  ', [1, 1], true) pdo
     *
     * @param string $procedureName 要调用的存储过程名称
     * @param array $bindParams 绑定的参数
     * @param bool|true $isSelect 是否为返回数据集的语句
     *
     * @return array|int
     */
    public function callProcedure($procedureName = '', $bindParams = [], $isSelect = true);

    /**
     * 关闭连接
     *
     */
    public function close();

}
