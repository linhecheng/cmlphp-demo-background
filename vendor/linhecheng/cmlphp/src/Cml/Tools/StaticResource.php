<?php namespace Cml\Tools;

/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 静态资源管理
 * *********************************************************** */

use Cml\Cml;
use Cml\Config;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;
use Cml\Http\Request;
use Cml\Http\Response;
use Cml\Route;
use DirectoryIterator;

/**
 * 静态资源管理类
 *
 * @package Cml\Tools
 */
class StaticResource
{
    /**
     * 生成软链接
     *
     * @param null $rootDir 站点静态文件根目录默认为项目目录下的public目录
     */
    public static function createSymbolicLink($rootDir = null)
    {
        $isCli = Request::isCli();

        is_null($rootDir) && $rootDir = CML_PROJECT_PATH . DIRECTORY_SEPARATOR . 'public';
        is_dir($rootDir) || mkdir($rootDir, true, 0700);

        if ($isCli) {
            Output::writeln(Colour::colour('create link start!', [Colour::GREEN, Colour::HIGHLIGHT]));

            Output::writeln(Colour::colour("  ROOT DIR >>>> {$rootDir}", [Colour::WHITE]));
        } else {
            echo "<br />**************************create link start!*********************<br />";
            echo "  ROOT DIR >>>> {$rootDir} <br />";
        }
        //modules_static_path_name
        // 递归遍历目录

        $createDirFunc = function ($distDir, $resourceDir, $fileName) use ($isCli) {
            $cmd = Request::operatingSystem() ? "rd /Q {$distDir}" : "rm -rf {$distDir}";
            exec($cmd);
            $cmd = Request::operatingSystem() ? "mklink /d {$distDir} {$resourceDir}" : "ln -s {$resourceDir} {$distDir}";
            is_dir($distDir) || exec($cmd, $result);
            $tip = "  create link Application [{$fileName}] result : ["
                . (is_dir($distDir) ? 'true' : 'false') . "]";
            if ($isCli) {
                Output::writeln(Colour::colour($tip, [Colour::WHITE, Colour::HIGHLIGHT]));
            } else {
                print_r('|<span style="color:blue">' . str_pad($tip, 64, ' ', STR_PAD_BOTH) . '</span>|');
            }
        };

        //创建系统静态文件目录映射
        /* $createDirFunc(
             $rootDir . DIRECTORY_SEPARATOR . 'cmlphpstatic',
             __DIR__ . DIRECTORY_SEPARATOR . 'Static',
             'cmlphpstatic'
         );*/

        $dirIteratorFunc = function ($dir, $parentDirName = '') use ($rootDir, $createDirFunc, &$dirIteratorFunc) {
            $dirIterator = new DirectoryIterator($dir);

            foreach ($dirIterator as $file) {
                if (!$file->isDot() && $file->isDir()) {
                    $resourceDir = $file->getPathname() . DIRECTORY_SEPARATOR . Cml::getApplicationDir('app_static_path_name');

                    $currentDirName = ltrim($parentDirName, DIRECTORY_SEPARATOR);
                    $currentDirName .= ($currentDirName ? DIRECTORY_SEPARATOR : '') . $file->getFilename();

                    if (is_dir($resourceDir)) {
                        if ($file->getFilename() != $currentDirName) {
                            $parentDir = trim(substr($currentDirName, 0, strpos($currentDirName, $file->getFilename())), DIRECTORY_SEPARATOR);
                            is_dir($rootDir . DIRECTORY_SEPARATOR . $parentDir) || mkdir($rootDir . DIRECTORY_SEPARATOR . $parentDir, 0700, true);
                        }
                        $distDir = $rootDir . DIRECTORY_SEPARATOR . $currentDirName;
                        $createDirFunc($distDir, $resourceDir, $currentDirName);
                    } /*else if (!is_dir($file->getPathname() . DIRECTORY_SEPARATOR . Cml::getApplicationDir('app_controller_path_name'))) {
                        $dirIteratorFunc($file->getPathname(), $currentDirName);
                    }*/
                    $dirIteratorFunc($file->getPathname(), $currentDirName);//找到resource了还是继续迭代子目录
                }
            }
        };

        $dirIteratorFunc(Cml::getApplicationDir('apps_path'));

        if ($isCli) {
            Output::writeln(Colour::colour('create link end!', [Colour::GREEN, Colour::HIGHLIGHT]));
        } else {
            echo("<br />****************************create link end!**********************<br />");
        }
    }

    /**
     * 解析一个静态资源的地址
     *
     * @param string $resource 文件地址
     * @param bool $echo 是否输出  true输出 false return
     *
     * @return mixed
     */
    public static function parseResourceUrl($resource = '', $echo = true)
    {
        //简单判断没有.的时候当作是目录不加版本号
        $resource = ltrim($resource, '/');
        $isDir = strpos($resource, '.') === false ? true : false;
        if (Cml::$debug) {
            $file = Response::url("cmlframeworkstaticparse/{$resource}", false);
            if (Config::get('url_model') == 2) {
                $file = str_replace(Config::get('url_html_suffix'), '', $file);
            }

            $isDir || $file .= (Config::get("url_model") == 3 ? "&v=" : "?v=") . 0;//Cml::$nowTime;
        } else {
            $file = rtrim(Config::get("static__path", Cml::getContainer()->make('cml_route')->getSubDirName()), '/') . '/' . $resource;
            $isDir || $file .= "?v=" . Config::get('static_file_version');
        }
        if ($echo) {
            echo $file;
            return '';
        } else {
            return $file;
        }
    }

    /**
     * 解析一个静态资源的内容
     *
     */
    public static function parseResourceFile()
    {
        if (Cml::$debug) {
            $file = '';
            $pathInfo = array_map(function ($item) {
                return trim($item, '.');
            }, Route::getPathInfo());

            array_shift($pathInfo);

            if (isset($pathInfo[0]) && $pathInfo[0] == 'cmlphpstatic') {
                array_shift($pathInfo);
                $file = __DIR__ . DIRECTORY_SEPARATOR . 'Static' . DIRECTORY_SEPARATOR;
                $file .= trim(implode('/', $pathInfo), '/');
                strpos($file, '.') || $file .= Config::get('url_html_suffix');
            } else {
                $resource = implode('/', $pathInfo);
                $appName = '';
                $i = 0;
                //$routeAppHierarchy = Config::get('route_app_hierarchy', 1);
                while (true) {
                    $resource = ltrim($resource, '/');
                    $pos = strpos($resource, '/');
                    $appName = ($appName == '' ? '' : $appName . DIRECTORY_SEPARATOR) . substr($resource, 0, $pos);
                    $resource = substr($resource, $pos);
                    $file = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR
                        . Cml::getApplicationDir('app_static_path_name') . $resource;
                    strpos($file, '.') || $file .= Config::get('url_html_suffix');
                    if (is_file($file) || ++$i > 15/*|| ++$i >= $routeAppHierarchy*/) {
                        break;
                    }
                }
            }

            if (is_file($file)) {
                Response::sendContentTypeBySubFix(substr($file, strrpos($file, '.') + 1));
                exit(file_get_contents($file));
            } else {
                Response::sendHttpStatus(404);
            }
        }
    }
}
