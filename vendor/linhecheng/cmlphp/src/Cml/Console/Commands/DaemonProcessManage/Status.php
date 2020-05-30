<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-查看守护进程运行状态
 * *********************************************************** */

namespace Cml\Console\Commands\DaemonProcessManage;

use Cml\Console\Command;
use Cml\Tools\Daemon\ProcessManage;
use InvalidArgumentException;

/**
 * 查看守护进程运行状态
 *
 * @package Cml\Console\Commands\DaemonProcessManage
 */
class Status extends Command
{
    protected $description = "show worker status";

    protected $arguments = [
    ];

    protected $options = [
    ];

    /**
     * 查看守护进程运行状态
     *
     * @param array $args 传递给命令的参数
     * @param array $options 传递给命令的选项
     *
     * @throws InvalidArgumentException
     */
    public function execute(array $args, array $options = [])
    {
        ProcessManage::getStatus(true);
    }
}
