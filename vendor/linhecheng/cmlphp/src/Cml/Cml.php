<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 项目基类
 * *********************************************************** */

namespace Cml;

use Cml\Http\Message\MiddlewareControllerTrait;
use Cml\Http\Message\Psr\ServerRequestCreator;
use Cml\Http\Message\RequestHandler;
use Cml\Http\Message\ResponseEmitter;
use Cml\Http\Request;
use Cml\Interfaces\Middleware;
use Cml\Middleware\ExecuteControllerMiddleware;

/**
 * 框架基础类,负责初始化应用的一系列工作,如配置初始化、语言包载入、错误异常机制的处理等
 *
 * @package Cml
 */
class Cml
{
    /**
     * 版本
     */
    const VERSION = 'v2.9.1';

    /**
     * 执行app/只是初始化环境
     *
     * @var bool
     */
    private static $run = false;

    /**
     * 是否为debug模式
     *
     * @var bool
     */
    public static $debug = false;

    /**
     * 应用容器
     *
     * @var null|Container
     */
    public static $container = null;

    /**
     * 应用路径
     *
     * @var array
     */
    private static $appDir = [];

    /**
     * 当前时间
     *
     * @var int
     */
    public static $nowTime = 0;

    /**
     * 当前时间含微秒
     *
     * @var int
     */
    public static $nowMicroTime = 0;

