<?php namespace Cml\Queue;

/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-02-04 下午20:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 队列Redis驱动
 * *********************************************************** */
use Cml\Config;
use Cml\Model;

/**
 * 队列Redis驱动
 *
 * @package Cml\Queue
 */
class Redis extends Base
{
    private $useCache = '';

    /**
     * Redis队列驱动
     *
     * @param mixed $useCache 使用的缓存配置key,未传则获取redis_queue_use_cache中配置的key
     */
    public function __construct($useCache = false)
    {
        $this->useCache = $useCache ? $useCache : Config::get('redis_queue_use_cache');
    }

    /**
     * 从列表头入队
     *
     * @param string $name 要从列表头入队的队列的名称
     * @param mixed $data 要入队的数据
     *
     * @return mixed
     */
    public function lPush($name, $data)
    {
        return $this->getDriver()->lPush($name, $this->encodeDate($data));
    }

    /**
     * 从列表头出队
     *
     * @param string $name 要从列表头出队的队列的名称
     *
     * @return mixed
     */
    public function lPop($name)
    {
        $data = $this->getDriver()->lPop($name);
        $data && $data = $this->decodeDate($data);
        return $data;
    }

    /**
     * 从列表尾入队
     *
     * @param string $name 要从列表尾入队的队列的名称
     * @param mixed $data 要入队的数据
     *
     * @return mixed
     */
    public function rPush($name, $data)
    {
        return $this->getDriver()->rPush($name, $this->encodeDate($data));
    }

    /**
     * 从列表尾出队
     *
     * @param string $name 要从列表尾出队的队列的名称
     *
     * @return mixed
     */
    public function rPop($name)
    {
        $data = $this->getDriver()->rPop($name);
        $data && $data = $this->decodeDate($data);
        return $data;
    }

    /**
     * 弹入弹出
     *
     * @param string $from 要弹出的队列名称
     * @param string $to 要入队的队列名称
     *
     * @return mixed
     */
    public function rPopLpush($from, $to)
    {
        return $this->getDriver()->rpoplpush($from, $to);
    }

    /**
     * 返回驱动
     *
     * @return \Redis
     */
    private function getDriver()
    {
        return Model::getInstance()->cache($this->useCache)->getInstance();
    }
}
