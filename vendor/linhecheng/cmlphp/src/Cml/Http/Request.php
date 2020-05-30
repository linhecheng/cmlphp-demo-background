<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 下午5:30
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 请求类
 * *********************************************************** */

namespace Cml\Http;

use Cml\Cml;
use Cml\Config;
use Cml\Route;

/**
 * 请求处理类，获取用户请求信息以发起curl请求
 *
 * @package Cml\Http
 */
class Request
{
    /**
     * 获取IP地址
     *
     * @param bool $prox 是否只获取代理Ip
     *
     * @return string
     */
    public static function ip($prox = false)
    {
        if ($prox) {
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                return strip_tags($_SERVER['HTTP_CLIENT_IP']);
            }
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return strip_tags($_SERVER['HTTP_X_FORWARDED_FOR']);
            }
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return strip_tags($_SERVER['REMOTE_ADDR']);
        }
        return 'unknown';
    }

    /**
     * 获取用户标识
     *
     * @return string
     */
    public static function userAgent()
    {
        return strip_tags($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * 获取主机名称
     *
     * @param bool $joinPort 是否带上端口
     *
     * @return string
     */
    public static function host($joinPort = true)
    {
        $host = strip_tags(isset($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'])[0] : $_SERVER['SERVER_NAME']);
        $joinPort && $host = $host . (in_array($_SERVER['SERVER_PORT'], [80, 443]) ? '' : ':' . $_SERVER['SERVER_PORT']);
        return $host;
    }

    /**
     * 获取基本地址
     *
     * @param bool $joinPort 是否带上端口
     *
     * @return string
     */
    public static function baseUrl($joinPort = true)
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
        return $protocol . self::host($joinPort);
    }

    /**
     * 获取带全参数的url地址
     *
     * @param bool $addSufFix 是否添加伪静态后缀
     * @param bool $joinParams 是否带上GET请求参数
     *
     * @return string
     */
    public static function fullUrl($addSufFix = true, $joinParams = true)
    {
        $params = '';
        if ($joinParams) {
            $get = $_GET;
            unset($get[Config::get('var_pathinfo')]);
            $params = http_build_query($get);
            $params && $params = '?' . $params;
        }
        return Request::baseUrl() . '/' . implode('/', Route::getPathInfo()) . ($addSufFix ? Config::get('url_html_suffix') : '') . $params;
    }

    /**
     * 获取请求时间
     *
     * @return mixed
     */
    public static function requestTime()
    {
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 判断是否为手机浏览器
     *
     * @return bool
     */
    public static function isMobile()
    {
        if ($_GET['mobile'] === 'yes') {
            setcookie('ismobile', 'yes', 3600);
            return true;
        } elseif ($_GET['mobile'] === 'no') {
            setcookie('ismobile', 'no', 3600);
            return false;
        }

        $cookie = $_COOKIE('ismobile');
        if ($cookie === 'yes') {
            return true;
        } elseif ($cookie === 'no') {
            return false;
        } else {
            $cookie = null;
            static $mobileBrowserList = ['iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
                'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
                'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
                'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
                'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
                'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
                'benq', 'haier', '^lct', '320x320', '240x320', '176x220'];
            foreach ($mobileBrowserList as $val) {
                $result = strpos(strtolower($_SERVER['HTTP_USER_AGENT']), $val);
                if (false !== $result) {
                    setcookie('ismobile', 'yes', 3600);
                    return true;
                }
            }
            setcookie('ismobile', 'no', 3600);
            return false;
        }
    }

    /**
     * 判断是否为POST请求
     *
     * @return bool
     */
    public static function isPost()
    {
        return (strtolower(self::getService('REQUEST_METHOD')) == 'post') ? true : false;
    }

    /**
     * 判断是否为GET请求
     *
     * @return bool
     */
    public static function isGet()
    {
        return (strtolower(self::getService('REQUEST_METHOD')) == 'get') ? true : false;
    }

    /**
     * 判断是否为AJAX请求
     *
     * @param bool $checkAccess 是否检测HTTP_ACCESS头
     *
     * @return bool
     */
    public static function isAjax($checkAccess = false)
    {
        if (
            self::getService('HTTP_X_REQUESTED_WITH')
            && strtolower(self::getService('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest'
        ) {
            return true;
        }

        if ($checkAccess) {
            return self::acceptJson();
        }

        return false;
    }

    /**
     * 判断请求类型是否为json
     *
     * @return bool
     */
    public static function acceptJson()
    {
        $accept = self::getService('HTTP_ACCEPT');
        if (false !== strpos($accept, 'json') || false !== strpos($accept, 'javascript')) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否以cli方式运行
     *
     * @return bool
     */
    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }


    /**
     * 获取SERVICE信息
     *
     * @param string $name SERVER的键值名称
     *
     * @return string
     */
    public static function getService($name = '')
    {
        if ($name == '') return $_SERVER;
        return (isset($_SERVER[$name])) ? strip_tags($_SERVER[$name]) : '';
    }

    /**
     * 获取POST过来的二进制数据,与手机端交互
     *
     * @param bool $formatJson 获取的数据是否为json并格式化为数组
     * @param string $jsonField 获取json格式化为数组的字段多维数组用.分隔  如top.son.son2
     *
     * @return bool|mixed|null|string
     */
    public static function getBinaryData($formatJson = false, $jsonField = '')
    {
        if (isset($GLOBALS['HTTP_RAW_POST_DATA']) && !empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $data = $GLOBALS['HTTP_RAW_POST_DATA'];
        } else {
            $data = file_get_contents('php://input');
        }
        if ($formatJson) {
            $data = json_decode($data, true);
            $jsonField && $data = Cml::doteToArr($jsonField, $data);
        }
        return $data;
    }

    /**
     * 发起curl请求
     *
     * @param string $url 要请求的url
     * @param array $parameter 请求参数
     * @param array $header header头信息
     * @param string $type 请求的数据类型 json/post/file/get/raw
     * @param int $connectTimeout 请求的连接超时时间默认10s
     * @param int $execTimeout 等待执行输出的超时时间默认30s
     * @param bool $writeLog 是否写入错误日志
     * @param null|callable $cusFunc 可自定义调用curl相关参数
     *
     * @return bool|mixed
     */
    public static function curl($url, $parameter = [], $header = [], $type = 'json', $connectTimeout = 10, $execTimeout = 30, $writeLog = false, $cusFunc = null)
    {
        $curlClient = new CurlClient($url);
        return $curlClient->setRequestParams($parameter)
            ->setRequestHeader($header)
            ->setRequestType($type)
            ->setConnectTimeout($connectTimeout)
            ->setExecTimeout($execTimeout)
            ->setErrorIsWriteLog($writeLog)
            ->setCustomHandler($cusFunc)
            ->sendRequest(false);
    }

    /**
     * 返回操作系统类型
     *
     * @return bool true为win false为unix
     */
    public static function operatingSystem()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        } else {
            return false;
        }
    }
}
