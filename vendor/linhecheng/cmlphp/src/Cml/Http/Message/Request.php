<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 ServerRequest扩展类
 * *********************************************************** */

namespace Cml\Http\Message;

use Cml\Cml;
use Cml\Http\Cookie;
use Cml\Http\Input;
use Cml\Http\Message\Psr\ServerRequest;
use Cml\Http\Message\Psr\Stream;
use Cml\Http\Message\Psr\UploadedFile;
use Cml\Http\Message\Psr\Uri;
use Cml\Tools\Arr;
use Cml\Http\Request as StaticRequest;

/**
 * 框架ServerRequest扩展类
 *
 * @mixin ServerRequest
 * @package Cml\Http\Message
 */
class Request
{
    /**
     * @var ServerRequest
     */
    protected $psrServerRequest = null;

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
        if (is_null($this->psrServerRequest)) {
            $this->psrServerRequest = Cml::getContainer()->make('psr7_server_request', func_get_args());
        }
    }

    public function __call($method, $arguments)
    {
        $result =  call_user_func_array([$this->psrServerRequest, $method], $arguments);
        if ($result instanceof  $this->psrServerRequest) {
            $this->psrServerRequest = $result;
            return $this;
        } else {
            return $result;
        }
    }

    /**
     * 获取get string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_GET值时返回的默认值
     *
     * @return string|null|array
     */
    public function getString($name, $default = null)
    {
        return Input::getString($name, $default);
    }

    /**
     * 获取post string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_POST值时返回的默认值
     *
     * @return string|null|array
     */
    public function postString($name, $default = null)
    {
        return Input::postString($name, $default);
    }

    /**
     * 获取$_REQUEST string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_REQUEST值时返回的默认值
     *
     * @return null|string|array
     */
    public function requestString($name, $default = null)
    {
        return Input::requestString($name, $default);
    }

    /**
     * 获取Refer string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到Refer值时返回的默认值
     *
     * @return null|string|array
     */
    public function referString($name, $default = null)
    {
        return Input::referString($name, $default);
    }


    /**
     * 获取get int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_GET值时返回的默认值
     *
     * @return int|null|array
     */
    public function getInt($name, $default = null)
    {
        return Input::getInt($name, $default);
    }

    /**
     * 获取post int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_POST值时返回的默认值
     *
     * @return int|null|array
     */
    public function postInt($name, $default = null)
    {
        return Input::postInt($name, $default);
    }

    /**
     * 获取$_REQUEST int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_REQUEST值时返回的默认值
     *
     * @return null|int|array
     */
    public function requestInt($name, $default = null)
    {
        return Input::requestInt($name, $default);
    }

    /**
     * 获取Refer int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到Refer值时返回的默认值
     *
     * @return null|string|array
     */
    public function referInt($name, $default = null)
    {
        return Input::referInt($name, $default);
    }

    /**
     * 获取get bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_GET值时返回的默认值
     *
     * @return bool|null|array
     */
    public function getBool($name, $default = null)
    {
        return Input::getBool($name, $default);
    }

    /**
     * 获取post bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_POST值时返回的默认值
     *
     * @return bool|null|array
     */
    public function postBool($name, $default = null)
    {
        return Input::postBool($name, $default);
    }

    /**
     * 获取$_REQUEST bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_REQUEST值时返回的默认值
     *
     * @return null|bool|array
     */
    public function requestBool($name, $default = null)
    {
        return Input::requestBool($name, $default);
    }

    /**
     * 获取Refer bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到Refer值时返回的默认值
     *
     * @return null|string|array
     */
    public function referBool($name, $default = null)
    {
        return Input::referBool($name, $default);
    }

    /**
     * 判断当前请求的是否为某类型
     *
     * @param string $method
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * 获取上传文件
     *
     * @param string $key
     * @param null|mixed $default
     *
     * @return null|UploadedFile|UploadedFile[]
     */
    public function file($key, $default = null)
    {
        return Arr::get($this->getUploadedFiles(), $key, $default);
    }

    /**
     * 检查文件是否存在
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasFile($key)
    {
        return !!$this->file($key);
    }

    /**
     * 获取cookie
     *
     * @param string $key
     * @param null $default
     *
     * @return bool|mixed
     */
    public function cookie($key, $default = null)
    {
        return Cookie::get($key) ?: $default;
    }

    /**
     * 判断cookie是否存在
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasCookie($key)
    {
        return !is_null($this->cookie($key));
    }

    /**
     * 获取请求json数据
     *
     * @param null $key 多维数组.分隔  如top.son.son2
     *
     * @return mixed
     */
    public function json($key = null)
    {
        return $this->contentTypeIsJson() ? StaticRequest::getBinaryData(true, $key) : null;
    }

    /**
     * 返回未经处理的json数据
     *
     * @return mixed
     */
    public function jsonRaw()
    {
        return $this->contentTypeIsJson() ? StaticRequest::getBinaryData(false) : null;
    }

    /**
     * 判断请求类型是否为json
     *
     * @return bool
     */
    private function contentTypeIsJson()
    {
        $contentType = strtolower($this->getHeaderLine('Content-Type'));
        return strpos($contentType, 'application/json') === 0;
    }

    /**
     * 获取IP地址
     *
     * @return string
     */
    public function ip()
    {
        return StaticRequest::ip();
    }

    /**
     * 获取主机名称
     *
     * @param bool $joinPort 是否带上端口
     *
     * @return string
     */
    public function host($joinPort = true)
    {
        return StaticRequest::host($joinPort);
    }

    /**
     * 获取基本地址
     *
     * @param bool $joinPort 是否带上端口
     *
     * @return string
     */
    public function baseUrl($joinPort = true)
    {
        return StaticRequest::baseUrl($joinPort);
    }

    /**
     * 获取带全参数的url地址
     *
     * @param bool $addSufFix 是否添加伪静态后缀
     * @param bool $joinParams 是否带上GET请求参数
     *
     * @return string
     */
    public function fullUrl($addSufFix = true, $joinParams = true)
    {
        return StaticRequest::fullUrl($addSufFix, $joinParams);
    }

    /**
     * 判断是否为AJAX请求
     *
     * @param bool $checkAccess 是否检测HTTP_ACCESS头
     *
     * @return bool
     */
    public function isAjax($checkAccess = false)
    {
        return StaticRequest::isAjax($checkAccess);
    }

    /**
     * 判断是否以cli方式运行
     *
     * @return bool
     */
    public function isCli()
    {
        return StaticRequest::isCli();
    }
}