    /**
     * 致命错误记录日志的等级列表
     *
     * @var array
     */
    private static $fatalErrorLogLevel = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
        E_RECOVERABLE_ERROR
    ];

    /**
     * 警告日志的等级列表
     *
     * @var array
     */
    private static $warningLogLevel = [
        E_NOTICE,
        E_STRICT,
        E_DEPRECATED,
        E_USER_DEPRECATED,
        E_USER_NOTICE
    ];

    /**
     * 自动加载类库
     * 要注意的是 使用autoload的时候  不能手动抛出异常
     * 因为在自动加载静态类时手动抛出异常会导致自定义的致命错误捕获机制和自定义异常处理机制失效
     * 而 new Class 时自动加载不存在文件时，手动抛出的异常可以正常捕获
     * 这边即使文件不存在时没有抛出自定义异常也没关系，因为自定义的致命错误捕获机制会捕获到错误
     *
     * @param string $className
     */
    public static function autoloadComposerAdditional($className)
    {
        $className == 'Cml\Server' && class_alias('Cml\Service', 'Cml\Server');//兼容旧版本
        self::$debug && Debug::addTipInfo(Lang::get('_CML_DEBUG_ADD_CLASS_TIP_', $className), Debug::TIP_INFO_TYPE_INCLUDE_LIB);//在debug中显示包含的类
    }

    /**
     * 处理配置及语言包相关
     *
     */
    private static function handleConfigLang()
    {
        //引入框架惯例配置文件
        $cmlConfig = Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php');
        Config::init();

        //应用正式配置文件
        $appConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . Config::$isLocal . DIRECTORY_SEPARATOR . 'normal.php';

        is_file($appConfig) ? $appConfig = Cml::requireFile($appConfig)
            : exit('Config File [' . Config::$isLocal . '/normal.php] Not Found Please Check！');
        is_array($appConfig) || $appConfig = [];

        $commonConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'common.php';
        $commonConfig = is_file($commonConfig) ? Cml::requireFile($commonConfig) : [];

        Config::set(array_merge($cmlConfig, $commonConfig, $appConfig));//合并配置

        if (Config::get('debug')) {
            self::$debug = true;
            $GLOBALS['debug'] = true;//开启debug
            Debug::addTipInfo(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
            Debug::addTipInfo(Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . Config::$isLocal . DIRECTORY_SEPARATOR . 'normal.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
            empty($commonConfig) || Debug::addTipInfo(Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'common.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
        }

        //引入系统语言包
        Lang::set(Cml::requireFile((CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . Config::get('lang') . '.php')));
    }

    /**
     * 初始化运行环境
     *
     */
    private static function init()
    {
        define('CML_PATH', dirname(__DIR__)); //框架的路径
        define('CML_CORE_PATH', CML_PATH . DIRECTORY_SEPARATOR . 'Cml');// 系统核心类库目录
        define('CML_EXTEND_PATH', CML_PATH . DIRECTORY_SEPARATOR . 'Vendor');// 系统扩展类库目录

        self::handleConfigLang();

        //后面自动载入的类都会自动收集到Debug类下
        spl_autoload_register('Cml\Cml::autoloadComposerAdditional', true, true);

        //包含框架中的框架函数库文件
        Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Tools' . DIRECTORY_SEPARATOR . 'functions.php');

        //设置自定义捕获致命异常函数
        //普通错误由Cml\Debug::catcher捕获 php默认在display_errors为On时致命错误直接输出 为off时 直接显示服务器错误或空白页,体验不好
        register_shutdown_function(function () {
            if ($error = error_get_last()) {//获取最后一个发生的错误的信息。 包括提醒、警告、致命错误
                if (in_array($error['type'], self::$fatalErrorLogLevel)) { //当捕获到的错误为致命错误时 报告
                    if (Plugin::hook('cml.before_fatal_error', $error) == 'jump') {
                        return;
                    }

                    Cml::getContainer()->make('cml_error_or_exception')->fatalError($error);

                    Plugin::hook('cml.after_fatal_error', $error);
                }
            }

            Plugin::hook('cml.before_cml_stop');
        }); //捕获致命异常

        //设置自定义的异常处理函数。
        set_exception_handler(function ($e) {
            if (Plugin::hook('cml.before_throw_exception', $e) === 'resume') {
                return;
            }

            Cml::getContainer()->make('cml_error_or_exception')->appException($e);

            Plugin::hook('cml.after_throw_exception', $e);
        }); //手动抛出的异常由此函数捕获

        ini_set('display_errors', 'off');//屏蔽系统自带的错误输出

        //载入插件配置文件
        $pluginConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'plugin.php';
        is_file($pluginConfig) && Cml::requireFile($pluginConfig);

        Plugin::hook('cml.before_set_time_zone');//用于动态设置时区等。

        date_default_timezone_set(Config::get('time_zone')); //设置时区

        self::$nowTime = time();
        self::$nowMicroTime = microtime(true);

        //全局的自定义语言包
        $globalLang = Cml::getApplicationDir('global_lang_path') . DIRECTORY_SEPARATOR . Config::get('lang') . '.php';
        is_file($globalLang) && Lang::set(Cml::requireFile($globalLang));

        //设置调试模式
        if (Cml::$debug) {
            Debug::start();//记录开始运行时间\内存初始使用
            //设置捕获系统异常 使用set_error_handler()后，error_reporting将会失效。所有的错误都会交给set_error_handler。
            set_error_handler('\Cml\Debug::catcher');

            array_map(function ($class) {
                Debug::addTipInfo(Lang::get('_CML_DEBUG_ADD_CLASS_TIP_', $class), Debug::TIP_INFO_TYPE_INCLUDE_LIB);
            }, [
                'Cml\Cml',
                'Cml\Config',
                'Cml\Lang',
                'Cml\Http\Request',
                'Cml\Debug',
                'Cml\Interfaces\Debug',
                'Cml\Container',
                'Cml\Interfaces\Environment',
                get_class(self::getContainer()->make('cml_environment'))
            ]);
            $runTimeClassList = null;
        } else {
            $GLOBALS['debug'] = false;//关闭debug
            //ini_set('error_reporting', E_ALL & ~E_NOTICE);//记录除了notice之外的错误
            ini_set('log_errors', 'off'); //关闭php自带错误日志
            //严重错误已经通过fatalError记录。为了防止日志过多,默认不记录致命错误以外的日志。有需要可以修改配置开启
            if (Config::get('log_warn_log')) {
                set_error_handler('\Cml\Log::catcherPhpError');
            }

            //线上模式包含runtime.php
            $runTimeFile = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . '_runtime_.php';
            if (!is_file($runTimeFile)) {
                //程序运行必须的类
                $runTimeClassList = [
                    CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Controller.php',
                    CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Route.php',
                    CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Interfaces' . DIRECTORY_SEPARATOR . 'Route.php',
                    CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Interfaces' . DIRECTORY_SEPARATOR . 'Middleware.php',
                    CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Middleware' . DIRECTORY_SEPARATOR . 'ExecuteControllerMiddleware.php',
                ];
                Config::get('session_user') && $runTimeClassList[] = CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Session.php';

                $runTimeContent = '<?php';
                foreach ($runTimeClassList as $file) {
                    $runTimeContent .= str_replace(['<?php', '?>'], '', php_strip_whitespace($file));
                }
                file_put_contents($runTimeFile, $runTimeContent, LOCK_EX);
                $runTimeContent = null;
            }
            Cml::requireFile($runTimeFile);
        }

        if (Request::isCli()) {
            //兼容旧版直接运行方法
            if (self::$run && ($_SERVER['argc'] != 2 || strpos($_SERVER['argv'][1], '/') < 1)) {
                $console = Cml::getContainer()->make('cml_console');
                $userCommand = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'command.php';
                if (is_file($userCommand)) {
                    $commandList = Cml::requireFile($userCommand);
                    if (is_array($commandList) && count($commandList) > 0) {
                        $console->addCommands($commandList);
                    }
                }

                if ($console->run() !== 'don_not_exit') {
                    exit(0);
                }
            }
        } else {
            //header('X-Powered-By:CmlPHP');
            // 页面压缩输出支持
            $zlib = ini_get('zlib.output_compression');
            if (empty($zlib)) {
                php_sapi_name() === 'cli-server' && @ob_end_clean(); //防止在启动ob_start()之前程序已经有输出(比如配置文件尾多敲了换行)会导致服务器303错误
                ob_start(Config::get('output_encode') ? 'ob_gzhandler' : null);
                define('CML_OB_START', true);
            } else {
                define('CML_OB_START', false);
            }
        }

        Plugin::hook('cml.before_parse_url');

        //载入路由
        $routeConfigFile = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'route.php';
        is_file($routeConfigFile) && Cml::requireFile($routeConfigFile);

        Cml::getContainer()->make('cml_route')->parseUrl();//解析处理URL

        Plugin::hook('cml.after_parse_url');

        //载入模块配置
        $appConfig = Cml::getApplicationDir('apps_path')
            . '/' . Cml::getContainer()->make('cml_route')->getAppName() . '/'
            . Cml::getApplicationDir('app_config_path_name') . '/' . 'normal.php';
        is_file($appConfig) && Config::set(Cml::requireFile($appConfig));

        //载入模块语言包
        $appLang = Cml::getApplicationDir('apps_path')
            . '/' . Cml::getContainer()->make('cml_route')->getAppName() . '/'
            . Cml::getApplicationDir('app_lang_path_name') . '/' . Config::get('lang') . '.php';
        is_file($appLang) && Lang::set(Cml::requireFile($appLang));

        //载入模块插件
        $appPlugin = dirname($appConfig) . '/' . 'plugin.php';
        is_file($appPlugin) && Config::set(Cml::requireFile($appPlugin));
    }

    /**
     * 某些场景(如：跟其它项目混合运行的时候)只希望使用CmlPHP中的组件而不希望运行控制器，用来替代runApp
     *
     * @param callable $initDi 注入依赖
     */
    public static function onlyInitEnvironmentNotRunController(callable $initDi)
    {
        //初始化依赖
        $initDi();

        //系统初始化
        self::init();
    }

    /**
     * 获得容器
     *
     * @return Container
     */
    public static function getContainer()
    {
        if (is_null(self::$container)) {
            self::$container = new Container();
        }
        return self::$container;
    }

    /**
     * 启动应用
     *
     * @param callable $initDi 注入依赖
     */
    public static function runApp(callable $initDi)
    {
        self::$run = true;

        self::onlyInitEnvironmentNotRunController($initDi);

        self::cmlStop(self::requestHandler());
    }

    /**
     * 处理请求
     *
     */
    private static function requestHandler()
    {
        $controllerAction = Cml::getContainer()->make('cml_route')->getControllerAndAction();

        if (isset(class_uses($controllerAction['class'])[MiddlewareControllerTrait::class])) {
            $factory = self::getContainer()->make('psr17_http_factory');
            $creator = new ServerRequestCreator($factory, $factory, $factory, $factory);

            /**
             * @var RequestHandler $dispatcher
             */
            $dispatcher = self::getContainer()->make('psr15_request_handler', [self::getMiddleware()]);
            $dispatcher->add(new ExecuteControllerMiddleware());

            $response = $dispatcher->handle($creator->fromGlobals());
            $responseEmitter = new ResponseEmitter();
            $responseEmitter->emit($response);
            return $response->getHeaderLine('content-type');
        } else {
            (new ExecuteControllerMiddleware())->process();
            return null;
        }
    }

    /**
     * 获取中间件列表
     * 执行顺序 => 全局中间件 -> 应用级别中间件 -> 类级别中间件 -> 方法级别中间件
     *
     * @return Middleware[]
     */
    private static function getMiddleware()
    {
        $middles = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'middleware.php';
        if (is_file($middles)) {
            $middles = Cml::requireFile($middles);
            $needUse = $middles['global'] ?? [];
            $app = Cml::getContainer()->make('cml_route')->getAppName();
            foreach (($middles['app'] ?? []) as $patten => $mid) {
                if (preg_match('#' . str_replace('*', '(.*?)', $patten) . '#im', "{$app}/", $matches)) {
                    array_push($needUse, $mid);
                }
            }
            //加载模块的中间件
            $moduleMiddles = Cml::getApplicationDir('apps_path')
                . '/' . $app . '/'
                . Cml::getApplicationDir('app_config_path_name') . '/' . 'middleware.php';

            if (is_file($moduleMiddles)) {
                $moduleMiddles = Cml::requireFile($moduleMiddles);
                $middles['app'][$app] = array_merge((array)($middles['app'][$app] ?? []), (array)($moduleMiddles['app'] ?? []));
                $middles['controller'] = array_merge((array)($middles['controller'] ?? []), (array)($moduleMiddles['controller'] ?? []));
                $middles['action'] = array_merge((array)($middles['action'] ?? []), (array)($moduleMiddles['action'] ?? []));
            }

            $controllerActionMap = Cml::getContainer()->make('cml_route')->getControllerAndAction();
            $controller = $controllerActionMap['class'];
            $action = $controller . '@' . $controllerActionMap['action'];

            isset($middles['app'][$app]) && $needUse = array_merge($needUse, (array)$middles['app'][$app]);
            isset($middles['controller'][$controller]) && $needUse = array_merge($needUse, (array)$middles['controller'][$controller]);
            isset($middles['action'][$action]) && $needUse = array_merge($needUse, (array)$middles['action'][$action]);
            //获取路由中配置的中间件
            $routeMiddle = Cml::getContainer()->make('cml_route')->getMiddleware();
            $routeMiddle && $needUse = array_merge($needUse, $routeMiddle);

            return array_map(function ($middleware) {
                return new $middleware();
            }, $needUse);
        }
        return [];
    }

    /**
     * 未找到控制器的时候设置勾子
     *
     */
    public static function montFor404Page()
    {
        Plugin::mount('cml.before_show_404_page', [
            function () {
                $cmdLists = Config::get('cmlframework_system_route');
                $pathInfo = Route::getPathInfo();
                $cmd = strtolower(trim($pathInfo[0], '/'));
                if ($pos = strpos($cmd, '/')) {
                    $cmd = substr($cmd, 0, $pos);
                }
                if (isset($cmdLists[$cmd])) {
                    call_user_func($cmdLists[$cmd]);
                }
            }
        ]);
        Plugin::hook('cml.before_show_404_page');
    }

    /**
     * 程序中并输出调试信息
     *
     * @param string $contentType 响应类型
     */
    public static function cmlStop($contentType = null)
    {
        if (!$contentType || false !== stripos($contentType, 'text/html') || false !== stripos($contentType, 'text/plain')) {
            //输出Debug模式的信息
            if (self::$debug) {
                header('Content-Type:text/html; charset=' . Config::get('default_charset'));
                Debug::stop();
            } else {
                $deBugLogData = dump('', 1);
                if (!empty($deBugLogData)) {
                    Config::get('dump_use_php_console') ? dumpUsePHPConsole($deBugLogData) : Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'ConsoleLog.php', ['deBugLogData' => $deBugLogData]);
                }
            }
        }

        Plugin::hook('cml.before_ob_end_flush');
        CML_OB_START && ob_end_flush();
        exit();
    }

    /**
     * 以.的方式获取数组的值
     *
     * @param string $key
     * @param array $arr
     * @param null $default
     *
     * @return null
     */
    public static function doteToArr($key = '', &$arr = [], $default = null)
    {
        if (!strpos($key, '.')) {
            return isset($arr[$key]) ? $arr[$key] : $default;
        }

        // 获取多维数组
        $key = explode('.', $key);
        $tmp = null;
        foreach ($key as $k) {
            if (is_null($tmp)) {
                if (isset($arr[$k])) {
                    $tmp = $arr[$k];
                } else {
                    return $default;
                }
            } else {
                if (isset($tmp[$k])) {
                    $tmp = $tmp[$k];
                } else {
                    return $default;
                }
            }
        }
        return $tmp;
    }

    /**
     * 是否开启全局紧急模式
     *
     * @return bool
     */
    public static function isEmergencyMode()
    {
        return Config::get('emergency_mode_not_real_time_refresh_mysql_query_cache') !== false;
    }

    /**
     * 渲染显示系统模板
     *
     * @param string $tpl 要渲染的模板文件
     */
    public static function showSystemTemplate($tpl)
    {
        $configSubFix = Config::get('html_template_suffix');
        Config::set('html_template_suffix', '');
        $engine = View::getEngine('html');
        $html = $engine->setHtmlEngineOptions('templateDir', dirname($tpl) . DIRECTORY_SEPARATOR)
            ->fetch(basename($tpl), false, true, true);
        $engine->sendHeader();
        echo $html;
        Config::set('html_template_suffix', $configSubFix);
    }

    /**
     * 设置应用路径
     *
     * @param array $dir
     */
    public static function setApplicationDir(array $dir)
    {
        if (DIRECTORY_SEPARATOR == '\\') {//windows
            array_walk($dir, function (&$val) {
                $val = str_replace('/', DIRECTORY_SEPARATOR, $val);
            });
        }
        self::$appDir = array_merge(self::$appDir, $dir);
    }

    /**
     * 获取应用路径
     *
     * @param string $dir
     *
     * @return string | bool
     */
    public static function getApplicationDir($dir)
    {
        return isset(self::$appDir[$dir]) ? self::$appDir[$dir] : '';
    }

    /**
     * require 引入文件
     *
     * @param string $file 要引入的文件
     * @param array $args 要释放的变量
     *
     * @return mixed
     */
    public static function requireFile($file, $args = [])
    {
        empty($args) || extract($args, EXTR_PREFIX_SAME, "xxx");
        Cml::$debug && Debug::addTipInfo($file, Debug::TIP_INFO_TYPE_INCLUDE_FILE);
        return require $file;
    }

    /**
     * 动态获取容器绑定的实例
     *
     * @param string $name 要获取的绑定的实例名
     * @param string $arguments 第一个参数为绑定名称的前缀，默认为cml，目前有cml/view/db/cache几种前缀
     *
     * @return object
     */
    public static function __callStatic($name, $arguments)
    {
        $prefix = isset($arguments[0]) ? $arguments[0] : 'cml';
        return Cml::getContainer()->make($prefix . humpToLine($name));
    }

    /**
     * 获取警告日志的等级列表
     *
     * @return array
     */
    public static function getWarningLogLevel()
    {
        return self::$warningLogLevel;
    }

    /**
     * 设置警告日志的等级列表
     *
     * @return array
     */
    public static function getFatalErrorLogLevel()
    {
        return self::$fatalErrorLogLevel;
    }

    /**
     * 设置警告日志的等级列表
     *
     * @param array|int $level
     */
    public static function setWarningLogLevel($level)
    {
        if (is_array($level)) {
            self::$warningLogLevel = $level;
        } else {
            self::$warningLogLevel[] = $level;
        }
    }

    /**
     * 设置警告日志的等级列表
     *
     * @param array|int $level
     */
    public static function setFatalErrorLogLevel($level)
    {
        if (is_array($level)) {
            self::$fatalErrorLogLevel = $level;
        } else {
            self::$fatalErrorLogLevel[] = $level;
        }
    }
}
