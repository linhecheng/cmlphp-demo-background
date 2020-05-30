<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图 html渲染引擎
 * *********************************************************** */

namespace Cml\View;

use Cml\Cml;
use Cml\Config;
use Cml\Exception\FileCanNotReadableException;
use Cml\Exception\MkdirErrorException;
use Cml\Lang;
use Cml\Secure;

/**
 * 视图 html渲染引擎
 *
 * @package Cml\View
 */
class Html extends Base
{
    /**
     * 模板参数信息
     *
     * @var array
     */
    private $options = [];

    /**
     * 子模板block内容数组
     *
     * @var array
     */
    private $layoutBlockData = [];

    /**
     * 模板布局文件
     *
     * @var null
     */
    private $layout = null;

    /**
     * 要替换的标签
     *
     * @var array
     */
    private $pattern = [];

    /**
     * 替换后的内容
     *
     * @var array
     */
    private $replacement = [];

    /**
     * 构造方法
     *
     */
    public function __construct()
    {
        $this->options = [
            'templateDir' => 'templates' . DIRECTORY_SEPARATOR, //模板文件所在目录
            'cacheDir' => 'templates' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR, //缓存文件存放目录
            'autoUpdate' => true, //当模板文件改动时是否重新生成缓存
            'leftDelimiter' => preg_quote(Config::get('html_left_deper')),
            'rightDelimiter' => preg_quote(Config::get('html_right_deper'))
        ];

        //要替换的标签
        $this->pattern = [
            '#\<\?(=|php)(.+?)\?\>#s', //替换php标签
            '#' . $this->options['leftDelimiter'] . "(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?);?" . $this->options['rightDelimiter'] . '#', //替换变量 $a['name']这种一维数组以及$a['name']['name']这种二维数组
            '#' . $this->options['leftDelimiter'] . "(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替换$a.key这种一维数组
            '#' . $this->options['leftDelimiter'] . "(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替换$a.key.key这种二维数组

            //htmlspecialchars
            '#' . $this->options['leftDelimiter'] . "\\+(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?);?" . $this->options['rightDelimiter'] . '#', //替换变量 $a['name']这种一维数组以及$a['name']['name']这种二维数组
            '#' . $this->options['leftDelimiter'] . "\\+(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替换$a.key这种一维数组
            '#' . $this->options['leftDelimiter'] . "\\+(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替换$a.key.key这种二维数组
            '#' . $this->options['leftDelimiter'] . '\\+echo\s+(.+?)' . $this->options['rightDelimiter'] . '#s', //替换 echo

            '#' . $this->options['leftDelimiter'] . 'template\s+([a-z0-9A-Z_\.\/]+);?' . $this->options['rightDelimiter'] . '[\n\r\t]*#',//替换模板载入命令
            '#' . $this->options['leftDelimiter'] . 'eval\s+(.+?)' . $this->options['rightDelimiter'] . '#s',//替换eval
            '#' . $this->options['leftDelimiter'] . 'echo\s+(.+?)' . $this->options['rightDelimiter'] . '#s', //替换 echo
            '#' . $this->options['leftDelimiter'] . 'if\s+(.+?)' . $this->options['rightDelimiter'] . '#s',//替换if
            '#' . $this->options['leftDelimiter'] . '(elseif|elseif)\s+(.+?)' . $this->options['rightDelimiter'] . '#s', //替换 elseif
            '#' . $this->options['leftDelimiter'] . 'else' . $this->options['rightDelimiter'] . '#', //替换 else
            '#' . $this->options['leftDelimiter'] . '\/if' . $this->options['rightDelimiter'] . '#',//替换 /if
            '#' . $this->options['leftDelimiter'] . '(loop|foreach)\s+(\S+)\s+(\S+)\s*?' . $this->options['rightDelimiter'] . '#s',//替换loop|foreach
            '#' . $this->options['leftDelimiter'] . '(loop|foreach)\s+(\S+)\s+(\S+)\s+(\S+)\s*?' . $this->options['rightDelimiter'] . '#s',//替换loop|foreach
            '#' . $this->options['leftDelimiter'] . '\/(loop|foreach)' . $this->options['rightDelimiter'] . '#',//替换 /foreach|/loop
            '#' . $this->options['leftDelimiter'] . 'hook\s+([A-Za-z0-9_]+)(.*?)' . $this->options['rightDelimiter'] . '#i',//替换 hook
            '#' . $this->options['leftDelimiter'] . '(get|post|request)\s+(\w+?)\s*' . $this->options['rightDelimiter'] . '#i',//替换 get/post/request
            '#' . $this->options['leftDelimiter'] . 'lang\s+([A-Za-z0-9_\.\s*]+)(.*?)' . $this->options['rightDelimiter'] . '#i',//替换 lang
            '#' . $this->options['leftDelimiter'] . 'config\s+([A-Za-z0-9_\.]+)\s*' . $this->options['rightDelimiter'] . '#i',//替换 config
            '#' . $this->options['leftDelimiter'] . 'url\s+(.*?)\s*' . $this->options['rightDelimiter'] . '#i',//替换 url
            '#' . $this->options['leftDelimiter'] . 'public' . $this->options['rightDelimiter'] . '#i',//替换 {{public}}
            '#' . $this->options['leftDelimiter'] . 'self' . $this->options['rightDelimiter'] . '#i',//替换 {{self}}
            '#' . $this->options['leftDelimiter'] . 'token' . $this->options['rightDelimiter'] . '#i',//替换 {{token}}
            '#' . $this->options['leftDelimiter'] . 'controller' . $this->options['rightDelimiter'] . '#i',//替换 {{controller}}
            '#' . $this->options['leftDelimiter'] . 'action' . $this->options['rightDelimiter'] . '#i',//替换 {{action}}
            '#' . $this->options['leftDelimiter'] . 'urldeper' . $this->options['rightDelimiter'] . '#i',//替换 {{urldeper}}
            '#' . $this->options['leftDelimiter'] . ' \\?\\>[\n\r]*\\<\\?' . $this->options['rightDelimiter'] . '#', //删除 PHP 代码断间多余的空格及换行
            '#(href\s*?=\s*?"\s*?"|href\s*?=\s*?\'\s*?\')#',
            '#(src\s*?=\s*?"\s*?"|src\s*?=\s*?\'\s*?\')#',
            '#' . $this->options['leftDelimiter'] . 'assert\s+(.+?)\s*' . $this->options['rightDelimiter'] . '#i',//替换 assert
            '#' . $this->options['leftDelimiter'] . 'comment\s+(.+?)\s*' . $this->options['rightDelimiter'] . '#i',//替换 comment 模板注释
            '#' . $this->options['leftDelimiter'] . 'acl\s+(.+?)\s*' . $this->options['rightDelimiter'] . '#i',//替换 acl权限判断标识
            '#' . $this->options['leftDelimiter'] . '\/acl' . $this->options['rightDelimiter'] . '#i',//替换 /acl
            '#' . $this->options['leftDelimiter'] . 'datetime\s+(\S+?)\s*?\|(.*?)' . $this->options['rightDelimiter'] . '#i',//替换 /datetime
        ];

        //替换后的内容
        $this->replacement = [
            '&lt;?${1}${2}?&gt',
            '<?php echo ${1};?>',
            '<?php echo ${1}[\'${2}\'];?>',
            '<?php echo ${1}[\'${2}\'][\'${3}\'];?>',

            //htmlspecialchars
            '<?php echo htmlspecialchars(${1});?>',
            '<?php echo htmlspecialchars(${1}[\'${2}\']);?>',
            '<?php echo htmlspecialchars(${1}[\'${2}\'][\'${3}\']);?>',
            '<?php echo htmlspecialchars(${1});?>',

            '<?php require(\Cml\View::getEngine()->getFile(\'${1}\', 1)); ?>',
            '<?php ${1};?>',
            '<?php echo ${1};?>',
            '<?php if (${1}) { ?>',
            '<?php } elseif (${2}) { ?>',
            '<?php } else { ?>',
            '<?php } ?>',
            '<?php if (is_array(${2})) { foreach (${2} as ${3}) { ?>',
            '<?php if (is_array(${2})) { foreach (${2} as ${3} => ${4}) { ?>',
            '<?php } } ?>',
            '<?php echo \Cml\Plugin::hook("${1}"${2});?>',
            '<?php echo \Cml\Http\Input::${1}String("${2}");?>',
            '<?php echo \Cml\Lang::get("${1}"${2});?>',
            '<?php echo \Cml\Config::get("${1}");?>',
            '<?php \Cml\Http\Response::url(${1});?>',
            '<?php echo \Cml\Config::get("static__path", \Cml\Cml::getContainer()->make("cml_route")->getSubDirName());?>',//替换 {{public}}
            '<?php echo strip_tags($_SERVER["REQUEST_URI"]); ?>',//替换 {{self}}
            '<input type="hidden" name="CML_TOKEN" value="<?php echo \Cml\Secure::getToken();?>" />',//替换 {{token}}
            '<?php echo \Cml\Cml::getContainer()->make("cml_route")->getControllerName(); ?>',//替换 {{controller}}
            '<?php echo \Cml\Cml::getContainer()->make("cml_route")->getActionName(); ?>',//替换 {{action}}
            '<?php echo \Cml\Config::get("url_model") == 3 ? "&" : "?"; ?>',//替换 {{urldeper}}
            '',
            'href="javascript:void(0);"',
            'src="javascript:void(0);"',
            '<?php echo \Cml\Tools\StaticResource::parseResourceUrl("${1}");?>',//静态资源
            '',
            '<?php if (\Cml\Vendor\Acl::checkAcl("${1}")) { ?>',//替换 acl权限判断标识
            '<?php } ?>',// /acl
            '<?php echo date(trim("${2}"), ${1}); ?>',// /datetime
        ];
    }

