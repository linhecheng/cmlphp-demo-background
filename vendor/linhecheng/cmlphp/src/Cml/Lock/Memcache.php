<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 锁机制Memcache驱动
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Model;
use Memcached;

/**
 * 锁机制Memcache驱动
 *
 * @package Cml\Lock
 */
class Memcache extends Base
{
    /**
     * 加锁的具体实现-每个驱动自行实现原子性加锁
     *
     * @param string $lock 锁的标识key
     * @param bool $wouldBlock 是否堵塞
     *
     * @return bool
     */
    protected function execLock($lock, $wouldBlock = false)
    {
        $inst = Model::getInstance()->cache($this->useCache)->getInstance();

        if (
            isset($this->lockCache[$lock])
            && $this->lockCache[$lock] == $inst->get($lock)
        ) {
            return true;
        }
        $unique = uniqid('', true);

        $driverType = Model::getInstance()->cache($this->useCache)->getDriverType();
        if ($driverType === 1) { //memcached
            $isLock = $inst->add($lock, $unique, $this->expire);
        } else {//memcache
            $isLock = $inst->add($lock, $unique, 0, $this->expire);
        }
        if ($isLock) {
            $this->lockCache[$lock] = $unique;
            return true;
        }

        //非堵塞模式
        if (!$wouldBlock) {
            return false;
        }

        //堵塞模式
        do {
            usleep(200);

            if ($driverType === 1) { //memcached
                $isLock = $inst->add($lock, $unique, $this->expire);
            } else {//memcache
                $isLock = $inst->add($lock, $unique, 0, $this->expire);
            }
        } while (!$isLock);

        $this->lockCache[$lock] = $unique;
        return true;
    }

    /**
     * 解锁的具体实现-每个驱动自行实现原子性解锁
     *
     * @param string $lock 锁的标识key
     *
     * @return bool
     */
    protected function execUnlock($lock)
    {
        $inst = Model::getInstance()->cache($this->useCache);

        $success = false;
        if ($inst->getDriverType() === 1) { //memcached
            $cas = 0;
            if (defined('Memcached::GET_EXTENDED')) {
                $lockValue = $inst->getInstance()->get($lock, null, Memcached::GET_EXTENDED);
                if (is_array($lockValue)) {
                    $cas = $lockValue['cas'];
                    $lockValue = $lockValue['value'];
                }
            } else {
                $lockValue = $inst->getInstance()->get($lock, null, $cas);
            }
            if ($this->lockCache[$lock] == $lockValue && $inst->getInstance()->cas($cas, $lock, 0, $this->expire)) {
                $success = true;
            }
        } else {//memcache
            $lockValue = $inst->getInstance()->get($lock);
            if ($this->lockCache[$lock] == $lockValue) {
                $success = true;
            }
        }

        if ($success) {
            $inst->getInstance()->delete($lock);
        }
        $this->lockCache[$lock] = null;//防止gc延迟,判断有误
        unset($this->lockCache[$lock]);
        return $success;
    }
}
