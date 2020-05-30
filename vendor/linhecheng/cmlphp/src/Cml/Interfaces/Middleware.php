<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架  PSR-15 中间件抽象接口 Psr\Http\Server\MiddlewareInterface
 * *********************************************************** */

namespace Cml\Interfaces;

use Cml\Http\Message\Request;
use Cml\Http\Message\Response;

/**
 *  PSR-15 中间件抽象接口
 *
 * 参与处理服务器的请求与响应
 * 一个 HTTP 中间件组件参与处理一个 HTTP 的消息:
 * 通过对请求进行操作, 生成相应,或者将请求转发给后续的中间件，并  且可能对它的响应进行操作
 */
interface Middleware
{
    /**
     *  处理传入的服务器请求以产生相应.
     *
     * @param Request $request
     * @param RequestHandler $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandler $handler);
}
