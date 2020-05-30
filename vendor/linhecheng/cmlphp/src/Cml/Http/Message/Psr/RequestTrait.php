<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 请求通用Trait
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use InvalidArgumentException;

/**
 * 请求通用Trait
 */
trait RequestTrait
{
    use MessageTrait;

    /**
     * 请求方法
     *
     * @var string
     */
    private $method;

    /**
     * 请求目标
     *
     * @var string|null
     */
    private $requestTarget;

    /**
     * URI
     *
     * @var Uri|null
     */
    private $uri;

    /**
     * 获取消息的请求目标。
     * 获取消息的请求目标的使用场景，可能是在客户端，也可能是在服务器端，也可能是在指定信息的时候
     * （参阅下方的 `withRequestTarget()`）。
     * 在大部分情况下，此方法会返回组合 URI 的原始形式，除非被指定过（参阅下方的 `withRequestTarget()`）。
     * 如果没有可用的 URI，并且没有设置过请求目标，此方法返回 「/」。
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        if ('' === $target = $this->uri->getPath()) {
            $target = '/';
        }
        if ('' !== $this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * 返回一个指定目标的请求实例。
     *
     * 如果请求需要非原始形式的请求目标——例如指定绝对形式、认证形式或星号形式——则此方法可用于创建指定请求目标的实例。
     *
     * @see [http://tools.ietf.org/html/rfc7230#section-2.7](http://tools.ietf.org/html/rfc7230#section-2.7)（关于请求目标的各种允许的格式）
     *
     * @param mixed $requestTarget
     *
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * 获取当前请求使用的 HTTP 方法
     *
     * @return string HTTP 方法字符串
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 返回更改了请求方法的消息实例。
     *
     * @param string $method 大小写敏感的方法名
     *
     * @return self
     *
     * @throws InvalidArgumentException 当非法的 HTTP 方法名传入时会抛出异常。
     */
    public function withMethod($method)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Method must be a string');
        }

        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * 获取 URI 实例。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @return Uri 返回与当前请求相关的 `UriInterface` 类型的 URI 实例。
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 返回修改了 URI 的消息实例。
     *
     * 你可以通过传入第二个参数来，来干预方法的处理，当 `$preserveHost` 设置为 `true`
     * 的时候，会保留原来的 HOST 信息。当 `$preserveHost` 设置为 `true` 时，此方法
     * 会如下处理 HOST 信息：
     *
     * - 如果 HOST 信息不存在或为空，并且新 URI 包含 HOST 信息，则此方法 **必须** 更新返回请求中的 HOST 信息。
     * - 如果 HOST 信息不存在或为空，并且新 URI 不包含 HOST 信息，则此方法 **不得** 更新返回请求中的 HOST 信息。
     * - 如果HOST 信息存在且不为空，则此方法 **不得** 更新返回请求中的 HOST 信息。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param Uri $uri `UriInterface` 新的 URI 实例
     * @param bool $preserveHost 是否保留原有的 HOST 头信息
     *
     * @return self
     */
    public function withUri(Uri $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    /**
     * 从url更改host
     *
     */
    private function updateHostFromUri()
    {
        if ('' === $host = $this->uri->getHost()) {
            return;
        }

        if (null !== ($port = $this->uri->getPort())) {
            $host .= ':' . $port;
        }

        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $this->headerNames['host'] = $header = 'Host';
        }

        $this->headers = [$header => [$host]] + $this->headers;
    }
}
