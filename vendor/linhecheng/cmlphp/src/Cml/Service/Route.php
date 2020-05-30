<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 自带路由实现
 * *********************************************************** */

namespace Cml\Service;

use Closure;
use Cml\Cml;
use Cml\Config;
use Cml\Interfaces\Middleware;
use Cml\Lang;
use Cml\Interfaces\Route as RouteInterface;
use InvalidArgumentException;
use Cml\Route as RouteFace;
use function Cml\dd;

/**
 * Url解析类,负责路由及Url的解析
 *
 * @package Cml
 */
class Route implements RouteInterface
{
    /**
     * 分组信息
     *
     * @var array
     */
    private $group = [];

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
    private $rules = [];

    /**
     * 路由使用的中间件
     *
     * @var array
     */
    private $middleware = [];

    /**
     * 解析得到的请求信息 含应用名、控制器、操作、路由绑定的中间件
     *
     * @var array
     */
    private $urlParams = [
        'path' => '',
        'controller' => '',
        'action' => '',
        'root' => '',
        'middleware' => []
    ];

    /**
     * 成功匹配到的路由
     *
     * @var string
     */
    private $matchRoute = 'url_to_action';

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
            $this->urlParams = array_merge($this->urlParams, $key);
        } else {
            $this->urlParams[$key] = $val;
        }
    }

    /**
     * 解析url
     *
     * @return void
     */
    public function parseUrl()
    {
        RouteFace::parsePathInfo();

        $path = '/';

        //定义URL常量
        $subDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($subDir == '/' || $subDir == '\\') {
            $subDir = '';
        }
        //定义项目根目录地址
        $this->urlParams['root'] = $subDir . '/';

        $pathInfo = RouteFace::getPathInfo();

        //检测路由
        if ($this->rules) {//配置了路由，所有请求通过路由处理
            $isRoute = $this->isRoute($pathInfo);
            if ($isRoute[0]) {//匹配路由成功
                $isRoute = $isRoute['route'];

                if (class_exists($isRoute[0])) {
                    $this->urlParams['action'] = $isRoute[1];
                    $isRoute[0] = explode(Cml::getApplicationDir('app_controller_path_name') . '/', str_replace('\\', '/', $isRoute[0]));
                    $path = trim($isRoute[0][0], '/');
                    $this->urlParams['controller'] = mb_substr(trim($isRoute[0][1], '/'), 0, -(mb_strlen(Config::get('controller_suffix'))));
                } else if (is_array($isRoute)) {
                    $this->urlParams['action'] = $isRoute[2];
                    $this->urlParams['controller'] = $isRoute[1];
                    $path = $isRoute[0];
                    if (isset($isRoute[3]) && $isRoute[3] instanceof Closure) {
                        $isRoute[3]();
                    }
                } else {
                    $routeArr = explode('/', $isRoute);
                    $isRoute = null;
                    $this->urlParams['action'] = array_pop($routeArr);
                    $this->urlParams['controller'] = ucfirst(array_pop($routeArr));
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
                    $this->urlParams['controller'] = $controllerPath . $this->urlParams['controller'];
                    unset($routeArr);
                }
            } else {
                $this->findAction($pathInfo, $path); //未匹配到路由 按文件名映射查找
            }
        } else {
            $this->findAction($pathInfo, $path);//未匹配到路由 按文件名映射查找
        }

        $pathInfo = array_values($pathInfo);
        for ($i = 0; $i < count($pathInfo); $i += 2) {
            $_GET[$pathInfo[$i]] = $pathInfo[$i + 1];
        }

        unset($pathInfo);
        $this->urlParams['path'] = $path ? $path : '/';
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
        $route = $this->rules;

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

            if ($route[$isSuccess[0]] instanceof Closure) {
                $parseGet();
                RouteFace::executeCallableRoute($route[$isSuccess[0]], substr($isSuccess[0], 1));
            }

            is_array($route[$isSuccess[0]]) || $route[$isSuccess[0]] = trim(str_replace('\\', '/', $route[$isSuccess[0]]), '/');

            //判断路由的正确性
            if (!is_array($route[$isSuccess[0]]) && count(explode('/', $route[$isSuccess[0]])) < 2) {
                throw new InvalidArgumentException(Lang::get('_ROUTE_PARAM_ERROR_', substr($isSuccess[0], 1)));
            }

            $returnArr[0] = true;

            $parseGet();

            if (substr($isSuccess[0], 0, 1) == self::REST_ROUTE) {
                if (class_exists($route[$isSuccess[0]][0])) {
                    $route[$isSuccess[0]][1] = strtolower($httpMethod) . ucfirst($route[$isSuccess[0]][1]);
                } else {
                    $actions = explode('/', $route[$isSuccess[0]]);
                    $arrKey = count($actions) - 1;
                    $actions[$arrKey] = strtolower($httpMethod) . ucfirst($actions[$arrKey]);
                    $route[$isSuccess[0]] = implode('/', $actions);
                }
            }

            $this->matchRoute = substr($isSuccess[0], 1);
            $this->urlParams['middleware'] = $this->middleware[$isSuccess[0]] ?? [];
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
        substr($this->urlParams['root'], -1) != '/' && $this->urlParams['root'] .= '/';
        substr($this->urlParams['root'], 0, 1) != '/' && $this->urlParams['root'] = '/' . $this->urlParams['root'];
        return $this->urlParams['root'];
    }

    /**
     * 获取应用目录可以是多层目录。如web、admin等.404的时候也必须有值用于绑定系统命令
     *
     * @return string
     */
    public function getAppName()
    {
        return trim($this->urlParams['path'], '\\/');
    }

    /**
     * 获取控制器名称不带Controller后缀
     *
     * @return string
     */
    public function getControllerName()
    {
        return trim($this->urlParams['controller'], '\\/');
    }

    /**
     * 获取控制器名称方法名称
     *
     * @return string
     */
    public function getActionName()
    {
        return trim($this->urlParams['action'], '\\/');
    }

    /**
     * 获取不含子目录的完整路径 如: web/Goods/add
     *
     * @return string
     */
    public function getFullPathNotContainSubDir()
    {
        return $this->getAppName() . '/' . $this->getControllerName() . '/' . $this->getActionName();
    }

    /**
     * 获取要执行的控制器类名及方法
     *
     */
    public function getControllerAndAction()
    {
        //控制器所在路径
        $appName = $this->getAppName();
        $className = $appName . ($appName ? '/' : '') . Cml::getApplicationDir('app_controller_path_name') .
            '/' . $this->getControllerName() . Config::get('controller_suffix');
        $actionController = Cml::getApplicationDir('apps_path') . '/' . $className . '.php';

        if (is_file($actionController)) {
            return ['class' => str_replace('/', '\\', $className), 'action' => $this->getActionName(), 'route' => $this->matchRoute];
        } else {
            return false;
        }
    }

    /**
     * 获取中间件
     *
     * @return Middleware[]
     */
    public function getMiddleware()
    {
        return $this->urlParams['middleware'];
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
                $this->urlParams['controller'] = $controllerPath . $controllerName;
                break;
            } else {
                if ($i++ < $routeAppHierarchy) {
                    $path .= $dir . '/';
                } else {
                    $controllerPath .= $dir . '/';
                }
            }
        }
        empty($this->urlParams['controller']) && $this->urlParams['controller'] = $controllerName;//用于404的时候挂载插件用
        $this->urlParams['action'] = array_shift($pathInfo);
    }

    /**
     * 添加路由公用方法
     *
     * @param string $requestMethod http方法
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    private function addRoute($requestMethod, $pattern, $action, $middleware = [])
    {
        $pattern = $this->patternFactory($pattern, $requestMethod);
        $this->rules[$pattern] = $action;
        $this->middleware[$pattern] = array_merge($this->middleware[$pattern] ?? [], $middleware);
        return $this;
    }

    /**
     * 增加get访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function get($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_GET, $pattern, $action, $middleware);
    }

    /**
     * 增加post访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function post($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_POST, $pattern, $action, $middleware);
    }

    /**
     * 增加put访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function put($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_PUT, $pattern, $action, $middleware);
    }

    /**
     * 增加patch访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function patch($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_PATCH, $pattern, $action, $middleware);
    }

    /**
     * 增加delete访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function delete($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_DELETE, $pattern, $action, $middleware);
    }

    /**
     * 增加options访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function options($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_OPTIONS, $pattern, $action, $middleware);
    }

    /**
     * 增加任意访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function any($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REQUEST_METHOD_ANY, $pattern, $action, $middleware);
    }

    /**
     * 增加REST方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function rest($pattern, $action, $middleware = [])
    {
        return $this->addRoute(self::REST_ROUTE, $pattern, $action, $middleware);
    }

    /**
     * 分组路由
     *
     * @param string $namespace 分组名
     * @param callable $func 闭包
     * @param array $middleware 使用的中间件
     */
    public function group($namespace, callable $func, $middleware = [])
    {
        if (empty($namespace)) {
            throw new InvalidArgumentException(Lang::get('_NOT_ALLOW_EMPTY_', '$namespace'));
        }

        $this->group = [
            'namespace' => trim($namespace, '/'),
            'middleware' => $middleware
        ];

        $func();

        $this->group = null;
    }

    /**
     * 组装路由规则
     *
     * @param $pattern
     * @param string $requestMethod http方法
     *
     * @return string
     */
    private function patternFactory($pattern, $requestMethod)
    {
        if ($this->group) {
            $pattern = $requestMethod . $this->group['namespace'] . '/' . ltrim($pattern);
            $this->middleware[$pattern] = $this->group['middleware'];
            return $pattern;
        } else {
            return $requestMethod . $pattern;
        }
    }
}
