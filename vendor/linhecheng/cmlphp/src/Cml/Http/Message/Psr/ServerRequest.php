<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 ServerRequestInterface实现
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use InvalidArgumentException;

/**
 * Psr\Http\Message\ServerRequestInterface实现
 *  表示服务器端接收到的 HTTP 请求。
 *
 * @package Cml\Http\Message\Psr
 */
class ServerRequest extends Request
{
    /**
     * 派生属性
     *
     * @var array
     */
    private $attributes = [];

    /**
     * cookie
     *
     * @var array
     */
    private $cookieParams = [];

    /**
     * 请求消息体中的参数
     *
     * @var array|object|null
     */
    private $parsedBody;

    /**
     * 查询字符串参数
     *
     * @var array
     */
    private $queryParams = [];

    /**
     * 服务器参数
     *
     * @var array
     */
    private $serverParams;

    /**
     * 规范化的上传文件数据
     *
     * @var UploadedFile[]
     */
    private $uploadedFiles = [];

    /**
     * 构造函数
     *
     * @param string $method HTTP 方法
     * @param string|Uri $uri URI
     * @param array $headers 报头信息
     * @param string|resource|Stream|null $body 请求体
     * @param string $version HTTP 版本信息
     * @param array $serverParams 通常从 PHP 的 `$_SERVER` 超全局变量中获取，但不是必然的。
     */
    public function __construct($method, $uri, array $headers = [], $body = null, $version = '1.1', array $serverParams = [])
    {
        $this->serverParams = $serverParams;

        parent::__construct($method, $uri, $headers, $body, $version);
    }

    /**
     * 返回服务器参数。
     *
     * 返回与请求环境相关的数据，通常从 PHP 的 `$_SERVER` 超全局变量中获取，但不是必然的。
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * 获取从客户端发往服务器的 Cookie 数据。
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * 返回具体指定 Cookie 的实例。
     * 这个数据不是一定要来源于 `$_COOKIE`，但是 **必须** 与之结构兼容。通常在实例化时注入。
     *
     * @param array $cookies 表示 Cookie 的键值对。
     *
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * 获取查询字符串参数。
     * 注意：查询参数可能与 URI 或服务器参数不同步。如果你需要确保只获取原始值，则可能需要调用
     * `getUri()->getQuery()` 或服务器参数中的 `QUERY_STRING` 获取原始的查询字符串并自行解析。
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * 返回具体指定查询字符串参数的实例。
     *
     * @param array $query 查询字符串参数数组，通常来源于 `$_GET`。
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * 获取规范化的上传文件数据。
     *
     * 这个方法会规范化返回的上传文件元数据树结构，每个叶子结点都是 `Psr\Http\Message\UploadedFileInterface` 实例。
     *
     * 这些值 **可能** 在实例化的时候从 `$_FILES` 或消息体中获取，或者通过 `withUploadedFiles()` 获取。
     *
     * @return array `UploadedFile` 的实例数组；如果没有数据则必须返回一个空数组。
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * 返回使用指定的上传文件数据的新实例。
     *
     * @param array `UploadedFileInterface` 实例的树结构，类似于 `getUploadedFiles()` 的返回值。
     *
     * @return self
     *
     * @throws InvalidArgumentException 如果提供无效的结构时抛出。
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * 获取请求消息体中的参数。
     *
     * @return null|array|object 如果存在则返回反序列化消息体参数。一般是一个数组或 `object`。
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * 返回具有指定消息体参数的实例。
     *
     * @param null|array|object $data 反序列化的消息体数据，通常是数组或 `object`。
     *
     * @return self
     *
     * @throws InvalidArgumentException 如果提供的数据类型不支持。
     */
    public function withParsedBody($data)
    {
        if (!is_array($data) && !is_object($data) && null !== $data) {
            throw new InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
        }

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * 获取从请求派生的属性。
     *
     * 请求「attributes」可用于从请求导出的任意参数：比如路径匹配操作的结果；解密 Cookie 的结果；
     * 反序列化非表单编码的消息体的结果；属性将是应用程序与请求特定的，并且可以是可变的。
     *
     * @return mixed[] 从请求派生的属性。
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * 获取单个派生的请求属性。
     * 获取 getAttributes() 中声明的某一个属性，如果不存在则返回提供的默认值。
     * 这个方法不需要 hasAttribute 方法，因为允许在找不到指定属性的时候返回默认值。
     *
     * @param string $attribute 属性名称。
     * @param mixed $default 如果属性不存在时返回的默认值。
     *
     * @return mixed
     * @see getAttributes()
     */
    public function getAttribute($attribute, $default = null)
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * 返回具有指定派生属性的实例。
     * 此方法允许设置 getAttributes() 中声明的单个派生的请求属性。
     *
     * @param string $attribute 属性名。
     * @param mixed $value 属性值。
     *
     * @return self
     * @see getAttributes()
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    /**
     * 返回移除指定属性的实例。
     * 此方法允许移除 getAttributes() 中声明的单个派生的请求属性。
     * @param string $attribute 属性名。
     *
     * @return self
     * @see getAttributes()
     *
     */
    public function withoutAttribute($attribute)
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}
