<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统安全类
 * *********************************************************** */

namespace Cml;

use Cml\Http\Input;
use Cml\Http\Request;
use Cml\Http\Response;
use UnexpectedValueException;

/**
 * 安全处理类,封装了常用的安全过滤接口
 *
 * @package Cml
 */
class Secure
{

    /**
     * 增强的addslashes
     *
     * @param mixed $var 要过滤的变量字符串或数组
     *
     * @return mixed 处理后的变量
     */
    public static function addslashes(&$var)
    {
        if (is_array($var)) {
            foreach ($var as &$v) {
                self::addslashes($v);
            }
        } else {
            $var = addslashes($var);
        }
        return $var;
    }

    /**
     * 增强的stripslashes
     *
     * @param mixed $var 要过滤的变量字符串或数组
     *
     * @return mixed 处理后的变量
     */
    public static function stripslashes(&$var)
    {
        if (is_array($var)) {
            foreach ($var as &$v) {
                self::stripslashes($v);
            }
        } else {
            $var = stripslashes($var);
        }
        return $var;
    }

    /**
     * 增强的strip_tags
     *
     * @param mixed $var 要过滤的变量 字符串或数组
     *
     * @return mixed 处理后的变量
     */
    public static function stripTags(&$var)
    {
        if (is_array($var)) {
            foreach ($var as &$v) {
                self::stripTags($v);
            }
        } else {
            $var = strip_tags($var);
        }
        return $var;
    }

    /**
     * 增强的htmlspecialchars
     *
     * @param mixed $var 要过滤的变量 字符串或数组
     *
     * @return mixed 处理后的变量
     */
    public static function htmlspecialchars(&$var)
    {
        if (is_array($var)) {
            foreach ($var as &$v) {
                self::htmlspecialchars($v);
            }
        } else {
            $var = htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
        }
        return $var;
    }

    /**
     * 增强的htmlspecialchars_decode
     *
     * @param mixed $var 要过滤的变量 字符串或数组
     *
     * @return mixed 处理后的变量
     */
    public static function htmlspecialcharsDecode(&$var)
    {
        if (is_array($var)) {
            foreach ($var as &$v) {
                self::htmlspecialcharsDecode($v);
            }
        } else {
            $var = htmlspecialchars_decode($var, ENT_QUOTES);
        }
        return $var;
    }

    /**
     * 过滤javascript,css,iframes,object等标签
     *
     * @param string $value 需要过滤的值
     * @param bool $clear 转义还是删除
     *
     * @return mixed
     */
    public static function filterScript($value, $clear = false)
    {
        $value = preg_replace("/javascript:/i", $clear ? '' : "&111", $value);
        $value = preg_replace("/(javascript:)?on(click|load|key|mouse|error|abort|unload|change|dblclick|move|reset|resize|submit)/i", $clear ? '' : "&111n\\2", $value);
        $value = preg_replace("/<script(.*?)>(.*?)<\/script>/si", $clear ? '' : "&ltscript\\1&gt\\2&lt/script&gt", $value);
        $value = preg_replace("/<iframe(.*?)>(.*?)<\/iframe>/si", $clear ? '' : "&ltiframe\\1&gt\\2&lt/iframe&gt", $value);
        $value = preg_replace("/<object.+<\/object>/isU", '', $value);
        return $value;
    }

    /**
     * 过滤特殊字符
     *
     * @param string $value 需要过滤的值
     *
     * @return mixed
     */
    public static function filterStr($value)
    {
        $value = str_replace(["\0", "%00", "\r"], '', $value);
        $value = preg_replace(['/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '/&(?!(#[0-9]+|[a-z]+);)/is'], ['', '&amp;'], $value);
        $value = str_replace(["%3C", '<'], '&lt;', $value);
        $value = str_replace(["%3E", '>'], '&gt;', $value);
        $value = str_replace(['"', "'", "\t", '  '], ['&quot;', '&#39;', '    ', '&nbsp;&nbsp;'], $value);
        return $value;
    }

    /**
     * 过滤sql语句
     *
     * @param $value
     *
     * @return mixed
     */
    public static function filterSql($value)
    {
        return str_ireplace(["select", 'insert', "update", "delete", "\'", "\/\*", "\.\.\/", "\.\/", "union", "into", "load_file", "outfile"],
            ["", "", "", "", "", "", "", "", "", "", "", ""],
            $value);
    }

    /*
     * 加强型过滤
     *
     * @param $value
     *
     * @return mixed
     */
    public static function filterAll(&$var)
    {
        if (is_array($var)) {
            foreach ($var as &$v) {
                self::filterAll($v);
            }
        } else {
            $var = addslashes($var);
            $var = self::filterStr($var);
            $var = self::filterSql($var);
        }
        return $var;
    }

    /**
     * 防止csrf跨站攻击
     *
     * @param int $type 检测类型   0不检查，1、只检查post，2、post get都检查
     */
    public static function checkCsrf($type = 1)
    {
        if ($type !== 0 && isset($_SERVER['HTTP_REFERER']) && !strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
            if ($type == 1) {
                if (!empty($_POST)) {
                    Response::sendHttpStatus(403);
                    throw new UnexpectedValueException(Lang::get('_ILLEGAL_REQUEST_'));
                }
            } else {
                Response::sendHttpStatus(403);
                throw new UnexpectedValueException(Lang::get('_ILLEGAL_REQUEST_'));
            }
        }
    }

    /**
     * 类加载-获取全局TOKEN，防止CSRF攻击
     *
     * @return string
     */
    public static function getToken()
    {
        return isset($_COOKIE['CML_TOKEN']) ? $_COOKIE['CML_TOKEN'] : '';
    }

    /**
     * 类加载-检测token值
     *
     * @return bool
     */
    public static function checkToken()
    {
        $token = Input::postString('CML_TOKEN');
        if (empty($token)) return false;
        if ($token !== self::getToken()) return false;
        unset($_COOKIE['CML_TOKEN']);
        return true;
    }

    /**
     * 类加载-设置全局TOKEN，防止CSRF攻击
     *
     * @return void
     */
    public static function setToken()
    {
        if (!isset($_COOKIE['CML_TOKEN']) || empty($_COOKIE['CML_TOKEN'])) {
            $str = substr(md5(Cml::$nowTime . Request::getService('HTTP_USER_AGENT')), 5, 8);
            setcookie('CML_TOKEN', $str, null, '/');
            $_COOKIE['CML_TOKEN'] = $str;
        }
    }
}
