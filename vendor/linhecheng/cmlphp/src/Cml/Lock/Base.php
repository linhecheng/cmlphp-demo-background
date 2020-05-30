<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 锁机制驱动抽象类基类
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Config;
use Cml\Interfaces\Lock;

/**
 * 锁驱动抽象类基类
 *
 * @package Cml\Lock
 */
abstract class Base implements Lock
{
    /**
     *  锁驱动使用redis/memcache时使用的缓存
     *
     * @var string
     */
    protected $useCache = '';

    public function __construct($useCache = null)
    {
        $useCache || $useCache = Config::get('locker_use_cache', 'default_cache');
        $this->useCache = $useCache;
    }

    /**
     * 锁的过期时间针对Memcache/Redis两种锁有效,File锁无效 单位s
     * 设为0时不过期。此时假如开发未手动unlock且这时出现程序挂掉的情况 __destruct未执行。这时锁必须人工介入处理
     * 这个值可根据业务需要进行修改比如60等
     *
     * @var int
     */
    protected $expire = 100;

    /**
     * 保存锁数据
     *
     * @var array
     */
    protected $lockCache = [];

    /**
     * 设置锁的过期时间
     *
     * @param int $expire
     *
     * @return $this | \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File
     */
    public function setExpire($expire = 100)
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * 组装key
     *
     * @param string $lock 要上的锁的标识key
     *
     * @return string
     */
    protected function getKey($lock)
    {
        return Config::get('lock_prefix') . $lock;
    }

    /**
     * 上锁
     *
     * @param string $lock 要上的锁的标识key
     * @param bool $wouldBlock 是否堵塞
     *
     * @return bool
     */
    final public function lock($lock, $wouldBlock = false)
    {
        if (empty($lock)) {
            return false;
        }

        return $this->execLock($this->getKey($lock), $wouldBlock);
    }

    /**
     * 加锁的具体实现-每个驱动自行实现原子性加锁
     *
     * @param string $lock 锁的标识key
     * @param bool $wouldBlock 是否堵塞
     *
     * @return bool
     */
    abstract protected function execLock($lock, $wouldBlock = false);

    /**
     * 解锁
     *
     * @param string $lock 锁的标识key
     *
     * @return bool
     */
    final public function unlock($lock)
    {
        $lock = $this->getKey($lock);
        if (isset($this->lockCache[$lock])) {
            return $this->execUnlock($lock);
        } else {
            return false;
        }
    }

    /**
     * 解锁的具体实现-每个驱动自行实现原子性解锁
     *
     * @param string $lock 锁的标识
     *
     * @return bool
     */
    abstract protected function execUnlock($lock);

    /**
     * 定义析构函数 自动释放获得的锁
     *
     */
    public function __destruct()
    {
        foreach ($this->lockCache as $lock => $isMyLock) {
            $this->execUnlock($lock);
        }
    }
}
