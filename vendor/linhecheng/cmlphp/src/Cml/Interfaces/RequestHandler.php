<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架  PSR-15 请求处理程序抽象接口 Psr\Http\Server\MiddlewareInterface
 * *********************************************************** */

namespace Cml\Interfaces;

use Cml\Http\Message\Request;
use Cml\Http\Message\Response;

/**
 *  PSR-15 请求处理程序抽象接口
 *
 * 处理服务器请求并返回响应
 * HTTP 请求处理程序处理 HTTP 请求，以便生成 HTTP 相应。
 */
interface RequestHandler
{
    /**
     * 处理服务器请求并返回响应
     * 可以调用其他协助代码来生成响应。
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request);
}