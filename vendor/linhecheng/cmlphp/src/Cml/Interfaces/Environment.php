<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-11-15 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 环境解析实现接口
 * *********************************************************** */
namespace Cml\Interfaces;

/**
 * 环境解析实现接口
 *
 * @package Cml\Interfaces
 */
interface Environment
{
    /**
     * 获取当前环境名称
     *
     * @return string
     */
    public function getEnv();
}
