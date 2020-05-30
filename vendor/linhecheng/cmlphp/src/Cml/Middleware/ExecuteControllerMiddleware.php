<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 上午11:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 执行控制器中间件
 * *********************************************************** */

namespace Cml\Middleware;

use Cml\Cml;
use Cml\Config;
use Cml\Debug;
use Cml\Exception\ControllerNotFoundException;
use Cml\Http\Message\HttpFactory;
use Cml\Http\Message\Psr\Stream;
use Cml\Http\Message\Request;
use Cml\Http\Message\Response;
use Cml\Interfaces\Middleware;
use Cml\Interfaces\RequestHandler;
use Cml\Lang;
use Cml\Plugin;

/**
 * 执行控制器中间件
 *
 * @package Cml\Middleware
 */
class ExecuteControllerMiddleware implements Middleware
{
    /**
     * 处理传入的服务器请求以产生相应
     *
     * @param Request $request
     * @param RequestHandler $handler
     *
     * @return Response
     */
    public function process(Request $request = null, RequestHandler $handler = null)
    {
        Plugin::hook('cml.before_run_controller');

        $controllerAction = Cml::getContainer()->make('cml_route')->getControllerAndAction();

        $response = null;

        try {
            if ($controllerAction) {
                Cml::$debug && Debug::addTipInfo(Lang::get('_CML_EXECUTION_ROUTE_IS_', "{$controllerAction['route']}{ {$controllerAction['class']}::{$controllerAction['action']} }", Config::get('url_model')));
                $controller = new $controllerAction['class']();
                if ($request) {
                    $factory = new HttpFactory();
                    $response = $factory->createResponse();

                    if ($request->getMethod() === 'OPTIONS') {
                        return $response;
                    }
                }

                return call_user_func([$controller, "runAppController"], $controllerAction['action'], $request, $response);//运行
            } else {
                throw new ControllerNotFoundException(Lang::get('_CONTROLLER_NOT_FOUND_'));
            }
        } catch (ControllerNotFoundException $e) {
            Cml::montFor404Page();
            if (Cml::$debug) {
                throw $e;
            } else {
                ob_start();
                $tpl = Config::get('404_page');
                is_file($tpl) && Cml::requireFile($tpl);
                $html = ob_get_clean();
                if (!$response) echo $html;//兼容旧业务

                return $response ? $response->withStatus(404)->withBody(Stream::create($html)) : null;
            }
        }
    }
}
