<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 异常、错误捕获 使用第三方错误捕获插件 必须封装实现本接口
 * *********************************************************** */

namespace Cml;

use Cml\Console\IO\Output;
use Cml\Http\Request;
use \Cml\Interfaces\ErrorOrException as ErrorOrExceptionInterface;

class ErrorOrException implements ErrorOrExceptionInterface
{
    /**
     * 致命错误捕获
     *
     * @param  array $error 错误信息
     *
     */
    public function fatalError(&$error)
    {
        if (!Cml::$debug) {
            //正式环境 只显示‘系统错误’并将错误信息记录到日志
            Log::emergency('fatal_error', [$error]);
            $error = [];
            $error['message'] = Lang::get('_CML_ERROR_');
        } else {
            $error['exception'] = 'Fatal Error';
            $error['files'][0] = [
                'file' => $error['file'],
                'line' => $error['line']
            ];
        }

        if (Request::isCli()) {
            Output::writeException(sprintf("%s\n[%s]\n%s", isset($error['files']) ? implode($error['files'][0], ':') : '', 'Fatal Error', $error['message']));
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            View::getEngine('html')->reset()->assign('error', $error);
            Cml::showSystemTemplate(Config::get('html_exception'));
        }
    }

    /**
     * 自定义异常处理
     *
     * @param mixed $e 异常对象
     */
    public function appException(&$e)
    {
        $error = [];
        $exceptionClass = new \ReflectionClass($e);
        $error['exception'] = '\\' . $exceptionClass->name;
        $error['message'] = $e->getMessage();
        $trace = $e->getTrace();
        foreach ($trace as $key => $val) {
            $error['files'][$key] = $val;
        }

        if (substr($e->getFile(), -20) !== '\Tools\functions.php' || $e->getLine() !== 90) {
            array_unshift($error['files'], ['file' => $e->getFile(), 'line' => $e->getLine(), 'type' => 'throw']);
        }

        if (!Cml::$debug) {
            //正式环境 只显示‘系统错误’并将错误信息记录到日志
            Log::emergency($error['message'], [$error['files'][0]]);

            $error = [];
            $error['message'] = Lang::get('_CML_ERROR_');
        }

        if (Request::isCli()) {
            Output::writeException(sprintf("%s\n[%s]\n%s", isset($error['files']) ? implode($error['files'][0], ':') : '', get_class($e), $error['message']));
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            View::getEngine('html')->reset()->assign('error', $error);
            Cml::showSystemTemplate(Config::get('html_exception'));
        }
    }
}
