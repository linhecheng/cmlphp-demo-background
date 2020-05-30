<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 FastRoute封装实现 使用请先安装依赖composer require nikic/fast-route
 * *********************************************************** */

namespace Cml\Service;

use Closure;
use Cml\Cml;
use Cml\Config;
use Cml\Interfaces\Middleware;
use Cml\Interfaces\Route;
use Cml\Lang;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Cml\Route as RouteFace;
use function Cml\dd;
use function FastRoute\simpleDispatcher;

/**
 * Url解析类,负责路由及Url的解析
 * $this->>get('blog/bb/{aid:[0-9]+}' , ['adminbase', 'Public', 'login']);
 *
 * @package Cml
 */
class FastRoute implements Route
{
    /**
     * 路由规则
     *
     * @var array
     */
    protected $routes = [];

    /**
     * 路由使用的中间件
     *
     * @var array
     */
    private $middleware = [];

    /**
     * 是否启用分组
     *
     * @var array
     */
    private $group = [];

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
     * http方法
     *
     * @var array
     */
    private $httpMethod = [
        'GET',
        'POST',
        'PUT',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS'
    ];

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
        if (!$this->urlParams['path']) {
            $pathInfo = RouteFace::getPathInfo();
            $this->urlParams['path'] = $pathInfo[0];//用于绑定系统命令
        }
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
     * 解析url
     *
     * @return mixed
     */
    public function parseUrl()
    {
        RouteFace::parsePathInfo();

        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['uri'], [
                    'action' => $route['action'],
                    'middleware' => $this->middleware[$route['method'] . $route['uri']] ?? []
                ]);
            }
        });

        $httpMethod = isset($_POST['_method']) ? strtoupper($_POST['_method']) : strtoupper($_SERVER['REQUEST_METHOD']);
        $routeInfo = $dispatcher->dispatch($httpMethod, implode('/', RouteFace::getPathInfo()));

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
                break;
            case Dispatcher::FOUND:
                $_GET += $routeInfo[2];

                $this->urlParams['middleware'] = $routeInfo[1]['middleware'];
                $action = $routeInfo[1]['action'];
                if ($action instanceof Closure) {
                    RouteFace::executeCallableRoute($action, 'fastRoute');
                }
                $this->parseUrlParams($action);
                break;
        }

        return $dispatcher;
    }

    /**
     * 解析uri参数
     *
     * @param $uri
     */
    private function parseUrlParams($uri)
    {
        if (is_array($uri) && class_exists($uri[0])) {
            $this->urlParams['action'] = $uri[1];
            if ($uri['__rest']) {
                $this->urlParams['action'] = strtolower($_POST['_method'] ?? $_SERVER['REQUEST_METHOD']) . ucfirst($this->urlParams['action']);
            }

            $uri[0] = explode(Cml::getApplicationDir('app_controller_path_name') . '/', str_replace('\\', '/', $uri[0]));
            $this->urlParams['path'] = trim($uri[0][0], '/');
            $this->urlParams['controller'] = mb_substr(trim($uri[0][1], '/'), 0, -(mb_strlen(Config::get('controller_suffix'))));
        } else if (is_array($uri) && !isset($uri['__action'])) {
            $this->urlParams['path'] = $uri[0];
            $this->urlParams['controller'] = $uri[1];
            if (isset($uri['__rest'])) {
                $this->urlParams['action'] = strtolower(isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']) . ucfirst($uri[2]);
            } else {
                $this->urlParams['action'] = $uri[2];
            }
        } else {
            $rest = false;
            if (is_array($uri) && $uri['__rest'] === 0) {
                $rest = true;
                $uri = $uri['__action'];
            }
            $path = '/';
            $routeArr = explode('/', $uri);
            if ($rest) {
                $this->urlParams['action'] = strtolower(isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']) . ucfirst(array_pop($routeArr));
            } else {
                $this->urlParams['action'] = array_pop($routeArr);
            }

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

            $this->urlParams['path'] = $path ? $path : '/';
            unset($path);
        }

        //定义URL常量
        $subDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($subDir == '/' || $subDir == '\\') {
            $subDir = '';
        }
        //定义项目根目录地址
        $this->urlParams['root'] = $subDir . '/';
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
            return ['class' => str_replace('/', '\\', $className), 'action' => $this->getActionName(), 'route' => 'fastRoute'];
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
        $this->addRoute('GET', $pattern, $action, $middleware);

        return $this;
    }

    /**
     * 增加POST访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return $this
     */
    public function post($pattern, $action, $middleware = [])
    {
        $this->addRoute('POST', $pattern, $action, $middleware);

        return $this;
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
        $this->addRoute('PUT', $pattern, $action, $middleware);

        return $this;
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
        $this->addRoute('PATCH', $pattern, $action, $middleware);

        return $this;
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
        $this->addRoute('DELETE', $pattern, $action, $middleware);

        return $this;
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
        $this->addRoute('OPTIONS', $pattern, $action, $middleware);

        return $this;
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
        $this->addRoute($this->httpMethod, $pattern, $action, $middleware);
        return $this;
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
        is_array($action) ? $action['__rest'] = 1 : $action = ['__rest' => 0, '__action' => $action];
        $this->addRoute($this->httpMethod, $pattern, $action, $middleware);
        return $this;
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
     * 添加一个路由
     *
     * @param array|string $method http请求方法
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     * @param array $middleware 使用的中间件
     *
     * @return void
     */
    private function addRoute($method, $pattern, $action, $middleware)
    {
        if (is_array($method)) {
            foreach ($method as $verb) {
                $uri = $this->patternFactory($pattern, $verb);
                $this->routes[$verb . $uri] = ['method' => $verb, 'uri' => $uri, 'action' => $action];
                $this->middleware[$verb . $uri] = array_merge($this->middleware[$verb . $uri] ?? [], $middleware);
            }
        } else {
            $uri = $this->patternFactory($pattern, $method);
            $this->routes[$method . $uri] = ['method' => $method, 'uri' => $uri, 'action' => $action];
            $this->middleware[$method . $uri] = array_merge($this->middleware[$method . $uri] ?? [], $middleware);
        }
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
            $pattern = $this->group['namespace'] . '/' . ltrim($pattern);
            $this->middleware[$requestMethod . $pattern] = $this->group['middleware'];
            return $pattern;
        } else {
            return $pattern;
        }
    }
}
