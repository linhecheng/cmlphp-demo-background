<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-4-15
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Lock处理类
 * *********************************************************** */

namespace Cml;

use Cml\Lock\Base;

/**
 * Lock处理类提供统一的锁机制
 *
 * @package Cml
 */
class Lock
{
    /**
     * 获取Lock实例
     *
     * @param string|null $useCache 使用的锁的配置
     *
     * @return Base
     */
    public static function getLocker($useCache = null)
    {
        return Cml::getContainer()->make('cml_lock', [$useCache]);
    }

    /**
     * 设置锁的过期时间
     *
     * @param int $expire
     *
     * @return Base
     */
    public static function setExpire($expire = 100)
    {
        return self::getLocker()->setExpire($expire);
    }

    /**
     * 上锁
     *
     * @param string $key 要解锁的锁的key
     * @param bool $wouldBlock 是否堵塞
     *
     * @return mixed
     */
    public static function lock($key, $wouldBlock = false)
    {
        return self::getLocker()->lock($key, $wouldBlock);
    }

    /**
     * 上锁并重试N次-每2000微秒重试一次
     *
     * @param string $key 要解锁的锁的key
     * @param int $reTryTimes 重试的次数
     *
     * @return bool
     */
    public static function lockWait($key, $reTryTimes = 3)
    {
        $reTryTimes = intval($reTryTimes);

        $i = 0;
        while (!self::lock($key)) {
            if (++$i >= $reTryTimes) {
                return false;
            }
            usleep(2000);
        }

        return true;
    }

    /**
     * 解锁
     *
     * @param string $key
     *
     * @return void
     */
    public static function unlock($key)
    {
        self::getLocker()->unlock($key);
    }
}
