<?php namespace Cml\Logger;

/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-21-22 下午1:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Log 文件驱动实现
 * *********************************************************** */
use Cml\Cml;

/**
 *  Log 文件驱动实现
 *
 * @package Cml\Logger
 */
class File extends Base
{
    /**
     * 日志存放的目录
     *
     * @var string
     */
    private $logDir = '';

    /**
     * 构造方法
     *
     */
    public function __construct()
    {
        $this->logDir = Cml::getApplicationDir('runtime_logs_path') . DIRECTORY_SEPARATOR . date('Y/m/d') . DIRECTORY_SEPARATOR;
        is_dir($this->logDir) || mkdir($this->logDir, 0755, true);
    }

    /**
     * 任意等级的日志记录
     *
     * @param mixed $level 日志等级
     * @param string $message 要记录到log的信息
     * @param array $context 上下文信息
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        return error_log($this->format($message, $context) . "\r\n", 3, $this->logDir . $level . '.log');
    }
}
