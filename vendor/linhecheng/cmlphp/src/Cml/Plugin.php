<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 插件类
 * *********************************************************** */
namespace Cml;

/**
 * CmlPHP中的插件实现类,负责钩子的绑定和插件的执行
 *
 * @package Cml
 */
class Plugin
{
    /**
     * 插件的挂载信息
     *
     * @var array
     */
    private static $mountInfo = [];

    /**
     * 执行插件
     *
     * @param string $hook 插件钩子名称
     * @param array $params 参数
     *
     * @return mixed
     */
    public static function hook($hook, $params = [])
    {
        $hookRun = isset(self::$mountInfo[$hook]) ? self::$mountInfo[$hook] : null;
        if (!is_null($hookRun)) {
            foreach ($hookRun as $key => $val) {
                if (is_int($key)) {
                    $callBack = $val;
                } else {
                    $plugin = new $key();
                    $callBack = [$plugin, $val];
                }
                $return = call_user_func_array($callBack, array_slice(func_get_args(), 1));

                if (!is_null($return)) {
                    return $return;
                }
            }
        }
        return null;
    }

    /**
     * 挂载插件到钩子
    \Cml\Plugin::mount('hookName', [
        function() {//匿名函数
        },
        '\App\Test\Plugins' => 'run' //对象,
        '\App\Test\Plugins::run'////静态方法
    ]);
     *
     * @param string $hook 要挂载的目标钩子
     * @param array $params 相应参数
     */
    public static function mount($hook, $params = [])
    {
        is_array($params) || $params = [$params];
        if (isset(self::$mountInfo[$hook])) {
            self::$mountInfo[$hook] += $params;
        } else {
            self::$mountInfo[$hook] = $params;
        }
    }
}
