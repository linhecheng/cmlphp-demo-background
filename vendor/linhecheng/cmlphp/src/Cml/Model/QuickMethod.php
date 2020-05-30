<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Db 快捷方法
 * *********************************************************** */

namespace Cml\Model;

use Cml\Model;

/**
 * Trait QuickMethod
 *
 * @see 快捷方法(http://doc.cmlphp.com/devintro/model/mysql/fastmethod/readme.html)
 * @mixin Model
 *
 * @package Cml\Db
 */
trait QuickMethod
{
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
        is_null($column) && $column = $this->getPrimaryKey($tableName, $tablePrefix);
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
        is_null($column) && $column = $this->getPrimaryKey($tableName, $tablePrefix);
        return $this->db($this->getDbConf())->table($tableName, $tablePrefix)
            ->where($column, $val)
            ->select(null, null, $this->useMaster);
    }

    /**
     * @deprecated 请使用insert方法
     *
     * @param array $data 要新增的数据
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return int
     */
    public function set($data, $tableName = null, $tablePrefix = null)
    {
        return $this->insert($data, $tableName, $tablePrefix);
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
    public function insert($data, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->insert($tableName, $data, $tablePrefix);
    }

    /**
     * @deprecated 请使用insertMulti方法
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
        return $this->insertMulti($field, $data, $tableName, $tablePrefix, $openTransAction);
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
    public function insertMulti($field, $data, $tableName = null, $tablePrefix = null, $openTransAction = true)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->insertMulti($tableName, $field, $data, $tablePrefix, $openTransAction);
    }

    /**
     * 插入或更新一条记录，当UNIQUE index or PRIMARY KEY存在的时候更新，不存在的时候插入
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param array $up 更新的值-会自动merge $data中的数据
     * @param array $upIgnoreField 更新的时候要忽略的的字段
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return int
     */
    public function upSet(array $data, array $up = [], $upIgnoreField = [], $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->upSet($tableName, $data, $up, $tablePrefix, $upIgnoreField);
    }

    /**
     * 插入或替换多条记录
     *
     * @param array $field 要插入的字段 eg: ['title', 'msg', 'status', 'ctime’]
     * @param array $data 多条数据的值 eg:  [['标题1', '内容1', 1, '2017'], ['标题2', '内容2', 1, '2017']]
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     * @param mixed $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     * @param bool $openTransAction 是否开启事务 默认开启
     *
     * @return bool | array
     */
    public function replaceMulti($field, $data, $tableName = null, $tablePrefix = null, $openTransAction = true)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        return $this->db($this->getDbConf())->replaceMulti($tableName, $field, $data, $tablePrefix, $openTransAction);
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
        is_null($column) && $column = $this->getPrimaryKey($tableName, $tablePrefix);
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
        is_null($column) && $column = $this->getPrimaryKey($tableName, $tablePrefix);
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
        is_null($pkField) && $pkField = $this->getPrimaryKey($tableName, $tablePrefix);
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
        is_array($order) || $order = [$this->getPrimaryKey($tableName, $tablePrefix) => $order];

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
        is_array($order) || $order = [$this->getPrimaryKey($tableName, $tablePrefix) => $order];

        $dbInstance = $this->db($this->getDbConf())->table($tableName, $tablePrefix);
        foreach ($order as $key => $val) {
            $dbInstance->orderBy($key, $val);
        }
        return $dbInstance->paginate($limit, $this->useMaster);
    }

    /**
     * 强制使用索引
     *
     * @param string $index 要强制使用的索引
     * @param string | null $tableName 要强制索引的表名(不带前缀) 不传会自动从当前Model中$table属性获取
     * @param string | null $tablePrefix 表前缀 不传会自动从当前Model中$tablePrefix属性获取再没有则获取配置中配置的前缀
     *
     * @return static
     */
    public function forceIndex($index, $tableName = null, $tablePrefix = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

        $this->db($this->getDbConf())->forceIndex($tableName, $index, $tablePrefix);
        return $this;
    }
}
