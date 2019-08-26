<?php
//应用入口
use Cml\Cml;

//定义项目根目录
define('CML_PROJECT_PATH', dirname(__DIR__));
define('CML_URL_DEFAULT_ACTION', 'adminbase/System/Index/index');//应用默认的action。不同的入口可自行更改

//载入composer自动加载文件
$loader = require CML_PROJECT_PATH . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

//配置目录组成
Cml::setApplicationDir([
    'secure_src' => CML_PROJECT_PATH . DIRECTORY_SEPARATOR . 'proj3e9xooelsxooeeewsb',
    'app_config_path_name' => 'Config',//自动载入配置用
    'app_lang_path_name' => 'Lang',//自动载入语言用
    'app_view_path_name' => 'View',//渲染模板用
    'app_controller_path_name' => 'Controller',//路由根据请求url映射控制器用
    'app_static_path_name' => 'Resource', //静态资源目录名
]);
//根据上面配置的目录，配置其它目录
Cml::setApplicationDir([
    'apps_path' => Cml::getApplicationDir('secure_src') . '/Application',//应用存放目录其下可能有多个应用
    'global_config_path' => Cml::getApplicationDir('secure_src') . '/' . Cml::getApplicationDir('app_config_path_name'),
    'global_lang_path' => Cml::getApplicationDir('secure_src') . '/' . Cml::getApplicationDir('app_lang_path_name'),
    'global_store_path' => Cml::getApplicationDir('secure_src') . '/Store',
    'runtime_cache_path' => Cml::getApplicationDir('secure_src') . '/Store' . DIRECTORY_SEPARATOR . 'Cache',
    'runtime_logs_path' => Cml::getApplicationDir('secure_src') . '/Store' . DIRECTORY_SEPARATOR . 'Logs',
]);

$loader->setPsr4('', Cml::getApplicationDir('apps_path'));

//注入服务并运行应用
//要注意的是这边只是做绑定并没有真正实例化
Cml::runApp(function () {
    /*********注意**********
     * 框架只要求php5.4+即可。但是下面用了php5.5的语法::class，
     * 如果php版本不支持::class的语法直接把相应的xxx::class改成字符串即可。
     * 如\Cml\ErrorOrException::class直接改成'\Cml\ErrorOrException'
     ***********************/

    //必须绑定。系统错误及异常捕获机制 如果想使用第三方的服务只要简单封装一个服务。实现\Cml\Interfaces\ErrorOrException接口即可
    Cml::getContainer()->singleton('cml_error_or_exception', \Cml\ErrorOrException::class);
    // Cml::getContainer()->singleton('cml_error_or_exception', \Cml\Service\Whoops::class);//Whoops封装服务使用前请安装Whoops. composer require filp/whoops

    //必须绑定。环境解析。自带的服务实现development/product/cli三种。可以根据需要实现更多的环境
    Cml::getContainer()->singleton('cml_environment', \Cml\Service\Environment::class);

    //必须绑定。系统日志驱动 内置\Cml\Logger\File::class｜\Cml\Logger\Redis::class两种.
    //自定义服务实现\Cml\Interfaces\Logger接口即可或继承\Cml\Logger\Base再按需重载
    Cml::getContainer()->singleton('cml_log', \Cml\Logger\File::class);

    //必须绑定。路由
    //框架自带的路由支持restful分格的路由、路由分组。 在未声明/未匹配到路由规则时会按url映射到文件的方式来执行相应的控制器方法。具体参考 http://doc.cmlphp.com/devintro/route/readme.html。
    //如果想使用第三方的路由只要简单封装一个服务。实现\Cml\Interfaces\Route接口即可
    Cml::getContainer()->singleton('cml_route', \Cml\Service\Route::class);
    //Cml::getContainer()->singleton('cml_route', \Cml\Service\FastRoute::class);   //composer require nikic/fast-route

    //开发模式必须绑定。Debug调试信息
    //如果想使用第三方的调试台只要简单封装一个服务。实现\Cml\Interfaces\Debug接口即可/当然直接修改模板也可以。配置项 'debug_page' => CML_CORE_PATH.'/Tpl/debug.tpl', // debug调试信息模板
    Cml::getContainer()->singleton('cml_debug', \Cml\Debug::class);

    //必须绑定。命令行组件
    Cml::getContainer()->singleton('cml_console', \Cml\Console\Console::class);

    //可选，队列服务 内置 \Cml\Queue\Redis::class.(内置的redis服务与缓存挂钩)参考 http://doc.cmlphp.com/devintro/quenue.html
    //自定义服务实现\Cml\Interfaces\Queue接口即可或继承\Cml\Queue\Base再按需重载
    Cml::getContainer()->singleton('cml_queue', \Cml\Queue\Redis::class);

    //可选，锁服务 内置\Cml\Lock\File::class｜\Cml\Lock\Redis::class｜\Cml\Lock\Memcache::class三种.
    //内置的redis锁跟/memcache锁 跟缓存服务挂钩。参考 http://doc.cmlphp.com/devintro/lock.html
    //自定义服务实现\Cml\Interfaces\Lock接口即可或继承\Cml\Lock\Base再按需重载
    Cml::getContainer()->singleton('cml_lock', \Cml\Lock\Redis::class);

    //可选。绑定要用到视图引擎内置以下5种 以view_为前缀，用的时候不用带前缀如使用view_html视图服务： \Cml\View::getEngine('html');
    //\Cml\View::getEngine();不传类型的时候，使用的引擎可在配置文件中配置 'view_render_engine' => 'Html'默认为view_html
    //自定义服务实现\Cml\Interfaces\View接口即可或继承\Cml\View\Base再按需重载
    Cml::getContainer()->singleton('view_html', \Cml\View\Html::class);
    Cml::getContainer()->singleton('view_json', \Cml\View\Json::class);
    Cml::getContainer()->singleton('view_blade', \Cml\Service\Blade::class); //blade模板引擎，使用前安装依赖。composer require linhecheng/cmlphp-ext-blade
    //Cml::getContainer()->singleton('view_excel', \Cml\View\Excel::class);
    //Cml::getContainer()->singleton('view_xml', \Cml\View\Xml::class);

    //可选，db 允许多种驱动同时使用。因同种数据库可能同时连多个.这边不使用单例绑定.内置 \Cml\Db\MySql\Pdo::class｜\Cml\Db\MongoDB\MongoDB::class 两种数据库支持.
    //自定义数据库驱动实现\Cml\Interfaces\Db接口即可或继承\Cml\Db\Base再按需重载
    Cml::getContainer()->bind('db_mysql', \Cml\Db\MySql\Pdo::class);
    //Cml::getContainer()->bind('db_mongodb', \Cml\Db\MongoDB\MongoDB::class);

    //可选，cache  允许多种驱动同时使用。如即使用memcache又使用redis.有使用数据库时至少要启用一种缓存,因同种缓存可能同时连多个.这边不使用单例绑定。
    // 内置 \Cml\Cache\Redis::class｜\Cml\Cache\File::class | \Cml\Cache\Memcache::class ｜ \Cml\Cache\Apc::class 四种缓存支持.
    //自定义数据库驱动实现\Cml\Interfaces\Cache接口即可或继承\Cml\Cache\Base再按需重载
    Cml::getContainer()->bind('cache_redis', \Cml\Cache\Redis::class);
    Cml::getContainer()->bind('cache_file', \Cml\Cache\File::class);
    //Cml::getContainer()->bind('cache_memcache', \Cml\Cache\Memcache::class);
    //Cml::getContainer()->bind('cache_apc', \Cml\Cache\Memcache::class);
});