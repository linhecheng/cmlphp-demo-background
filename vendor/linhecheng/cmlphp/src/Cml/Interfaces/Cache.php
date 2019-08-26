<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 缓存驱动抽象接口
 * *********************************************************** */
namespace Cml\Interfaces;

/**
 * 缓存驱动抽象接口
 *
 * @package Cml\Interfaces
 */
interface Cache
{

    /**
     * 使用的缓存配置 默认为使用default_cache配置的参数
     *
     * @param bool ｜array $conf
     */
    public function __construct($conf = false);

    /**
     * 根据key取值
     *
     * @param mixed $key 要获取的缓存key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * 存储对象
     *
     * @param mixed $key 要缓存的数据的key
     * @param mixed $value 要缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0);

    /**
     * 更新对象
     *
     * @param mixed $key 要更新的数据的key
     * @param mixed $value 要更新缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool|int
     */
    public function update($key, $value, $expire = 0);

    /**
     * 删除对象
     *
     * @param mixed $key 要删除的数据的key
     *
     * @return bool
     */
    public function delete($key);

    /**
     * 清洗已经存储的所有元素
     *
     * @return bool
     */
    public function truncate();

    /**
     * 自增
     *
     * @param mixed $key 要自增的缓存的数据的key
     * @param int $val 自增的进步值,默认为1
     *
     * @return bool
     */
    public function increment($key, $val = 1);

    /**
     * 自减
     *
     * @param mixed $key 要自减的缓存的数据的key
     * @param int $val 自减的进步值,默认为1
     *
     * @return bool
     */
    public function decrement($key, $val = 1);

    /**
     * 返回实例便于操作未封装的方法
     *
     * @param string $key
     *
     * @return \Redis | \Memcache | \Memcached
     */
    public function getInstance($key = '');
}
