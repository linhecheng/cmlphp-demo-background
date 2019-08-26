<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * 用法  加密：encrypt($data, $key)、解密：ecrypt($data, $key = null)
 * *********************************************************** */
namespace Cml;

/**
 * 加密解密处理类
 *
 * @package Cml
 */
class Encry
{

    /**
     * 加密key
     *
     * @var string
     */
    private static $auth_key;

    /**
     * 生成加密KEY
     *
     * @param string $key
     *
     * @return void
     */
    private static function createKey($key)
    {
        $key = is_null($key) ? Config::get("auth_key") : $key;
        self::$auth_key = md5($key /*. $_SERVER['HTTP_USER_AGENT']*/);
    }

    /**
     * 位加密或解密
     *
     * @param string $string 加密或解密内容
     * @param int $type 类型:1加密 2解密
     * @param string $key
     *
     * @return mixed
     */
    private static function cry($string, $type, $key)
    {
        self::createKey($key);
        $type == 2 && $string = str_replace(['___a', '___b', '___c'], ['/', '+', '='], $string);

        $string = $type == 2 ? base64_decode($string) : substr(md5(self::$auth_key . $string), 0, 8) . $string;
        $str_len = strlen($string);
        $data = [];
        $auth_key_length = strlen(self::$auth_key);
        for ($i = 0; $i <= 256; $i++) {
            $data[$i] = ord(self::$auth_key[$i % $auth_key_length]);
        }
        for ($i = $j = 1; $i < 256; $i++) {
            $j = $data[($i + $data[$i]) % 256];
            $tmp = $data[$i];
            $data[$i] = ord($data[$j]);
            $data[$j] = $tmp;
        }
        $s = '';
        for ($i = $j = 0; $i < $str_len; $i++) {
            $tmp = ($i + ($i % 256)) % 256;
            $j = $data[$tmp] % 256;
            $n = ($tmp + $j) % 256;
            $code = $data[($data[$j] + $data[$n]) % 256];
            $s .= chr(ord($string[$i]) ^ $code);
        }
        if ($type == 1) {
            return str_replace(['/', '+', '='], ['___a', '___b', '___c'], base64_encode($s));
        } else {
            if (substr(md5(self::$auth_key . substr($s, 8)), 0, 8) == substr($s, 0, 8)) {
                return substr($s, 8);
            }
            return '';
        }
    }

    /**
     * 加密方法
     *
     * @param string $data 加密字符串
     * @param string $key 密钥
     *
     * @return mixed
     */
    public static function encrypt($data, $key = null)
    {
        is_null($key) && $key = Config::get('auth_key');
        return self::cry(serialize($data), 1, $key);
    }

    /**
     * 解密方法
     *
     * @param string $data 解密字符串
     * @param string $key 密钥
     *
     * @return mixed
     */
    public static function decrypt($data, $key = null)
    {
        is_null($key) && $key = Config::get('auth_key');
        return unserialize(self::cry($data, 2, $key));
    }
}
