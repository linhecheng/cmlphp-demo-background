<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr17  HTTP 工厂
 * *********************************************************** */

namespace Cml\Http\Message;

use Cml\Http\Message\Psr\Psr17Factory;

class HttpFactory extends Psr17Factory
{
    /**
     * 创建 服务器端接收到的 HTTP 请求对象
     *
     * @param string $method
     * @param Psr\Uri|string $uri
     * @param array $serverParams
     *
     * @return Psr\ServerRequest|Request
     */
    public function createServerRequest($method, $uri, array $serverParams = [])
    {
        return new Request($method, $uri, [], null, '1.1', $serverParams);
    }


    /**
     * @inheritDoc
     */
    public function createResponse($code = 200, $reasonPhrase = '')
    {
        if (2 > func_num_args()) {
            $reasonPhrase = null;
        }

        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}