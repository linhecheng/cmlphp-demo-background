<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 覆盖db的方法--不传table
 * *********************************************************** */

namespace Cml\Entity;

/**
 * Trait OverrideDbMethod
 *
 * @mixin Entity
 *
 * @package Cml\Entity
 */
trait OverrideDbMethod
{
    /**
     * 增加一条数据 entity不暴露此方法，使用save或create
     *
     * @param array $data 要新增的数据
     *
     * @return int
     */
    protected function insert($data)
    {
        return $this->db($this->getDbConf())->insert($this->getTableName(), $data, $this->tablePrefix);
    }

    /**
     * 更新数据 entity不暴露此方法，使用save
     *
     * @param $data
     *
     * @return int
     */
    protected function update($data)
    {
        return $this->db($this->getDbConf())->update($this->getTableName(), $data, true, $this->tablePrefix);
    }

    /**
     * 增加多条数据
     *
     * @param array $field 要插入的字段 eg: ['title', 'msg', 'status', 'ctime’]
     * @param array $data 多条数据的值 eg:  [['标题1', '内容1', 1, '2017'], ['标题2', '内容2', 1, '2017']]
     * @param bool $openTransAction 是否开启事务 默认开启
     *
     * @return bool | array
     */
    public function insertMulti($field, $data, $openTransAction = true)
    {
        return $this->db($this->getDbConf())->insertMulti($this->getTableName(), $field, $data, $this->tablePrefix, $openTransAction);
    }

    /**
     * 插入或替换一条记录
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param array $data 插入/更新的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     *
     * @return int
     */
    public function replaceInto(array $data)
    {
        return $this->db($this->getDbConf())->replaceInto($this->getTableName(), $data, $this->tablePrefix);
    }

    /**
     * 插入或更新一条记录，当UNIQUE index or PRIMARY KEY存在的时候更新，不存在的时候插入
     * 若AUTO_INCREMENT存在则返回 AUTO_INCREMENT 的值.
     *
     * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
     * @param array $up 更新的值-会自动merge $data中的数据
     *
     * @return int
     */
    public function upSet(array $data, array $up = [])
    {
        return $this->db($this->getDbConf())->upSet($this->getTableName(), $data, $up, $this->tablePrefix);
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
     * 强制使用索引
     *
     * @param string $index 要强制使用的索引
     *
     * @return static
     */
    public function forceIndex($index)
    {
        $this->db($this->getDbConf())->forceIndex($this->getTableName(), $index, $this->tablePrefix);
        return $this;
    }
}
