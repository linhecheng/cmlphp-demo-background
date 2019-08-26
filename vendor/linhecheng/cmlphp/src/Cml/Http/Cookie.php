<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Cookie管理类
 * *********************************************************** */

namespace Cml\Http;

use Cml\Cml;
use Cml\Config;
use Cml\Encry;

/**
 * Cookie管理类，封装了对Cookie的操作
 *
 * @package Cml\Http
 */
class Cookie
{
    /**
     * 判断Cookie是否存在
     *
     * @param $key string 要判断Cookie
     *
     * @return bool
     */
    public static function isExist($key)
    {
        return isset($_COOKIE[Config::get('cookie_prefix') . $key]);
    }

    /**
     * 获取某个Cookie值
     *
     * @param string $name 要获取的cookie名称
     *
     * @return bool|mixed
     */
    public static function get($name)
    {
        if (!self::isExist($name)) return false;
        $value = $_COOKIE[Config::get('cookie_prefix') . $name];
        return Encry::decrypt($value);
    }

    /**
     * 设置某个Cookie值
     *
     * @param string $name 要设置的cookie的名称
     * @param mixed $value 要设置的值
     * @param int $expire 过期时间
     * @param string $path path
     * @param string $domain domain
     *
     * @return void
     */
    public static function set($name, $value, $expire = 0, $path = '', $domain = '')
    {
        empty($expire) && $expire = Config::get('cookie_expire');
        empty($path) && $path = Config::get('cookie_path');
        empty($domain) && $domain = Config::get('cookie_domain');

        $expire = empty($expire) ? 0 : Cml::$nowTime + $expire;
        $value = Encry::encrypt($value);
        setcookie(Config::get('cookie_prefix') . $name, $value, $expire, $path, $domain);
        $_COOKIE[Config::get('cookie_prefix') . $name] = $value;
    }

    /**
     * 删除某个Cookie值
     *
     * @param string $name 要删除的cookie的名称
     * @param string $path path
     * @param string $domain domain
     *
     * @return void
     */
    public static function delete($name, $path = '', $domain = '')
    {
        self::set($name, '', -3600, $path, $domain);
        unset($_COOKIE[Config::get('cookie_prefix') . $name]);
    }

    /**
     * 清空Cookie值
     *
     * @return void
     */
    public static function clear()
    {
        unset($_COOKIE);
    }
}
