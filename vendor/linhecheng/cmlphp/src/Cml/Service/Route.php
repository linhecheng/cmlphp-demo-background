<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 自带路由实现
 * *********************************************************** */

namespace Cml\Service;

use Cml\Cml;
use Cml\Config;
use Cml\Lang;
use Cml\Interfaces\Route as RouteInterface;

/**
 * Url解析类,负责路由及Url的解析
 *
 * @package Cml
 */
class Route implements RouteInterface
{
    /**
     * 是否启用分组
     *
     * @var false
     */
    private static $group = false;

    /**
     * 路由类型为GET请求
     *
     * @var int
     */
    const REQUEST_METHOD_GET = 1;

    /**
     * 路由类型为POST请求
     *
     * @var int
     */
    const REQUEST_METHOD_POST = 2;

    /**
     * 路由类型为PUT请求
     *
     * @var int
     */
    const REQUEST_METHOD_PUT = 3;

    /**
     * 路由类型为PATCH请求
     *
     * @var int
     */
    const REQUEST_METHOD_PATCH = 4;

    /**
     * 路由类型为DELETE请求
     *
     * @var int
     */
    const REQUEST_METHOD_DELETE = 5;

    /**
     * 路由类型为OPTIONS请求
     *
     * @var int
     */
    const REQUEST_METHOD_OPTIONS = 6;

    /**
     * 路由类型为任意请求类型
     *
     * @var int
     */
    const REQUEST_METHOD_ANY = 7;

    /**
     * 路由类型 reset 路由
     *
     * @var int
     */
    const REST_ROUTE = 8;

    /**
     * 路由规则 [请求方法对应的数字常量]pattern => [/models]/controller/action
     * 'blog/:aid\d' =>'Site/Index/read',
     * 'category/:cid\d/:p\d' =>'Index/index',
     * 'search/:keywords/:p'=>'Index/index',
     * 当路由为REST_ROUTE路由时访问的时候会访问路由定义的方法名前加上访问方法如：
     * 定义了一条rest路由 'blog/:aid\d' =>'Site/Index/read' 当请求方法为GET时访问的方法为 Site模块Index控制器下的getRead方法当
     * 请求方法为POST时访问的方法为 Site模块Inde控制器下的postRead方法以此类推.
     *
     * @var array
     */
    private static $rules = [];

    /**
     * 解析得到的请求信息 含应用名、控制器、操作
     *
     * @var array
     */
    private static $urlParams = [
        'path' => '',
        'controller' => '',
        'action' => '',
        'root' => '',
    ];

    /**
     * 成功匹配到的路由
     *
     * @var string
     */
    private static $matchRoute = 'url_to_action';

    /**
     * 修改解析得到的请求信息 含应用名、控制器、操作
     *
     * @param string|array $key path|controller|action|root
     * @param string $val
     *
     * @return void
     */
    public function setUrlParams($key = 'path', $val = '')
    {
        if (is_array($key)) {
            self::$urlParams = array_merge(self::$urlParams, $key);
        } else {
            self::$urlParams[$key] = $val;
        }
    }

    /**
     * 解析url
     *
     * @return void
     */
    public function parseUrl()
    {
        \Cml\Route::parsePathInfo();

        $path = '/';

        //定义URL常量
        $subDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($subDir == '/' || $subDir == '\\') {
            $subDir = '';
        }
        //定义项目根目录地址
        self::$urlParams['root'] = $subDir . '/';

        $pathInfo = \Cml\Route::getPathInfo();

        //检测路由
        if (self::$rules) {//配置了路由，所有请求通过路由处理
            $isRoute = self::isRoute($pathInfo);
            if ($isRoute[0]) {//匹配路由成功
                if (is_array($isRoute['route'])) {
                    self::$urlParams['action'] = $isRoute['route'][2];
                    self::$urlParams['controller'] = $isRoute['route'][1];
                    $path = self::$urlParams['path'] = $isRoute['route'][0];
                    if (is_callable($isRoute['route'][3])) {
                        $isRoute['route'][3]();
                    }
                } else {
                    $routeArr = explode('/', $isRoute['route']);
                    $isRoute = null;
                    self::$urlParams['action'] = array_pop($routeArr);
                    self::$urlParams['controller'] = ucfirst(array_pop($routeArr));
                    $controllerPath = '';

                    $routeAppHierarchy = Config::get('route_app_hierarchy', 1);
                    $i = 0;
                    while ($dir = array_shift($routeArr)) {
                        if ($i++ < $routeAppHierarchy) {
                            $path .= $dir . '/';
                        } else {
                            $controllerPath .= $dir . '/';
                        }
                    }
                    self::$urlParams['controller'] = $controllerPath . self::$urlParams['controller'];
                    unset($routeArr);
                }
            } else {
                self::findAction($pathInfo, $path); //未匹配到路由 按文件名映射查找
            }
        } else {
            self::findAction($pathInfo, $path);//未匹配到路由 按文件名映射查找
        }

        $pathInfo = array_values($pathInfo);
        for ($i = 0; $i < count($pathInfo); $i += 2) {
            $_GET[$pathInfo[$i]] = $pathInfo[$i + 1];
        }

        unset($pathInfo);
        self::$urlParams['path'] = $path ? $path : '/';
        unset($path);
        $_REQUEST = array_merge($_REQUEST, $_GET);
    }

