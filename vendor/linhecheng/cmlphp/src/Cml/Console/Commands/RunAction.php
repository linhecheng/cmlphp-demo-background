<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-运行程序
 * *********************************************************** */

namespace Cml\Console\Commands;

use Cml\Console\Command;
use Cml\Route;

/**
 * 创建静态文件资源目录软链接
 *
 * @package Cml\Console\Commands
 */
class RunAction extends Command
{
    protected $description = "run action eg: web/Blog/Comment/add it will run \\web\\Controller\\Blog\\CommentController::add";

    protected $arguments = [
        'action' => 'action eg: web/Blog/Comment/add'
    ];

    protected $options = [
    ];

    /**
     * 命令的入口方法
     *
     * @param array $args 传递给命令的参数
     * @param array $options 传递给命令的选项
     *
     * @return string;
     */
    public function execute(array $args, array $options = [])
    {
        if (empty($args) || strpos($args[0], '/') < 1) {
            throw new \InvalidArgumentException('please input action');
        }
        Route::setPathInfo(explode('/', trim(trim($args[0], '/\\'))));
        return 'don_not_exit';
    }
}
