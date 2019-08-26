<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Apc缓存驱动
 * *********************************************************** */
namespace Cml\Cache;

use Cml\Config;
use Cml\Exception\PhpExtendNotInstall;
use Cml\Lang;

/**
 * Apc缓存驱动
 *
 * @package Cml\Cache
 */
class Apc extends namespace\Base
{
    /**
     * 使用的缓存配置 默认为使用default_cache配置的参数
     *
     * @param bool ｜array $conf
     *
     * @throws PhpExtendNotInstall
     */
    public function __construct($conf = false)
    {
        if (!function_exists('apc_cache_info')) {
            throw new PhpExtendNotInstall(Lang::get('_CACHE_EXTENT_NOT_INSTALL_', 'Apc'));
        }
        $this->conf = $conf ? $conf : Config::get('default_cache');
    }

    /**
     * 根据key取值
     *
     * @param mixed $key 要获取的缓存key
     *
     * @return mixed
     */
    public function get($key)
    {
        return apc_fetch($this->conf['prefix'] . $key);
    }

    /**
     * 存储对象
     *
     * @param mixed $key 要缓存的数据的key
     * @param mixed $value 要缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        ($expire == 0) && $expire = null;
        return apc_store($this->conf['prefix'] . $key, $value, $expire);
    }

    /**
     * 更新对象
     *
     * @param mixed $key 要更新的缓存的数据的key
     * @param mixed $value 要更新的要缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool|int
     */
    public function update($key, $value, $expire = 0)
    {
        $arr = $this->get($key);
        if (!empty($arr)) {
            $arr = array_merge($arr, $value);
            return $this->set($key, $arr, $expire);
        }
        return 0;
    }

    /**
     * 删除对象
     *
     * @param mixed $key 要删除的缓存的数据的key
     *
     * @return bool
     */
    public function delete($key)
    {
        return apc_delete($this->conf['prefix'] . $key);
    }

    /**
     * 清洗已经存储的所有元素
     *
     */
    public function truncate()
    {
        return apc_clear_cache('user'); //只清除用户缓存
    }

    /**
     * 自增
     *
     * @param mixed $key 要自增的缓存的数据的key
     * @param int $val 自增的进步值,默认为1
     *
     * @return bool
     */
    public function increment($key, $val = 1)
    {
        return apc_inc($this->conf['prefix'] . $key, abs(intval($val)));
    }

    /**
     * 自减
     *
     * @param mixed $key 要自减的缓存的数据的key
     * @param int $val 自减的进步值,默认为1
     *
     * @return bool
     */
    public function decrement($key, $val = 1)
    {
        return apc_dec($this->conf['prefix'] . $key, abs(intval($val)));
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @param string $key
     *
     * @return void
     */
    public function getInstance($key = '')
    {
    }
}
