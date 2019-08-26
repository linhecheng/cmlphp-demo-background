<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 FastRoute封装实现 使用请先安装依赖composer require nikic/fast-route
 * *********************************************************** */
namespace Cml\Service;

use Cml\Cml;
use Cml\Config;
use Cml\Interfaces\Route;
use Cml\Lang;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * Url解析类,负责路由及Url的解析
 * self::get('blog/bb/{aid:[0-9]+}' , 'adminbase/Public/login');
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
     * 是否启用分组
     *
     * @var false
     */
    private static $group = false;

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
            self::$urlParams = array_merge(self::$urlParams, $key);
        } else {
            self::$urlParams[$key] = $val;
        }
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
        if (!self::$urlParams['path']) {
            $pathInfo = \Cml\Route::getPathInfo();
            self::$urlParams['path'] = $pathInfo[0];//用于绑定系统命令
        }
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
     * 解析url
     *
     * @return mixed
     */
    public function parseUrl()
    {
        \Cml\Route::parsePathInfo();

        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['uri'], $route['action']);
            }
        });

        $httpMethod = isset($_POST['_method']) ? strtoupper($_POST['_method']) : strtoupper($_SERVER['REQUEST_METHOD']);
        $routeInfo = $dispatcher->dispatch($httpMethod, implode('/', \Cml\Route::getPathInfo()));

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
                break;
            case Dispatcher::FOUND:
                $_GET += $routeInfo[2];
                if (is_callable($routeInfo[1])) {
                    \Cml\Route::executeCallableRoute($routeInfo[1], 'fastRoute');
                }
                $this->parseUrlParams($routeInfo[1]);
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
        //is_array($action) ? $action['__rest'] = 1 :  ['__rest' => 0, '__action' => $action];
        if (is_array($uri) && !isset($uri['__action'])) {
            self::$urlParams['path'] = $uri[0];
            self::$urlParams['controller'] = $uri[1];
            if (isset($uri['__rest'])) {
                self::$urlParams['action'] = strtolower(isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']) . ucfirst($uri[2]);
            } else {
                self::$urlParams['action'] = $uri[2];
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
                self::$urlParams['action'] = strtolower(isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']) . ucfirst(array_pop($routeArr));
            } else {
                self::$urlParams['action'] = array_pop($routeArr);
            }

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

            self::$urlParams['path'] = $path ? $path : '/';
            unset($path);
        }

        //定义URL常量
        $subDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($subDir == '/' || $subDir == '\\') {
            $subDir = '';
        }
        //定义项目根目录地址
        self::$urlParams['root'] = $subDir . '/';
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
            return ['class' => str_replace('/', '\\', $className), 'action' => self::getActionName(), 'route' => 'fastRoute'];
        } else {
            return false;
        }
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
        $this->addRoute('GET', $pattern, $action);

        return $this;
    }

    /**
     * 增加POST访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string|array $action 执行的操作
     *
     * @return $this
     */
    public function post($pattern, $action)
    {
        $this->addRoute('POST', $pattern, $action);

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
        $this->addRoute('PUT', $pattern, $action);

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
        $this->addRoute('PATCH', $pattern, $action);

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
        $this->addRoute('DELETE', $pattern, $action);

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
        $this->addRoute('OPTIONS', $pattern, $action);

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
        $this->addRoute($this->httpMethod, $pattern, $action);
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
        is_array($action) ? $action['__rest'] = 1 : $action = ['__rest' => 0, '__action' => $action];
        $this->addRoute($this->httpMethod, $pattern, $action);
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
     * 添加一个路由
     *
     * @param  array|string $method
     * @param  string $pattern
     * @param  mixed $action
     * @return void
     */
    private function addRoute($method, $pattern, $action)
    {

        if (is_array($method)) {
            foreach ($method as $verb) {
                $this->routes[$verb . $pattern] = ['method' => $verb, 'uri' => self::patternFactory($pattern), 'action' => $action];
            }
        } else {
            $this->routes[$method . $pattern] = ['method' => $method, 'uri' => self::patternFactory($pattern), 'action' => $action];
        }
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
