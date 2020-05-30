<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架公用函数库
 * *********************************************************** */

namespace Cml;

use \Cml\dBug as outDebug;
use  \PhpConsole\Connector as PhpConsoleConnector;

/**
 * 友好的变量输出
 *
 * @param mixed $var 变量
 * @param int $getArgs 获取要打印的值
 *
 * @return string
 */
function dump($var, $getArgs = 0)
{
    if (Cml::$debug) {
        new outDebug($var);    //deBug模式直接输出
    } else {
        static $args = [];
        if (($getArgs == 1)) return $args;
        $args[] = $var;//输出到浏览器控制台
    }
    return '';
}

/**
 * 打印数据到chrome控制台
 *
 * @param mixed $var 要打印的变量
 * @param string $tag 标签
 *
 * @return void
 */
function dumpUsePHPConsole($var, $tag = 'debug')
{
    if (!Config::get('dump_use_php_console')) {
        throw new \BadFunctionCallException(Lang::get('_NOT_OPEN_', 'dump_use_php_console'));
    }
    static $connector = false;
    if ($connector === false) {
        $connector = PhpConsoleConnector::getInstance();
        $password = Config::get('php_console_password');
        $password && $connector->setPassword($password);
    }
    $connector->getDebugDispatcher()->dispatchDebug($var, $tag);
}

/**
 * 友好的变量输出并且终止程序(只在调试模式下才终止程序)
 *
 * @param mixed $var 变量
 *
 * @return void|string
 */
function dd($var)
{
    dump($var);
    Cml::$debug && exit();
}


/**
 * print_r && exit
 *
 * @param mixed $var
 */
function pd($var)
{
    print_r($var);
    exit();
}

/**
 * 自定义异常处理
 *
 * @param string $msg 异常消息
 * @param integer $code 异常代码 默认为0
 *
 * @throws \Exception
 */
function throwException($msg, $code = 0)
{
    throw new \Exception($msg, $code);
}

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 *
 * @param string $name 缓存名称
 * @param mixed $value 缓存值
 * @param string $path 缓存路径
 *
 * @return mixed
 */
function simpleFileCache($name, $value = '', $path = null)
{
    is_null($path) && $path = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . 'Data';
    static $_cache = [];
    $filename = $path . '/' . $name . '.php';
    if ($value !== '') {
        if (is_null($value)) {
            // 删除缓存
            return false !== @unlink($filename);
        } else if (is_array($value)) {
            // 缓存数据
            $dir = dirname($filename);
            // 目录不存在则创建
            is_dir($dir) || mkdir($dir, 0700, true);
            $_cache[$name] = $value;
            return file_put_contents($filename, "<?php\treturn " . var_export($value, true) . ";?>", LOCK_EX);
        } else {
            return false;
        }
    }
    if (isset($_cache[$name])) return $_cache[$name];
    // 获取缓存数据
    if (is_file($filename)) {
        $value = Cml::requireFile($filename);
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

/**
 * 生成友好的时间格式
 *
 * @param $from
 *
 * @return bool|string
 */
function friendlyDate($from)
{
    static $now = NULL;
    $now == NULL && $now = time();
    !is_numeric($from) && $from = strtotime($from);
    $seconds = $now - $from;
    $minutes = floor($seconds / 60);
    $hours = floor($seconds / 3600);
    $day = round((strtotime(date('Y-m-d', $now)) - strtotime(date('Y-m-d', $from))) / 86400);
    if ($seconds == 0) {
        return Lang::get('friendly date 0', '刚刚');//语言包配置: 'friendly date 0' => '刚刚'
    }
    if (($seconds >= 0) && ($seconds <= 60)) {
        return Lang::get('friendly date 1', ['seconds' => $seconds]) ?: "{$seconds}秒前";//语言包配置: 'friendly date 1' => '{seconds}秒前'
    }
    if (($minutes >= 0) && ($minutes <= 60)) {
        return Lang::get('friendly date 2', ['minutes' => $minutes]) ?: "{$minutes}分钟前";//语言包配置: 'friendly date 2' => '{minutes}分钟前'
    }
    if (($hours >= 0) && ($hours <= 24)) {
        return Lang::get('friendly date 3', ['hours' => $hours]) ?: "{$hours}小时前";//语言包配置: 'friendly date 3' => '{hours}小时前'
    }
    if ((date('Y') - date('Y', $from)) > 0) {
        return date('Y-m-d', $from);
    }

    switch ($day) {
        case 0:
            return date(Lang::get('friendly date 4', '今天H:i'), $from);
            break;
        case 1:
            return date(Lang::get('friendly date 5', '昨天H:i'), $from);
            break;
        default:
            return Lang::get('friendly date 6', ['day' => $day]) ?: "{$day}天前";//语言包配置: 'friendly date 6' => '{day}天前'
    }
}

/**
 * 生成唯一id
 *
 * @return string
 */
function createUnique()
{
    $data = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . Cml::$nowMicroTime . rand();
    return sha1($data);
}

/**
 * 驼峰转成下划线
 *
 * @param string $str
 *
 * @return string
 */
function humpToLine($str)
{
    $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
        return '_' . strtolower($matches[0]);
    }, $str);
    return $str;
}

/**
 * 下划线转驼峰
 *
 * @param string $value
 * @param bool $upper 首字母大写还是小写
 *
 * @return string
 */
function studlyCase($value, $upper = true)
{
    $value = str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $value)));
    $upper || $value = lcfirst($value);
    return $value;
}

/**
 * 获取
 * @param $class
 * @return string
 */
function getClassBasename($class, $humpToLine = false)
{
    $class = is_object($class) ? get_class($class) : $class;
    $class = basename(str_replace('\\', '/', $class));
    return $humpToLine ? humpToLine(lcfirst($class)) : $class;
}

/**
 * 过滤数组的值.
 *
 * @param array $array 要处理的数组
 * @param array $field 要包含/要排除的字段
 * @param int $type 1 只包含 0排除
 *
 * @return array
 */
function filterArrayValue(array &$array, array $field = [], $type = 1)
{
    foreach ($array as $key => $item) {
        if ($type == 1) {
            if (!in_array($key, $field)) {
                unset($array[$key]);
            }
        } else {
            if (in_array($key, $field)) {
                unset($array[$key]);
            }
        }
    }
    return $array;
}

/**
 * 将n级的关联数组格式化为索引数组-经常用于is tree插件
 *
 * @param array $array 待处理的数组
 * @param string $childrenKey 子极的key
 * @param array|callable $push 要额外添加的项
 *
 * @return array
 */
function arrayAssocKeyToNumber(array &$array, $childrenKey = 'children', $push = [])
{
    $array = array_values($array);
    foreach ($array as &$item) {
        if (is_callable($push)) {
            $pushData = call_user_func($push, $item);
        } else {
            $pushData = $push;
        }
        $pushData && $item = array_merge($item, $pushData);
        if (isset($item[$childrenKey]) && $item[$childrenKey]) {
            $item[$childrenKey] = arrayAssocKeyToNumber($item[$childrenKey], $childrenKey, $push);
        }
    }
    return $array;
}
