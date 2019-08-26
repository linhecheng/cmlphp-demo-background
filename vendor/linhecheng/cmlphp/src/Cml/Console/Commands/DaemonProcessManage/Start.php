<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-启动守护进程
 * *********************************************************** */

namespace Cml\Console\Commands\DaemonProcessManage;

use Cml\Console\Command;
use Cml\Tools\Daemon\ProcessManage;

/**
 * 启动守护进程
 *
 * @package Cml\Console\Commands\DaemonProcessManage
 */
class Start extends Command
{
    protected $description = "start daemon process";

    protected $arguments = [
    ];

    protected $options = [
    ];

    /**
     * 启动守护进程
     *
     * @param array $args 传递给命令的参数
     * @param array $options 传递给命令的选项
     */
    public function execute(array $args, array $options = [])
    {
        ProcessManage::start();
    }
}
