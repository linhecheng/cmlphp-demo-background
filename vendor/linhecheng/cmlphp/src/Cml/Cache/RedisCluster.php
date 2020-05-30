<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 RedisCluster集群缓存驱动
 * *********************************************************** */

namespace Cml\Cache;

use Cml\Config;
use Cml\Exception\PhpExtendNotInstallException;
use Cml\Lang;
use Cml\Lock;
use Cml\Log;
use Cml\Model;
use RedisCluster as RedisClusterDriver;
use RuntimeException;


/**
 * Redis缓存驱动
 *
 * @package Cml\Cache
 */
class RedisCluster extends namespace\Base
{
    /**
     * @var RedisCluster
     */
    private $redis = null;

    /**
     * 使用的缓存配置 默认为使用default_cache配置的参数
     *
     * @param array $conf
     */
    public function __construct($conf)
    {
        $this->conf = $conf ? $conf : Config::get('default_cache');

        if (!extension_loaded('redis')) {
            throw new PhpExtendNotInstallException(Lang::get('_CACHE_EXTEND_NOT_INSTALL_', 'Redis'));
        }

        if (!$this->redis) {
            try {
                $this->redis = new RedisClusterDriver(null, $this->conf['server'], 1, 1, true, $this->conf['password']);

                $this->redis->setOption(RedisClusterDriver::OPT_PREFIX, $this->conf['prefix']);
                $this->redis->setOption(RedisClusterDriver::OPT_READ_TIMEOUT, -1);
            } catch (\Exception $e) {
                Log::emergency('RedisCluster', [$e->getMessage()]);
                $this->redis = Model::staticCache('back_cache')->getInstance('xx');
            }

        }
        return $this->redis;
    }


    /**
     * 根据key取值
     *
     * @param mixed $key 要获取的缓存key
     *
     * @return bool | array
     */
    public function get($key)
    {
        $return = json_decode($this->redis->get($key), true);
        is_null($return) && $return = false;
        return $return; //orm层做判断用
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
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($expire > 0) {
            return $this->redis->setex($key, $expire, $value);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    /**
     * 更新对象
     *
     * @param mixed $key 要更新的数据的key
     * @param mixed $value 要更新缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool|int
     */
    public function update($key, $value, $expire = 0)
    {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($expire > 0) {
            return $this->redis->set($key, $value, ['xx', 'ex' => $expire]);
        } else {
            return $this->redis->set($key, $value, ['xx']);
        }
    }

    /**
     * 删除对象
     *
     * @param mixed $key 要删除的数据的key
     *
     * @return bool
     */
    public function delete($key)
    {
        return $this->redis->del($key);
    }

    /**
     * 清洗已经存储的所有元素
     *
     */
    public function truncate()
    {
        foreach ($this->conf['server'] as $key => $val) {
            $instance = new \Redis();
            if ($instance->pconnect($val['host'], $val['port'], 1.5)) {
                $val['password'] && $instance->auth($val['password']);
            } else {
                throw new RuntimeException(Lang::get('_CACHE_NEW_INSTANCE_ERROR_', 'Redis'));
            }
            $instance->flushDB();
            $instance->close();
        }
        return true;
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
        return $this->redis->incrBy($key, abs(intval($val)));
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
        return $this->redis->decrBy($key, abs(intval($val)));
    }

    /**
     * 判断key值是否存在
     *
     * @param mixed $key 要判断的缓存的数据的key
     *
     * @return mixed
     */
    public function exists($key)
    {
        return $this->redis->exists($key);
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @param string $key
     *
     * @return RedisClusterDriver
     */
    public function getInstance($key = '')
    {
        return $this->redis;
    }

    /**
     * 定义析构方法。不用判断长短连接，长链接执行close无效
     *
     */
    public function __destruct()
    {
        Lock::getLocker()->__destruct();//防止在lock gc之前 cache已经发生gc
        $this->redis->close();
    }
}
