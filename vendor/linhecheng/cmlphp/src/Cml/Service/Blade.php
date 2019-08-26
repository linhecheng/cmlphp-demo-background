<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Blade封装实现 使用请先安装依赖composer require pfinal/blade
 * *********************************************************** */
namespace Cml\Service;

use Cml\Cml;
use Cml\Config;
use Cml\View\Base;
use CmlExt\Blade\BladeCompiler;
use CmlExt\Blade\Factory;
use CmlExt\Blade\FileViewFinder;

/**
 * blade模板引擎封装实现
 *
 * @package Cml
 */
class Blade extends Base
{
    /**
     * 自定义规则
     *
     * @var array
     */
    private $rule = [];

    /**
     * 初始化目录
     *
     * @param string $templateFile 模板文件名
     *
     * @return string
     */
    private function initBaseDir($templateFile)
    {
        $baseDir = Cml::getContainer()->make('cml_route')->getAppName();
        $baseDir && $baseDir .= '/';
        $baseDir .= Cml::getApplicationDir('app_view_path_name') . (Config::get('html_theme') != '' ? DIRECTORY_SEPARATOR . Config::get('html_theme') : '');

        $layOutRootDir = $baseDir;
        if ($templateFile === '') {
            $baseDir .= '/' . Cml::getContainer()->make('cml_route')->getControllerName() . '/';
            $file = Cml::getContainer()->make('cml_route')->getActionName();
        } else {
            $templateFile = str_replace('.', '/', $templateFile);
            $baseDir .= DIRECTORY_SEPARATOR . dirname($templateFile) . DIRECTORY_SEPARATOR;
            $file = basename($templateFile);
        }

        return [
            'layoutDir' => Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $layOutRootDir,
            'layoutCacheRootPath' => Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . $layOutRootDir . DIRECTORY_SEPARATOR,
            'templateDir' => Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $baseDir, //指定模板文件存放目录
            'cacheDir' => Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . $baseDir, //指定缓存文件存放目录
            'file' => $file
        ];
    }

    /**
     * 抽象display
     *
     * @param string $templateFile 模板文件
     *
     * @return mixed
     */
    public function display($templateFile = '')
    {
        $options = $this->initBaseDir($templateFile);
        $compiler = new BladeCompiler($options['cacheDir'], $options['layoutCacheRootPath']);

        $compiler->directive('datetime', function ($timestamp) {
            return preg_replace('/\(\s*?(\S+?)\s*?\|(.*?)\)/i', '<?php echo date(trim("${2}"), ${1}); ?>', $timestamp);
        });

        $compiler->directive('hook', function ($hook) {
            return preg_replace('/\((.*?)\)/', '<?php \Cml\Plugin::hook("$1"); ?>', $hook);
        });


        $compiler->directive('urldeper', function () {
            return '<?php echo \Cml\Config::get("url_model") == 3 ? "&" : "?"; ?>';
        });

        $compiler->directive('get', function ($key) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Http\Input::getString("${1}");?>', $key);
        });

        $compiler->directive('post', function ($key) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Http\Input::postString("${1}");?>', $key);
        });

        $compiler->directive('request', function ($key) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Http\Input::requestString("${1}");?>', $key);
        });

        $compiler->directive('url', function ($key) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Http\Response::url("${1}"); ?>', $key);
        });

        $compiler->directive('public', function () {
            return '<?php echo \Cml\Config::get("static__path", \Cml\Cml::getContainer()->make("cml_route")->getSubDirName());?>';
        });

        $compiler->directive('token', function () {
            return '<input type="hidden" name="CML_TOKEN" value="<?php echo \Cml\Secure::getToken();?>" />';
        });

        $compiler->directive('lang', function ($lang) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Lang::get("${1}"); ?>', $lang);
        });

        $compiler->directive('config', function ($config) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Config::get("${1}"); ?>', $config);
        });

        $compiler->directive('assert', function ($url) {
            return preg_replace('/\((.*?)\)/', '<?php echo \Cml\Tools\StaticResource::parseResourceUrl("${1}"); ?>', $url);
        });

        $compiler->directive('acl', function ($url) {
            return preg_replace('/\((.*?)\)/', '<?php if (\Cml\Vendor\Acl::checkAcl("${1}")) : ?>', $url);
        });

        $compiler->directive('endacl', function () {
            return '<?php endif; ?>';
        });

        foreach ($this->rule as $pattern => $func) {
            $compiler->directive($pattern, $func);
        }


        $finder = new FileViewFinder([$options['templateDir'], $options['layoutDir']]);

        $finder->addExtension(trim(Config::get('html_template_suffix'), '.'));

        $factory = new Factory($compiler, $finder);

        header('Content-Type:text/html; charset=' . Config::get('default_charset'));
        echo $factory->make($options['file'], $this->args)->render();
        Cml::cmlStop();
        return;
    }

    /**
     * 添加一个模板替换规则
     *
     * @param string $pattern 正则
     * @param callable $func 执行的闭包函数
     *
     * @return $this
     */
    public function addRule($pattern, callable $func)
    {
        $this->rule[$pattern] = $func;
        return $this;
    }
}
