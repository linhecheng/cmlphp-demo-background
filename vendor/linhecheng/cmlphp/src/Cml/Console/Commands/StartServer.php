<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-启动PHP内置web服务器
 * *********************************************************** */

namespace Cml\Console\Commands;

use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;

/**
 * 在命令行下执行控制器方法
 *
 * @package Cml\Console\Commands
 */
class StartServer extends Command
{
    protected $description = "php server for cmlphp";

    protected $arguments = [
    ];

    protected $options = [
        '--host=0.0.0.0' => 'The host to server the on',
        '--port=8008' => 'The port to server the on',
        '--root=dir' => 'The dir to server the on',
        '--boot=index.php' => 'The app start script'
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
        $host = isset($options['host']) ? $options['host'] : '0.0.0.0';
        $port = isset($options['port']) ? $options['port'] : '8008';
        $root = isset($options['root']) ? $options['root'] : CML_PROJECT_PATH . DIRECTORY_SEPARATOR . 'public';
        $boot = isset($options['boot']) ? $options['boot'] : '';
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            $host,
            $port,
            $root,
            $boot ? $root . DIRECTORY_SEPARATOR . $boot : ''
        );

        Output::writeln(sprintf('cmlphp dev server is started on ' . Colour::colour('http://%s:%s/', Colour::RED), '0.0.0.0' == $host ? '127.0.0.1' : $host, $port));
        Output::writeln(sprintf('You can exit with ' . Colour::colour('CTRL-C', Colour::RED)));
        Output::writeln(sprintf('document root is: %s', $root));
        passthru($command);
    }
}