    /**
     * 匹配路由
     *
     * @param array $pathInfo
     *
     * @return mixed
     */
    private function isRoute(&$pathInfo)
    {
        empty($pathInfo) && $pathInfo[0] = '/';//网站根地址
        $isSuccess = [];
        $route = self::$rules;

        $httpMethod = isset($_POST['_method']) ? strtoupper($_POST['_method']) : strtoupper($_SERVER['REQUEST_METHOD']);

        switch ($httpMethod) {
            case 'GET':
                $rMethod = self::REQUEST_METHOD_GET;
                break;
            case 'POST':
                $rMethod = self::REQUEST_METHOD_POST;
                break;
            case 'PUT':
                $rMethod = self::REQUEST_METHOD_PUT;
                break;
            case 'PATCH':
                $rMethod = self::REQUEST_METHOD_PATCH;
                break;
            case 'DELETE':
                $rMethod = self::REQUEST_METHOD_DELETE;
                break;
            case 'OPTIONS':
                $rMethod = self::REQUEST_METHOD_OPTIONS;
                break;
            default :
                $rMethod = self::REQUEST_METHOD_ANY;
        }

        foreach ($route as $k => $v) {
            $rulesMethod = substr($k, 0, 1);
            if (
                $rulesMethod != $rMethod
                && $rulesMethod != self::REQUEST_METHOD_ANY
                && $rulesMethod != self::REST_ROUTE
            ) { //此条路由不符合当前请求方式
                continue;
            }
            unset($v);
            $singleRule = substr($k, 1);
            $arr = $singleRule === '/' ? [$singleRule] : explode('/', ltrim($singleRule, '/'));

            if ($arr[0] == $pathInfo[0]) {
                array_shift($arr);
                foreach ($arr as $key => $val) {
                    if (isset($pathInfo[$key + 1]) && $pathInfo[$key + 1] !== '') {
                        if (strpos($val, '\d') && !is_numeric($pathInfo[$key + 1])) {//数字变量
                            $route[$k] = false;//匹配失败
                            break 1;
                        } elseif (strpos($val, ':') === false && $val != $pathInfo[$key + 1]) {//字符串
                            $route[$k] = false;//匹配失败
                            break 1;
                        }
                    } else {
                        $route[$k] = false;//匹配失败
                        break 1;
                    }
                }
            } else {
                $route[$k] = false;//匹配失败
            }

            if ($route[$k] !== false) {//匹配成功的路由
                $isSuccess[] = $k;
            }
        }

        if (empty($isSuccess)) {
            $returnArr[0] = false;
        } else {
            //匹配到多条路由时 选择最长的一条（匹配更精确）
            usort($isSuccess, function ($item1, $item2) {
                return strlen($item1) >= strlen($item2) ? 0 : 1;
            });

            $parseGet = function () use ($isSuccess, &$pathInfo) {
                $successRoute = explode('/', $isSuccess[0]);
                foreach ($successRoute as $key => $val) {
                    $t = explode('\d', $val);
                    if (strpos($t[0], ':') !== false) {
                        $_GET[ltrim($t[0], ':')] = $pathInfo[$key];
                    }
                    unset($pathInfo[$key]);
                }
            };

            if (is_callable($route[$isSuccess[0]])) {
                $parseGet();
                \Cml\Route::executeCallableRoute($route[$isSuccess[0]], substr($isSuccess[0], 1));
            }

            is_array($route[$isSuccess[0]]) || $route[$isSuccess[0]] = trim(str_replace('\\', '/', $route[$isSuccess[0]]), '/');

            //判断路由的正确性
            if (!is_array($route[$isSuccess[0]]) && count(explode('/', $route[$isSuccess[0]])) < 2) {
                throw new \InvalidArgumentException(Lang::get('_ROUTE_PARAM_ERROR_', substr($isSuccess[0], 1)));
            }

            $returnArr[0] = true;

            $parseGet();

            if (substr($isSuccess[0], 0, 1) == self::REST_ROUTE) {
                $actions = explode('/', $route[$isSuccess[0]]);
                $arrKey = count($actions) - 1;
                $actions[$arrKey] = strtolower($httpMethod) . ucfirst($actions[$arrKey]);
                $route[$isSuccess[0]] = implode('/', $actions);
            }

            self::$matchRoute = substr($isSuccess[0], 1);
            $returnArr['route'] = $route[$isSuccess[0]];
        }
        return $returnArr;
    }

