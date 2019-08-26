<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图渲染引擎 视图调度工厂
 * *********************************************************** */

namespace Cml;

/**
 * 视图渲染引擎 视图调度工厂
 *
 * @package Cml
 */
class View
{
    /**
     * 获取渲染引擎
     *
     * @param string $engine 视图引擎 内置html/json/xml/excel
     *
     * @return \Cml\View\Html
     */
    public static function getEngine($engine = null)
    {
        is_null($engine) && $engine = Config::get('view_render_engine');
        return Cml::getContainer()->make('view_' . strtolower($engine));
    }
}
