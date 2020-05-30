<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 锁机制Redis驱动
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Model;

/**
 * 锁机制Redis驱动
 *
 * @package Cml\Lock
 */
class Redis extends Base
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
            && $inst->eval('if redis.call("GET", KEYS[1]) == ARGV[1] then return redis.call("EXPIRE", KEYS[1], ' . $this->expire . ') else return 0 end'
                , [$lock, $this->lockCache[$lock]], 1)
        ) {
            return true;
        }
        $unique = uniqid('', true);

        $script = 'if redis.call("SET", KEYS[1], ARGV[1], "nx", "ex", ARGV[2])  then return 1 else return 0 end';
        // eval "return redis.call('SET', KEYS[1], 'bar', 'NX', 'EX', 100)" 1 foo2

        if ($inst->eval($script, [$lock, $unique, $this->expire], 1)) {
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
        } while (!$inst->eval($script, [$lock, $unique, $this->expire], 1));

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
        $script = 'if redis.call("GET", KEYS[1]) == ARGV[1] then return redis.call("DEL", KEYS[1]) else return 0 end';
        $res = Model::getInstance()->cache($this->useCache)->getInstance()->eval($script, [$lock, $this->lockCache[$lock]], 1);

        //Model::getInstance()->cache($this->useCache)->getInstance()->delete($lock);
        $this->lockCache[$lock] = null;//防止gc延迟,判断有误
        unset($this->lockCache[$lock]);
        return $res > 0;
    }
}