    /**
     * 获取子目录路径。若项目在子目录中的时候为子目录的路径如/sub_dir/、否则为/
     *
     * @return string
     */
    public function getSubDirName()
    {
        substr(self::$urlParams['root'], -1) != '/' && self::$urlParams['root'] .= '/';
        substr(self::$urlParams['root'], 0, 1) != '/' && self::$urlParams['root'] = '/' . self::$urlParams['root'];
        return self::$urlParams['root'];
    }

    /**
     * 获取应用目录可以是多层目录。如web、admin等.404的时候也必须有值用于绑定系统命令
     *
     * @return string
     */
    public function getAppName()
    {
        return trim(self::$urlParams['path'], '\\/');
    }

    /**
     * 获取控制器名称不带Controller后缀
     *
     * @return string
     */
    public function getControllerName()
    {
        return trim(self::$urlParams['controller'], '\\/');
    }

    /**
     * 获取控制器名称方法名称
     *
     * @return string
     */
    public function getActionName()
    {
        return trim(self::$urlParams['action'], '\\/');
    }

    /**
     * 获取不含子目录的完整路径 如: web/Goods/add
     *
     * @return string
     */
    public function getFullPathNotContainSubDir()
    {
        return self::getAppName() . '/' . self::getControllerName() . '/' . self::getActionName();
    }

    /**
     * 获取要执行的控制器类名及方法
     *
     */
    public function getControllerAndAction()
    {
        //控制器所在路径
        $appName = self::getAppName();
        $className = $appName . ($appName ? '/' : '') . Cml::getApplicationDir('app_controller_path_name') .
            '/' . self::getControllerName() . Config::get('controller_suffix');
        $actionController = Cml::getApplicationDir('apps_path') . '/' . $className . '.php';

        if (is_file($actionController)) {
            return ['class' => str_replace('/', '\\', $className), 'action' => self::getActionName(), 'route' => self::$matchRoute];
        } else {
            return false;
        }
    }

    /**
     * 从文件查找控制器
     *
     * @param array $pathInfo
     * @param string $path
     */
    private function findAction(&$pathInfo, &$path)
    {
        if ($pathInfo[0] == '/' && !isset($pathInfo[1])) {
            $pathInfo = explode('/', trim(Config::get('url_default_action'), '/'));
        }
        $controllerPath = $controllerName = '';

        $routeAppHierarchy = Config::get('route_app_hierarchy', 1);
        $i = 0;

        $controllerSuffix = Config::get('controller_suffix');
        while ($dir = array_shift($pathInfo)) {
            $controllerName = ucfirst($dir);
            $controller = Cml::getApplicationDir('apps_path') . $path . Cml::getApplicationDir('app_controller_path_name') . '/'
                . $controllerPath . $controllerName . $controllerSuffix . '.php';

            if ($i >= $routeAppHierarchy && is_file($controller)) {
                self::$urlParams['controller'] = $controllerPath . $controllerName;
                break;
            } else {
                if ($i++ < $routeAppHierarchy) {
                    $path .= $dir . '/';
                } else {
                    $controllerPath .= $dir . '/';
                }
            }
        }
        empty(self::$urlParams['controller']) && self::$urlParams['controller'] = $controllerName;//用于404的时候挂载插件用
        self::$urlParams['action'] = array_shift($pathInfo);
    }

    /**
     * 增加get访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function get($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_GET . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加post访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function post($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_POST . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加put访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function put($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_PUT . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加patch访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function patch($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_PATCH . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加delete访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function delete($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_DELETE . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加options访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function options($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_OPTIONS . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加任意访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function any($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_ANY . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 增加REST方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function rest($pattern, $action)
    {
        self::$rules[self::REST_ROUTE . self::patternFactory($pattern)] = $action;
        return $this;
    }

    /**
     * 分组路由
     *
     * @param string $namespace 分组名
     * @param callable $func 闭包
     */
    public function group($namespace, callable $func)
    {
        if (empty($namespace)) {
            throw new \InvalidArgumentException(Lang::get('_NOT_ALLOW_EMPTY_', '$namespace'));
        }

        self::$group = trim($namespace, '/');

        $func();

        self::$group = false;
    }

    /**
     * 组装路由规则
     *
     * @param $pattern
     *
     * @return string
     */
    private function patternFactory($pattern)
    {
        if (self::$group) {
            return self::$group . '/' . ltrim($pattern);
        } else {
            return $pattern;
        }
    }
}
