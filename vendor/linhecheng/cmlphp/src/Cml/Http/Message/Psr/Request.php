<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 RequestInterface实现
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

/**
 * Psr\Http\Message\RequestInterface实现
 * 代表客户端向服务器发起请求的 HTTP 消息对象。
 *
 * 根据 HTTP 规范，此接口包含以下属性：
 *
 * - HTTP 协议版本号
 * - HTTP 请求方法
 * - URI
 * - 报头信息
 * - 消息内容
 */
class Request
{
    use RequestTrait;

    /**
     * 构造方法
     *
     * @param string $method HTTP 方法
     * @param string|Uri $uri URI
     * @param array $headers 报头信息
     * @param string|resource|Stream|null $body 请求体
     * @param string $version HTTP 版本信息
     */
    public function __construct($method, $uri, array $headers = [], $body = null, $version = '1.1')
    {
        if (!($uri instanceof Uri)) {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }
    }

}
