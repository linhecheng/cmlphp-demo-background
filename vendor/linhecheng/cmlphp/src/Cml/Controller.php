<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统默认控制器类
 * *********************************************************** */

namespace Cml;

use Cml\Exception\ControllerNotFoundException;
use Cml\Http\Request;
use Cml\Http\Response;
use Cml\Lock\File;
use Cml\Lock\Memcache;
use Cml\Lock\Redis;
use Exception;
use Cml\Http\Message\Response as PsrResponse;

/**
 * 框架基础控制器,所有控制器都要继承该类
 *
 * @package Cml
 */
class Controller
{

    /**
     * @deprecated
     * @var array
     */
    protected $htmlEngineRenderTplArray = [];

    /**
     * 自定义异常处理
     *
     * @param Exception $e
     *
     * @throws Exception
     */
    protected function customHandlerActionException(Exception $e)
    {
        throw $e;
    }

    /**
     * 运行对应的控制器
     *
     * @param string $method 要执行的控制器方法
     * @param Request $request
     * @param Response $response
     *
     * @return void
     * @throws Exception
     *
     */
    final public function runAppController($method, $request = null, $response = null)
    {
        method_exists($this, 'mapPsr7') && $this->mapPsr7($request, $response);

        //检测csrf跨站攻击
        Secure::checkCsrf(Config::get('check_csrf'));

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
            try {
                $response = $this->$method();
                if ($response instanceof PsrResponse) {
                    return $response;
                } elseif (is_array($response)) {
                    if (Request::acceptJson()) {
                        View::getEngine('Json')
                            ->assign($response)
                            ->display();
                    } else {
                        $tpl = isset($this->htmlEngineRenderTplArray[$method])
                            ? $this->htmlEngineRenderTplArray[$method]
                            : Cml::getContainer()->make('cml_route')->getControllerName() . '/' . $method;
                        $tpl = str_replace('\\', '/', $tpl);

                        call_user_func_array([View::getEngine('Html')->assign($response), is_array($tpl) ? 'displayWithLayout' : 'display'], is_array($tpl) ? $tpl : [$tpl]);
                    }
                }
            } catch (Exception $e) {
                $this->customHandlerActionException($e);
            }
        } else {
            throw new ControllerNotFoundException(Lang::get('_ACTION_NOT_FOUND_', $method));
        }
    }

    /**
     * 获取模型方法
     *
     * @return Model
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
     * @return Redis | Memcache | File | false
     * @throws Exception
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
