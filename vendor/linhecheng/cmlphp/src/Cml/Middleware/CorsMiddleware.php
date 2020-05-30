<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 上午11:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 跨域处理中间件
 * *********************************************************** */

namespace Cml\Middleware;

use Cml\Http\Message\Request;
use Cml\Http\Message\Response;
use Cml\Interfaces\Middleware;
use Cml\Interfaces\RequestHandler;

/**
 * 跨域处理中间件
 *
 * @package Cml\Middleware
 */
class CorsMiddleware implements Middleware
{
    /**
     * 处理传入的服务器请求以产生相应
     *
     * @param Request $request
     * @param RequestHandler $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandler $handler)
    {
        $response = $handler->handle($request);
        if ($request->isCli()) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('origin') ?: '*')
            ->withHeader('Access-Control-Allow-Methods', $request->getHeaderLine('access-control-request-methods') ?: '*')
            ->withHeader('Access-Control-Allow-Credentials', 'true');

        if ($request->getMethod() == 'OPTIONS') {
            $response = $response->withHeader('Access-Control-Allow-Headers', $request->getHeaderLine('access-control-request-headers') ?: 'content-type')
                ->withHeader('Access-Control-Max-Age', 86400 * 7);
        }

        return $response;
    }
}
