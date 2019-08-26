<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 队列驱动抽象接口
 * *********************************************************** */
namespace Cml\Interfaces;

/**
 * 队列驱动抽象接口
 *
 * @package Cml\Interfaces
 */
interface Queue
{
    /**
     * 从列表头入队
     *
     * @param string $name 要从列表头入队的队列的名称
     * @param mixed $data 要入队的数据
     *
     * @return mixed
     */
    public function lPush($name, $data);

    /**
     * 从列表头出队
     *
     * @param string $name 要从列表头出队的队列的名称
     *
     * @return mixed
     */
    public function lPop($name);

    /**
     * 从列表尾入队
     *
     * @param string $name 要从列表尾入队的队列的名称
     * @param mixed $data 要入队的数据
     *
     * @return mixed
     */
    public function rPush($name, $data);

    /**
     * 从列表尾出队
     *
     * @param string $name 要从列表尾出队的队列的名称
     *
     * @return mixed
     */
    public function rPop($name);
}
