<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 输入管理类
 * *********************************************************** */

namespace Cml\Http;

/**
 * 输入过滤管理类,用户输入数据通过此类获取
 *
 * @package Cml\Http
 */
class Input
{

    /**
     * 统一的处理输入-输出为字符串
     *
     * @param array|string $params
     *
     * @return array|string
     */
    private static function parseInputToString($params)
    {
        return is_array($params) ? array_map(function ($item) {
            return trim(htmlspecialchars($item, ENT_QUOTES, 'UTF-8'));
        }, $params) : trim(htmlspecialchars($params, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * 统一的处理输入-输出为整型
     *
     * @param array|string $params
     *
     * @return array|int
     */
    private static function parseInputToInt($params)
    {
        return is_array($params) ? array_map(function ($item) {
            return intval($item);
        }, $params) : intval($params);
    }

    /**
     * 统一的处理输入-输出为布尔型
     *
     * @param array|string $params
     *
     * @return array|bool
     */
    private static function parseInputToBool($params)
    {
        return is_array($params) ? array_map(function ($item) {
            return ((bool)$item);
        }, $params) : ((bool)$params);
    }

    /**
     * 获取解析后的Refer的参数
     * @param string $name 参数的key
     *
     * @return mixed
     */
    private static function getReferParams($name)
    {
        static $params = null;

        if (is_null($params)) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $args = parse_url($_SERVER['HTTP_REFERER']);
                parse_str($args['query'], $params);
            }
        }
        return isset($params[$name]) ? $params[$name] : null;
    }

    /**
     * 获取get string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_GET值时返回的默认值
     *
     * @return string|null|array
     */
    public static function getString($name, $default = null)
    {
        if (isset($_GET[$name]) && $_GET[$name] !== '') return self::parseInputToString($_GET[$name]);
        return $default;
    }

    /**
     * 获取post string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_POST值时返回的默认值
     *
     * @return string|null|array
     */
    public static function postString($name, $default = null)
    {
        if (isset($_POST[$name]) && $_POST[$name] !== '') return self::parseInputToString($_POST[$name]);
        return $default;
    }

    /**
     * 获取$_REQUEST string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_REQUEST值时返回的默认值
     *
     * @return null|string|array
     */
    public static function requestString($name, $default = null)
    {
        if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '') return self::parseInputToString($_REQUEST[$name]);
        return $default;
    }

    /**
     * 获取Refer string数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到Refer值时返回的默认值
     *
     * @return null|string|array
     */
    public static function referString($name, $default = null)
    {
        $res = self::getReferParams($name);
        if (!is_null($res)) return self::parseInputToString($res);
        return $default;
    }


    /**
     * 获取get int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_GET值时返回的默认值
     *
     * @return int|null|array
     */
    public static function getInt($name, $default = null)
    {
        if (isset($_GET[$name]) && $_GET[$name] !== '') return self::parseInputToInt($_GET[$name]);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取post int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_POST值时返回的默认值
     *
     * @return int|null|array
     */
    public static function postInt($name, $default = null)
    {
        if (isset($_POST[$name]) && $_POST[$name] !== '') return self::parseInputToInt($_POST[$name]);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取$_REQUEST int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_REQUEST值时返回的默认值
     *
     * @return null|int|array
     */
    public static function requestInt($name, $default = null)
    {
        if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '') return self::parseInputToInt($_REQUEST[$name]);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取Refer int数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到Refer值时返回的默认值
     *
     * @return null|string|array
     */
    public static function referInt($name, $default = null)
    {
        $res = self::getReferParams($name);
        if (!is_null($res)) return self::parseInputToInt($res);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取get bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_GET值时返回的默认值
     *
     * @return bool|null|array
     */
    public static function getBool($name, $default = null)
    {
        if (isset($_GET[$name]) && $_GET[$name] !== '') return self::parseInputToBool($_GET[$name]);
        return (is_null($default) ? null : ((bool)$default));
    }

    /**
     * 获取post bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_POST值时返回的默认值
     *
     * @return bool|null|array
     */
    public static function postBool($name, $default = null)
    {
        if (isset($_POST[$name]) && $_POST[$name] !== '') return self::parseInputToBool($_POST[$name]);
        return (is_null($default) ? null : ((bool)$default));
    }

    /**
     * 获取$_REQUEST bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到$_REQUEST值时返回的默认值
     *
     * @return null|bool|array
     */
    public static function requestBool($name, $default = null)
    {
        if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '') return self::parseInputToBool($_REQUEST[$name]);
        return (is_null($default) ? null : ((bool)$default));
    }

    /**
     * 获取Refer bool数据
     *
     * @param string $name 要获取的变量
     * @param null $default 未获取到Refer值时返回的默认值
     *
     * @return null|string|array
     */
    public static function referBool($name, $default = null)
    {
        $res = self::getReferParams($name);
        if (!is_null($res)) return self::parseInputToBool($res);
        return (is_null($default) ? null : ((bool)$default));
    }
}
