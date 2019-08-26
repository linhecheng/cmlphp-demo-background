<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Log处理类
 * *********************************************************** */

namespace Cml;

use Cml\Logger\Base;

/**
 * Log处理类,简化的psr-3日志接口,负责Log的处理
 *
 * @package Cml
 */
class Log
{
    /**
     * 获取Logger实例
     *
     * @return Base
     */
    private static function getLogger()
    {
        return Cml::getContainer()->make('cml_log');
    }

    /**
     * 添加debug类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function debug($log, array $context = [])
    {
        return self::getLogger()->debug($log, $context);
    }

    /**
     * 添加info类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function info($log, array $context = [])
    {
        return self::getLogger()->info($log, $context);
    }

    /**
     * 添加notice类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function notice($log, array $context = [])
    {
        return self::getLogger()->notice($log, $context);
    }

    /**
     * 添加warning类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function warning($log, array $context = [])
    {
        return self::getLogger()->warning($log, $context);
    }

    /**
     * 添加error类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function error($log, array $context = [])
    {
        return self::getLogger()->error($log, $context);
    }

    /**
     * 添加critical类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function critical($log, array $context = [])
    {
        return self::getLogger()->critical($log, $context);
    }

    /**
     * 添加critical类型的日志
     *
     * @param string $log 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public static function emergency($log, array $context = [])
    {
        return self::getLogger()->emergency($log, $context);
    }

    /**
     * 错误日志handler
     *
     * @param int $errorType 错误类型 分运行时警告、运行时提醒、自定义错误、自定义提醒、未知等
     * @param string $errorTip 错误提示
     * @param string $errorFile 发生错误的文件
     * @param int $errorLine 错误所在行数
     *
     * @return void
     */
    public static function catcherPhpError($errorType, $errorTip, $errorFile, $errorLine)
    {
        $logLevel = Cml::getWarningLogLevel();
        if (in_array($errorType, $logLevel)) {
            return;//只记录warning以上级别日志
        }

        self::getLogger()->log(self::getLogger()->phpErrorToLevel[$errorType], $errorTip, ['file' => $errorFile, 'line' => $errorLine]);
        return;
    }
}
