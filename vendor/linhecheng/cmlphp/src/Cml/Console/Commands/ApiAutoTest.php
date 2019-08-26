<?php
/* * *********************************************************
* 从代码注释提取接口信息自动运行测试
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/5/3 17:16
* *********************************************************** */

namespace Cml\Console\Commands;

use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;

/**
 * 从代码注释提取接口信息自动运行测试
 *
 * @package Cml\Console\Commands
 */
class ApiAutoTest extends Command
{
    protected $description = "run api test";

    protected $arguments = [
        'app' => 'cmlphp api app name eg: api',
        'true_code' => 'the true return code. eg:0, 0|1|2. More than one value with | separated'
    ];

    protected $options = [
    ];

    /**
     * 命令的入口方法
     *
     * @param array $args 传递给命令的参数
     * @param array $options 传递给命令的选项
     *
     * @return void;
     */
    public function execute(array $args, array $options = [])
    {
        $num = call_user_func_array(['Cml\Tools\Apidoc\AutoTest', 'run'], [
            isset($options['app']) ? $options['app'] : 'api',
            isset($options['true_code']) ? $options['true_code'] : 0,
        ]);
        Output::writeln(
            Colour::colour("api auto test success! api num:", Colour::GREEN) .
            Colour::colour($num, Colour::RED)
        );
    }
}