    /**
     * 添加一个模板替换规则
     *
     * @param string $pattern 正则
     * @param string $replacement 替换成xx内容
     * @param bool $haveDelimiter $pattern的内容是否要带上左右定界符
     *
     * @return $this
     */
    public function addRule($pattern, $replacement, $haveDelimiter = true)
    {
        if ($pattern && $replacement) {
            $this->pattern = $haveDelimiter ? '#' . $this->options['leftDelimiter'] . $pattern . $this->options['rightDelimiter'] . '#s' : "#{$pattern}#s";
            $this->replacement = $replacement;
        }
        return $this;
    }

    /**
     * 设定模板配置参数
     *
     * @param string | array $name 参数名称
     * @param mixed $value 参数值
     *
     * @return $this
     */
    public function setHtmlEngineOptions($name, $value = '')
    {
        if (is_array($name)) {
            $this->options = array_merge($this->options, $name);
        } else {
            $this->options[$name] = $value;
        }
        return $this;
    }

    /**
     * 获取模板文件缓存
     *
     * @param string $file 模板文件名称
     * @param int $type 缓存类型0当前操作的模板的缓存 1包含的模板的缓存
     *
     * @return string
     */
    public function getFile($file, $type = 0)
    {
        $type == 1 && $file = $this->initBaseDir($file);//初始化路径
        //$file = str_replace([('/', '\\'], DIRECTORY_SEPARATOR, $file);
        $cacheFile = $this->getCacheFile($file);
        if ($this->options['autoUpdate']) {
            $tplFile = $this->getTplFile($file);
            if (!is_readable($tplFile)) {
                throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $tplFile));
            }
            if (!is_file($cacheFile)) {
                if ($type !== 1 && !is_null($this->layout)) {
                    if (!is_readable($this->layout)) {
                        throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $this->layout));
                    }
                }
                $this->compile($tplFile, $cacheFile, $type);
                return $cacheFile;
            }

            $compile = false;
            $tplMtime = filemtime($tplFile);
            $cacheMtime = filemtime($cacheFile);
            if ($cacheMtime && $tplMtime) {
                ($cacheMtime < $tplMtime) && $compile = true;
            } else {//获取mtime失败
                $compile = true;
            }

            if ($compile && $type !== 1 && !is_null($this->layout)) {
                if (!is_readable($this->layout)) {
                    throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $this->layout));
                }
            }

            //当子模板未修改时判断布局模板是否修改
            if (!$compile && $type !== 1 && !is_null($this->layout)) {
                if (!is_readable($this->layout)) {
                    throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $this->layout));
                }
                $layoutMTime = filemtime($this->layout);
                if ($layoutMTime) {
                    $cacheMtime < $layoutMTime && $compile = true;
                } else {
                    $compile = true;
                }
            }

            $compile && $this->compile($tplFile, $cacheFile, $type);
        }
        return $cacheFile;
    }

    /**
     * 对模板文件进行缓存
     *
     * @param string $tplFile 模板文件名
     * @param string $cacheFile 模板缓存文件名
     * @param int $type 缓存类型0当前操作的模板的缓存 1包含的模板的缓存
     *
     * @return mixed
     */
    private function compile($tplFile, $cacheFile, $type)
    {
        //取得模板内容
        //$template = file_get_contents($tplFile);
        $template = $this->getTplContent($tplFile, $type);

        //执行替换
        $template = preg_replace($this->pattern, $this->replacement, $template);

        if (!Cml::$debug) {
            /* 去除html空格与换行 */
            $find = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $template = preg_replace($find, $replace, $template);
            $template = str_replace('?><?php', '', $template);
        }

        //添加 头信息
        $template = '<?php if (!class_exists(\'\Cml\View\')) die(\'Access Denied\');?>' . $template;

        if (!$cacheFile) {
            return $template;
        }

        //写入缓存文件
        $this->makePath($cacheFile);
        file_put_contents($cacheFile, $template, LOCK_EX);
        return true;
    }

    /**
     * 获取模板文件内容  使用布局的时候返回处理完的模板
     *
     * @param $tplFile
     * @param int $type 缓存类型0当前操作的模板的缓存 1包含的模板的缓存
     *
     * @return string
     */
    private function getTplContent($tplFile, $type)
    {
        if ($type === 0 && !is_null($this->layout)) {//主模板且存在模板布局
            $layoutCon = file_get_contents($this->layout);
            $tplCon = file_get_contents($tplFile);

            //获取子模板内容
            $presult = preg_match_all(
                '#' . $this->options['leftDelimiter'] . 'to\s+([a_zA-Z]+?)' . $this->options['rightDelimiter'] . '(.*?)' . $this->options['leftDelimiter'] . '/to' . $this->options['rightDelimiter'] . '#is',
                $tplCon,
                $tmpl
            );
            $tplCon = null;
            if ($presult > 0) {
                array_shift($tmpl);
            }
            //保存子模板提取完的区块内容
            for ($i = 0; $i < $presult; $i++) {
                $this->layoutBlockData[$tmpl[0][$i]] = $tmpl[1][$i];
            }
            $presult = null;

            //将子模板内容替换到布局文件返回
            $layoutBlockData = &$this->layoutBlockData;
            $layoutCon = preg_replace_callback(
                '#' . $this->options['leftDelimiter'] . 'block\s+([a_zA-Z]+?)' . $this->options['rightDelimiter'] . '(.*?)' . $this->options['leftDelimiter'] . '/block' . $this->options['rightDelimiter'] . '#is',
                function ($matches) use ($layoutBlockData) {
                    array_shift($matches);
                    if (isset($layoutBlockData[$matches[0]])) {
                        //替换{parent}标签并返回
                        return str_replace(
                            $this->options['rightDelimiter'] . 'parent' . $this->options['rightDelimiter'],
                            $matches[1],
                            $layoutBlockData[$matches[0]]
                        );
                    } else {
                        return '';
                    }
                },
                $layoutCon
            );
            unset($layoutBlockData);
            $this->layoutBlockData = [];
            return $layoutCon;//返回替换完的布局文件内容
        } else {
            return file_get_contents($tplFile);
        }
    }

    /**
     * 获取模板文件名及路径
     *
     * @param string $file 模板文件名称
     *
     * @return string
     */
    private function getTplFile($file)
    {
        return $this->options['templateDir'] . $file . Config::get('html_template_suffix');
    }

    /**
     * 获取模板缓存文件名及路径
     *
     * @param string $file 模板文件名称
     *
     * @return string
     */
    private function getCacheFile($file)
    {
        return $this->options['cacheDir'] . $file . '.cache.php';
    }

    /**
     * 根据指定的路径创建不存在的文件夹
     *
     * @param string $path 路径/文件夹名称
     *
     * @return string
     */
    private function makePath($path)
    {
        $path = dirname($path);
        if (!is_dir($path) && !mkdir($path, 0700, true)) {
            throw new MkdirErrorException(Lang::get('_CREATE_DIR_ERROR_') . "[{$path}]");
        }
        return true;
    }

    /**
     * 初始化目录
     *
     * @param string $templateFile 模板文件名
     * @param bool|false $inOtherApp 是否在其它app
     *
     * @return string
     */
    private function initBaseDir($templateFile, $inOtherApp = false)
    {
        $baseDir = $inOtherApp ? $inOtherApp : Cml::getContainer()->make('cml_route')->getAppName();
        $baseDir && $baseDir .= '/';
        $baseDir .= Cml::getApplicationDir('app_view_path_name') . (Config::get('html_theme') != '' ? DIRECTORY_SEPARATOR . Config::get('html_theme') : '');

        if ($templateFile === '') {
            $baseDir .= '/' . Cml::getContainer()->make('cml_route')->getControllerName() . '/';
            $file = Cml::getContainer()->make('cml_route')->getActionName();
        } else {
            $templateFile = str_replace('.', '/', $templateFile);
            $baseDir .= DIRECTORY_SEPARATOR . dirname($templateFile) . DIRECTORY_SEPARATOR;
            $file = basename($templateFile);
        }

        $options = [
            'templateDir' => Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $baseDir, //指定模板文件存放目录
            'cacheDir' => Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . $baseDir, //指定缓存文件存放目录
            'autoUpdate' => true, //当模板修改时自动更新缓存
        ];

        $this->setHtmlEngineOptions($options);
        return $file;
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法，
     *
     * @param string $templateFile 指定要调用的模板文件 默认为空 由系统自动定位模板文件
     * @param bool $inOtherApp 是否为载入其它应用的模板
     *
     * @return void
     */
    public function display($templateFile = '', $inOtherApp = false)
    {
        $html = $this->fetch($templateFile, $inOtherApp);
        $this->sendHeader();
        echo $html;
        Cml::cmlStop();
    }

    /**
     * 重置所有参数
     *
     */
    public function reset()
    {
        $this->layout = null;
        $this->args = [];
        $this->layoutBlockData = [];
        return $this;
    }

    /**
     * 渲染模板获取内容 调用内置的模板引擎显示方法，
     *
     * @param string $templateFile 指定要调用的模板文件 默认为空 由系统自动定位模板文件
     * @param bool $inOtherApp 是否为载入其它应用的模板
     * @param bool $doNotSetDir 不自动根据当前请求设置目录模板目录。用于特殊模板显示
     * @param bool $donNotWriteCacheFileImmediateReturn 不要使用模板缓存，实时渲染(系统模板使用)
     *
     * @return string
     */
    public function fetch($templateFile = '', $inOtherApp = false, $doNotSetDir = false, $donNotWriteCacheFileImmediateReturn = false)
    {
        $this->setHeader('Content-Type', 'text/html; charset=' . Config::get('default_charset'));
        if (Config::get('form_token')) {
            Secure::setToken();
        }

        ob_start();
        if ($donNotWriteCacheFileImmediateReturn) {
            $tplFile = $this->getTplFile($doNotSetDir ? $templateFile : $this->initBaseDir($templateFile, $inOtherApp));
            if (!is_readable($tplFile)) {
                throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $tplFile));
            }
            empty($this->args) || extract($this->args, EXTR_PREFIX_SAME, "xxx");
            $return = $this->compile($tplFile, false, 0);
            eval('?>' . $return . '<?php ');
        } else {
            Cml::requireFile($this->getFile(($doNotSetDir ? $templateFile : $this->initBaseDir($templateFile, $inOtherApp))), $this->args);
        }

        $this->args = [];
        $this->reset();
        return ob_get_clean();
    }

    /**
     * 使用布局模板并返回内容
     *
     * @param string $templateFile 模板文件
     * @param string $layout 布局文件
     * @param bool|false $layoutInOtherApp 布局文件是否在其它应用
     * @param bool|false $tplInOtherApp 模板是否在其它应用
     *
     * @return string
     */
    public function fetchWithLayout($templateFile = '', $layout = 'master', $layoutInOtherApp = false, $tplInOtherApp = false)
    {
        $this->layout = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR
            . ($layoutInOtherApp ? $layoutInOtherApp : Cml::getContainer()->make('cml_route')->getAppName())
            . DIRECTORY_SEPARATOR . Cml::getApplicationDir('app_view_path_name') . DIRECTORY_SEPARATOR
            . (Config::get('html_theme') != '' ? Config::get('html_theme') . DIRECTORY_SEPARATOR : '')
            . 'layout' . DIRECTORY_SEPARATOR . $layout . Config::get('html_template_suffix');
        return $this->fetch($templateFile, $tplInOtherApp);
    }

    /**
     * 使用布局模板并渲染
     *
     * @param string $templateFile 模板文件
     * @param string $layout 布局文件
     * @param bool|false $layoutInOtherApp 布局文件是否在其它应用
     * @param bool|false $tplInOtherApp 模板是否在其它应用
     */
    public function displayWithLayout($templateFile = '', $layout = 'master', $layoutInOtherApp = false, $tplInOtherApp = false)
    {
        $this->layout = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR
            . ($layoutInOtherApp ? $layoutInOtherApp : Cml::getContainer()->make('cml_route')->getAppName())
            . DIRECTORY_SEPARATOR . Cml::getApplicationDir('app_view_path_name') . DIRECTORY_SEPARATOR
            . (Config::get('html_theme') != '' ? Config::get('html_theme') . DIRECTORY_SEPARATOR : '')
            . 'layout' . DIRECTORY_SEPARATOR . $layout . Config::get('html_template_suffix');
        $this->display($templateFile, $tplInOtherApp);
    }

    /**
     * 正常情况布局文件直接通过displayWithLayout方法指定，会自动从主题目录/layout里寻找。但是一些特殊情况要单独设置布局。
     *
     * @param string $layout 必须为绝对路径
     */
    public function setLayout($layout = '')
    {
        $layout && $this->layout = $layout;
    }
}
