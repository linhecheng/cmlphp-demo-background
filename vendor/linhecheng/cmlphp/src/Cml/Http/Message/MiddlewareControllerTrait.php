<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 中间件支持trait
 * *********************************************************** */

namespace Cml\Http\Message;

use Cml\Controller;
use Cml\Http\CurlClient;

/**
 * Trait MiddlewareControllerTrait
 *
 * @mixin Controller
 *
 * @package Cml\Http\Message
 */
trait MiddlewareControllerTrait
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * 映射psr7的request和response
     *
     * @param Request $request
     * @param Response $response
     */
    final public function mapPsr7($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * 创建一个 CurlClient 实例
     *
     * @param string $url 请求的地址
     * @param string $requestType 请求的类型
     *
     * @return CurlClient
     */
    public function curlClient($url, $requestType = CurlClient::REQUEST_TYPE_JSON)
    {
        return new CurlClient($url, $requestType);
    }
}