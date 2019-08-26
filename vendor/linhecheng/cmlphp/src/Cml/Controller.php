<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统默认控制器类
 * *********************************************************** */

namespace Cml;

use Cml\Http\Request;
use Cml\Http\Response;

/**
 * 框架基础控制器,所有控制器都要继承该类
 *
 * @package Cml
 */
class Controller
{

    /**
     * 当执行的控制器方法返回数组且http请求头HTTP_ACCEPT为html时。默认渲染的tpl为"控制器名/方法名"
     * 这边配置[请求的控制器方法=>对应渲染的模板]则渲染配置的模板。当[请求的控制器方法=>对应渲染的模板为string]自动调用display方法
     * 当[请求的控制器方法=>对应渲染的模板为 array]自动调用html engine的displayWithLayout方法
     *
     * @var array
     */
    protected $htmlEngineRenderTplArray = [];

    /**
     * 运行对应的控制器
     *
     * @param string $method 要执行的控制器方法
     *
     * @return void
     */
    final public function runAppController($method)
    {
        //检测csrf跨站攻击
        Secure::checkCsrf(Config::get('check_csrf'));

        // 关闭GPC过滤 防止数据的正确性受到影响 在db层防注入
        if (get_magic_quotes_gpc()) {
            Secure::stripslashes($_GET);
            Secure::stripslashes($_POST);
            Secure::stripslashes($_COOKIE);
            Secure::stripslashes($_REQUEST); //在程序中对get post cookie的改变不影响 request的值
        }

        //session保存方式自定义
        if (Config::get('session_user')) {
            Session::init();
        } else {
            ini_get('session.auto_start') || session_start(); //自动开启session
        }

        header('Cache-control: ' . Config::get('http_cache_control'));  // 页面缓存控制

        //如果有子类中有init()方法 执行Init() eg:做权限控制
        if (method_exists($this, "init")) {
            $this->init();
        }

        //根据动作去找对应的方法
        if (method_exists($this, $method)) {
            $response = $this->$method();
            if (is_array($response)) {
                if (Request::acceptJson()) {
                    View::getEngine('Json')
                        ->assign($response)
                        ->display();
                } else {
                    $tpl = isset($this->htmlEngineRenderTplArray[$method])
                        ? $this->htmlEngineRenderTplArray[$method]
                        : Cml::getContainer()->make('cml_route')->getControllerName() . '/' . $method;

                    call_user_func_array([View::getEngine('Html')->assign($response), is_array($tpl) ? 'displayWithLayout' : 'display'], is_array($tpl) ? $tpl : [$tpl]);
                }
            }
        } elseif (Cml::$debug) {
            Cml::montFor404Page();
            throw new \BadMethodCallException(Lang::get('_ACTION_NOT_FOUND_', $method));
        } else {
            Cml::montFor404Page();
            Response::show404Page();
        }
    }

    /**
     * 获取模型方法
     *
     * @return \Cml\Model
     */
    public function model()
    {
        return Model::getInstance();
    }

    /**
     * 获取Lock实例
     *
     * @param string|null $useCache 使用的锁的配置
     *
     * @return \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File | false
     * @throws \Exception
     */
    public function locker($useCache = null)
    {
        return Lock::getLocker($useCache);
    }

    /**
     * 挂载插件钩子
     *
     */
    public function __destruct()
    {
        Plugin::hook('cml.run_controller_end');
    }
}
