<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 路由接口。使用第三方路由必须封装实现本接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 路由驱动抽象接口
 *
 * @package Cml\Interfaces
 */
interface Route
{

    /**
     * 修改解析得到的请求信息 含应用名、控制器、操作
     *
     * @param string $key path|controller|action|root
     * @param string $val
     *
     * @return void
     */
    public function setUrlParams($key = 'path', $val = '');

    /**
     * 获取子目录路径。若项目在子目录中的时候为子目录的路径如/sub_dir/、否则为/
     *
     * @return string
     */
    public function getSubDirName();

    /**
     * 获取应用目录可以是多层目录。如web、admin等.404的时候也必须有值用于绑定系统命令
     *
     * @return string
     */
    public function getAppName();

    /**
     * 获取控制器名称不带Controller后缀
     *
     * @return string
     */
    public function getControllerName();

    /**
     * 获取控制器名称方法名称
     *
     * @return string
     */
    public function getActionName();

    /**
     * 获取不含子目录的完整路径 如: web/Goods/add
     *
     * @return string
     */
    public function getFullPathNotContainSubDir();

    /**
     * 解析url参数
     * 框架在完成必要的启动步骤后。会调用 Cml::getContainer()->make('cml_route')->parseUrl();进行路由地址解析供上述几个方法调用。
     *
     * @return mixed
     */
    public function parseUrl();

    /**
     * 返回要执行的控制器及方法。必须返回一个包含 controller和action键的数组
     * 如:['class' => 'adminbase/Controller/IndexController', 'action' => 'index']
     * 在parseUrl之后框架会根据解析得到的参数去自动载入相关的配置文件然后调用Cml::getContainer()->make('cml_route')->getControllerAndAction();执行相应的方法
     *
     * @return mixed
     */
    public function getControllerAndAction();

    /**
     * 增加get访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function get($pattern, $action);

    /**
     * 增加post访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function post($pattern, $action);

    /**
     * 增加put访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function put($pattern, $action);

    /**
     * 增加patch访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function patch($pattern, $action);

    /**
     * 增加delete访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function delete($pattern, $action);

    /**
     * 增加options访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function options($pattern, $action);

    /**
     * 增加任意访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function any($pattern, $action);

    /**
     * 增加REST方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public function rest($pattern, $action);

    /**
     * 分组路由
     *
     * @param string $namespace 分组名
     * @param callable $func 闭包
     */
    public function group($namespace, callable $func);
}
