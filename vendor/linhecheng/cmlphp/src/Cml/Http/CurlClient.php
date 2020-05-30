<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 curl客户端
 * *********************************************************** */

namespace Cml\Http;

use Cml\Http\Message\Psr\Stream;
use Cml\Log;
use Cml\Http\Message\Psr\Response;

/**
 * curl客户端实现类
 *
 * @package Cml\Http
 */
class CurlClient
{
    /**
     * get 请求类型
     */
    CONST REQUEST_TYPE_GET = 'get';

    /**
     * post 表单请求类型
     */
    CONST REQUEST_TYPE_POST = 'post';

    /**
     * post json 请求类型
     */
    CONST REQUEST_TYPE_JSON = 'json';

    /**
     * 上传文件类型
     */
    CONST REQUEST_TYPE_FILE = 'file';

    /**
     * post 原始数据
     */
    CONST REQUEST_TYPE_RAW = 'raw';

    /**
     * 要请求的url
     *
     * @var string
     */
    private $url;

    /**
     * 请求的参数
     *
     * @var array
     */
    private $params = [];

    /**
     * header头信息
     *
     * @var array
     */
    private $header = [];

    /**
     * 请求的数据类型 json/post/file/get/raw
     *
     * @var string
     */
    private $requestType = 'json';

    /**
     *  请求的连接超时时间默认10s
     *
     * @var int
     */
    private $connectTimeout = 10;

    /**
     * 等待执行输出的超时时间默认30s
     *
     * @var int
     */
    private $execTimeout = 30;

    /**
     * 错误是否要写入日志
     *
     * @var bool
     */
    private $writeLog = false;

    /**
     * 可自定义调用curl相关参数
     *
     * @var null
     */
    private $cusFunc = null;

    /**
     * CurlClient constructor.
     *
     * @param string $url 请求的地址
     * @param string $requestType 请求的类型
     */
    public function __construct($url, $requestType = self::REQUEST_TYPE_JSON)
    {
        return $this->setRequestUrl($url)->setRequestType($requestType);
    }

    /**
     * 设置要请求的url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setRequestUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 获取要请求的url
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->url;
    }

    /**
     * 设置要请求的url
     *
     * @param array|string $params 除了self::REQUEST_TYPE_RAW类型的请求是传string，其它情况都传array
     *
     * @return $this
     */
    public function setRequestParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 获取要请求的url
     *
     * @return array|string
     */
    public function getRequestParams()
    {
        return $this->params;
    }

    /**
     * 设置header头信息
     *
     * @param array $header
     *
     * @return $this
     */
    public function setRequestHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 获取header头信息
     *
     * @return array
     */
    public function getRequestHeader()
    {
        return $this->header;
    }

    /**
     * 设置请求的连接超时时间 默认10s
     *
     * @param int $connectTimeout
     *
     * @return $this
     */
    public function setConnectTimeout($connectTimeout = 10)
    {
        $this->connectTimeout = $connectTimeout;
        return $this;
    }

    /**
     * 获取请求的连接超时时间
     *
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * 设置等待执行输出的超时时间 默认30s
     *
     * @param int $execTimeout
     *
     * @return $this
     */
    public function setExecTimeout($execTimeout = 30)
    {
        $this->execTimeout = $execTimeout;
        return $this;
    }

    /**
     * 获取等待执行输出的超时时间默认30s
     *
     * @return int
     */
    public function getExecTimeout()
    {
        return $this->execTimeout;
    }

    /**
     * 设置 错误是否要写入日志
     *
     * @param bool $writeLog
     *
     * @return $this
     */
    public function setErrorIsWriteLog($writeLog = false)
    {
        $this->writeLog = $writeLog;
        return $this;
    }

    /**
     * 获取 错误是否要写入日志
     *
     * @return int
     */
    public function getErrorIsWriteLog()
    {
        return $this->writeLog;
    }

    /**
     * 设置请求类型
     *
     * @param string $type
     *
     * @return $this
     */
    public function setRequestType($type)
    {
        $this->requestType = $type;
        return $this;
    }

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * 设置请求自定义handler
     *
     * @param callable $handle 会将curl连接标识传入，可自定义调用curl相关参数
     * eg: function($ch) {
     *      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
     * }
     *
     * @return $this
     */
    public function setCustomHandler(callable $handle = null)
    {
        $this->cusFunc = $handle;
        return $this;
    }

    /**
     * 获取请求自定义handler
     *
     * @return callable
     */
    public function getCustomHandler()
    {
        return $this->cusFunc;
    }

    /**
     * 发起curl请求
     *
     * @param bool $returnResponse
     *
     * @return mixed|Response
     */
    public function sendRequest($returnResponse = false)
    {
        $ch = $this->curl();

        $ret = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($returnResponse) {
            $response = new Response();
            $response = $response->withStatus($httpCode)
                ->withBody(Stream::create($ret));
        }

        if (!$ret || !empty($error)) {
            $this->getErrorIsWriteLog() && Log::error('curl-error', [
                'url' => $this->getRequestUrl(),
                'params' => $this->getRequestParams(),
                'error' => $error,
                'ret' => $ret,
                'errno' => $errno,
                'httpCode' => $httpCode
            ]);
            $ret = false;
        }
        return $response ?? $ret;
    }

    /**
     * 发起curl请求
     *
     * @return bool|mixed
     */
    private function curl()
    {
        $url = $this->getRequestUrl();
        $parameter = $this->getRequestParams();
        $header = $this->getRequestHeader();
        $type = $this->getRequestType();
        $connectTimeout = $this->getConnectTimeout();
        $execTimeout = $this->getExecTimeout();
        $cusFunc = $this->getCustomHandler();

        $ssl = substr($url, 0, 8) == "https://";
        $ch = curl_init();
        if ($ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //检查证书中是否设置域名
        }

        $type = strtolower($type);
        if ($type == 'json' || $type == 'raw') {
            $type == 'json' && ($parameter = json_encode($parameter, JSON_UNESCAPED_UNICODE)) && ($header[] = 'Content-Type: application/json');
            //$queryStr = str_replace(['\/','[]'], ['/','{}'], $queryStr);//兼容
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
        } else if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameter));
        } else if ($type == 'file') {
            $isOld = substr($parameter['file'], 0, 1) == '@';
            if (function_exists('curl_file_create')) {
                $parameter['file'] = curl_file_create($isOld ? substr($parameter['file'], 1) : $parameter['file'], '');
            } else {
                $isOld || $parameter['file'] = '@' . $parameter['file'];
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
        } else {
            $queryStr = '';
            if (is_array($parameter)) {
                foreach ($parameter as $key => $val) {
                    $queryStr .= $key . '=' . $val . '&';
                }
                $queryStr = substr($queryStr, 0, -1);
                $queryStr && $url .= '?' . $queryStr;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $execTimeout);

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        is_callable($cusFunc) && $cusFunc($ch);
        return $ch;
    }
}