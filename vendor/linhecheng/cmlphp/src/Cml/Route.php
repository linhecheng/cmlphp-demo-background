<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 URL解析类
 * *********************************************************** */

namespace Cml;

use Cml\Http\Request;
use InvalidArgumentException;

/**
 * Url解析类,负责路由及Url的解析
 *
 * @package Cml
 */
class Route
{
    /**
     * pathIfo数据用来提供给插件做一些其它事情
     *
     * @var array
     */
    private static $pathInfo = [];


    /**
     * 解析url获取pathinfo
     *
     * @return void
     */
    public static function parsePathInfo()
    {
        $urlModel = Config::get('url_model');

        $pathInfo = self::$pathInfo;
        if (empty($pathInfo)) {
            $isCli = Request::isCli(); //是否为命令行访问
            if ($isCli) {
                isset($_SERVER['argv'][1]) && $pathInfo = explode('/', $_SERVER['argv'][1]);
            } else {
                //修正可能由于nginx配置不当导致的子目录获取有误
                if (false !== ($fixScriptName = stristr($_SERVER['SCRIPT_NAME'], '.php', true))) {
                    $_SERVER['SCRIPT_NAME'] = $fixScriptName . '.php';
                }

                $urlPathInfoDeper = Config::get('url_pathinfo_depr');
                if ($urlModel === 1 || $urlModel === 2) { //pathInfo模式(含显示、隐藏index.php两种)SCRIPT_NAME
                    if (isset($_GET[Config::get('var_pathinfo')])) {
                        $param = str_replace(Config::get('url_html_suffix'), '', $_GET[Config::get('var_pathinfo')]);
                    } else {
                        $param = preg_replace('/(.*)\/(.+)\.php(.*)/i', '\\1\\3', preg_replace(
                            [
                                '/\\' . Config::get('url_html_suffix') . '/',
                                '/\&.*/', '/\?.*/'
                            ],
                            '',
                            $_SERVER['REQUEST_URI']
                        ));//这边替换的结果是带index.php的情况。不带index.php在以下处理
                        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
                        if ($scriptName && $scriptName != '/') {//假如项目在子目录这边去除子目录含模式1和模式2两种情况(伪静态到子目录)
                            $param = substr($param, strpos($param, $scriptName) + strlen($scriptName));//之所以要strpos是因为子目录或请求string里可能会有多个/而SCRIPT_NAME里只会有1个
                        }
                    }
                    $param = trim($param, '/' . $urlPathInfoDeper);
                } elseif ($urlModel === 3 && isset($_GET[Config::get('var_pathinfo')])) {//兼容模式
                    $urlString = $_GET[Config::get('var_pathinfo')];
                    unset($_GET[Config::get('var_pathinfo')]);
                    $param = trim(str_replace(
                        Config::get('url_html_suffix'),
                        '',
                        ltrim($urlString, '/')
                    ), $urlPathInfoDeper);
                }

                $pathInfo = explode($urlPathInfoDeper, $param);
            }
        }

        isset($pathInfo[0]) && empty($pathInfo[0]) && $pathInfo = ['/'];

        self::$pathInfo = $pathInfo;

        Plugin::hook('cml.after_parse_path_info');
    }

    /**
     * 增加get访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function get($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->get($pattern, $action, $middleware);
    }

    /**
     * 增加post访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function post($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->post($pattern, $action, $middleware);
    }

    /**
     * 增加put访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function put($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->put($pattern, $action, $middleware);
    }

    /**
     * 增加patch访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function patch($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->patch($pattern, $action, $middleware);
    }

    /**
     * 增加delete访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function delete($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->delete($pattern, $action, $middleware);
    }

    /**
     * 增加options访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function options($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->options($pattern, $action, $middleware);
    }

    /**
     * 增加任意访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function any($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->any($pattern, $action, $middleware);
    }

    /**
     * 增加REST方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    public static function rest($pattern, $action, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->rest($pattern, $action, $middleware);
    }

    /**
     * 分组路由
     *
     * @param string $namespace 分组名
     * @param callable $func 闭包
     * @param array $middleware 使用的中间件
     */
    public static function group($namespace, callable $func, $middleware = [])
    {
        Cml::getContainer()->make('cml_route')->group($namespace, $func, $middleware);
    }

    /**
     * 获取解析后的pathInfo信息
     *
     * @return array
     */
    public static function getPathInfo()
    {
        return self::$pathInfo;
    }

    /**
     * 设置pathInfo信息
     *
     * @param array $pathInfo
     *
     * @return array
     */
    public static function setPathInfo($pathInfo)
    {
        return self::$pathInfo = $pathInfo;
    }

    /**
     * 修改解析得到的请求信息 含应用名、控制器、操作
     *
     * @param string|array $key path|controller|action|root
     * @param string $val
     *
     * @return void
     */
    public static function setUrlParams($key, $val)
    {
        Cml::getContainer()->make('cml_route')->setUrlParams($key, $val);
    }

    /**
     * 访问Cml::getContainer()->make('cml_route')中其余方法
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([Cml::getContainer()->make('cml_route'), $name], $arguments);
    }

    /**
     * 载入应用单独的路由
     *
     * @param string $app 应用名称
     * @param mixed $inConfigDir 配置文件是否在Config目录中
     */
    public static function loadAppRoute($app = 'web', $inConfigDir = true)
    {
        static $loaded = [];
        if (isset($loaded[$app])) {
            return;
        }
        $path = $app . DIRECTORY_SEPARATOR . ($inConfigDir ? Cml::getApplicationDir('app_config_path_name') . DIRECTORY_SEPARATOR : '') . 'route.php';
        $appRoute = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $path;
        if (!is_file($appRoute)) {
            throw new InvalidArgumentException(Lang::get('_NOT_FOUND_', $path));
        }

        $loaded[$app] = 1;
        Cml::requireFile($appRoute);
    }

    /**
     * 执行闭包路由
     * 执行闭包路由
     *
     * @param callable $call 闭包
     * @param string $route 路由string
     */
    public static function executeCallableRoute(callable $call, $route = '')
    {
        call_user_func($call);
        Cml::$debug && Debug::addTipInfo(Lang::get('_CML_EXECUTION_ROUTE_IS_', "callable route:{{$route}}", Config::get('url_model')));
        Cml::cmlStop();
    }
}
