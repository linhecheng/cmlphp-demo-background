<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 cml_error_or_exception服务Whoops实现 使用请先安装依赖composer require filp/whoops
 * *********************************************************** */
namespace Cml\Service;

use Cml\Cml;
use Cml\Config;
use Cml\Console\IO\Output;
use Cml\Http\Request;
use Cml\Interfaces\ErrorOrException;
use Cml\Lang;
use Cml\Log;
use Cml\View;
use Whoops\Exception\ErrorException;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * cml_error_or_exception服务Whoops实现
 *
 * @package Cml\Service
 */
class Whoops implements ErrorOrException
{
    /**
     * 致命错误捕获
     *
     * @param  array $error 错误信息
     */
    public function fatalError(&$error)
    {
        if (Cml::$debug) {
            $run = new Run();
            $run->pushHandler(Request::isCli() ? new PlainTextHandler() : new PrettyPageHandler());
            $run->handleException(new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']));
        } else {
            //正式环境 只显示‘系统错误’并将错误信息记录到日志
            Log::emergency('fatal_error', [$error]);
            $error = [];
            $error['message'] = Lang::get('_CML_ERROR_');

            if (Request::isCli()) {
                Output::writeException(sprintf("[%s]\n%s", 'Fatal Error', $error['message']));
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                View::getEngine('html')->reset()->assign('error', $error);
                Cml::showSystemTemplate(Config::get('html_exception'));
            }
        }
        exit;
    }

    /**
     * 自定义异常处理
     *
     * @param mixed $e 异常对象
     */
    public function appException(&$e)
    {
        if (Cml::$debug) {
            $run = new Run();
            $run->pushHandler(Request::isCli() ? new PlainTextHandler() : new PrettyPageHandler());
            $run->handleException($e);
        } else {
            $error = [];
            $error['message'] = $e->getMessage();
            $trace = $e->getTrace();
            $error['files'][0] = $trace[0];

            if (substr($e->getFile(), -20) !== '\Tools\functions.php' || $e->getLine() !== 90) {
                array_unshift($error['files'], ['file' => $e->getFile(), 'line' => $e->getLine(), 'type' => 'throw']);
            }

            //正式环境 只显示‘系统错误’并将错误信息记录到日志
            Log::emergency($error['message'], [$error['files'][0]]);

            $error = [];
            $error['message'] = Lang::get('_CML_ERROR_');

            if (Request::isCli()) {
                \Cml\pd($error);
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                View::getEngine('html')->reset()->assign('error', $error);
                Cml::showSystemTemplate(Config::get('html_exception'));
            }
        }
        exit;
    }
}
