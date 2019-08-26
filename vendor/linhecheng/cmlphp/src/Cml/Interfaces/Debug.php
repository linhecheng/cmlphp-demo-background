<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Debug 抽象接口
 * *********************************************************** */
namespace Cml\Interfaces;

/**
 * Debug 抽象接口
 *
 * @package Cml\Interfaces
 */
interface Debug
{
    /**
     * 程序执行完毕,打印CmlPHP运行信息
     * 相关信息通过 \Cml\Debug获取
     * \Cml\Debug::getIncludeLib();获取框架载入的类文件
     * \Cml\Debug::getIncludeFiles();获取框架载入的模板文件缓存文件等
     * \Cml\Debug::getTipInfo();获取框架提示信息
     * \Cml\Debug::getSqls();获取orm执行的sql语句
     * \Cml\Debug::getUseTime();获取程序运行耗时
     * \Cml\Debug::getUseMemory();获取程序运行耗费的内存
     *
     */
    public function stopAndShowDebugInfo();
}
