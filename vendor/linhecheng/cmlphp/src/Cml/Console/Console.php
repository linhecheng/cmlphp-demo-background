<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具
 * *********************************************************** */

namespace Cml\Console;

use Cml\Console\Commands\Help;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Input;
use Cml\Console\IO\Output;

/**
 * 注册可用的命令并执行
 */
class Console implements \Cml\Interfaces\Console
{
    /**
     * 存放所有命令
     *
     * @var array
     */
    protected $commands = [
        'run-action' => 'Cml\Console\Commands\RunAction',
        //make
        'make:symlink' => 'Cml\Console\Commands\CreateSymbolicLink',
        'make:controller' => 'Cml\Console\Commands\Make\Controller',
        'make:model' => 'Cml\Console\Commands\Make\Model',
        //worker
        'worker:start' => 'Cml\Console\Commands\DaemonProcessManage\Start',
        'worker:status' => 'Cml\Console\Commands\DaemonProcessManage\Status',
        'worker:reload' => 'Cml\Console\Commands\DaemonProcessManage\Reload',
        'worker:stop' => 'Cml\Console\Commands\DaemonProcessManage\Stop',
        'worker:add-task' => 'Cml\Console\Commands\DaemonProcessManage\AddTask',
        'worker:rm-task' => 'Cml\Console\Commands\DaemonProcessManage\RmTask',
        //migrate
        'migrate:create' => 'Cml\Console\Commands\Migrate\Create',
        'migrate:run' => 'Cml\Console\Commands\Migrate\Migrate',
        'migrate:rollback' => 'Cml\Console\Commands\Migrate\Rollback',
        'migrate:status' => 'Cml\Console\Commands\Migrate\Status',
        'migrate:breakpoint' => 'Cml\Console\Commands\Migrate\Breakpoint',
        //seed
        'seed:create' => 'Cml\Console\Commands\Migrate\SeedCreate',
        'seed:run' => 'Cml\Console\Commands\Migrate\SeedRun',
        //api auto test
        'api-test' => 'Cml\Console\Commands\ApiAutoTest'
    ];

    /**
     * Console constructor.
     *
     * @param array $commands
     */
    public function __construct(array $commands = [])
    {
        $this->addCommand('Cml\Console\Commands\Help', 'help');
        $this->addCommands($commands);
    }

    /**
     * 将xx-xx转换为小驼峰返回
     *
     * @param string $string
     *
     * @return string
     */
    public static function dashToCamelCase($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
    }

    /**
     * 将小驼峰转换为xx-xx返回
     *
     * @param string $string
     * @return string
     */
    public static function camelCaseToDash($string)
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '-$1', $string));
    }

    /**
     * 批量添加命令
     *
     * @param array $commands 命令列表
     * @return $this
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $name => $command) {
            $this->addCommand($command, is_numeric($name) ? null : $name);
        }
        return $this;
    }

    /**
     * 注册一个命令
     *
     * @param string $class 类名
     * @param null $alias 命令别名
     *
     * @return $this
     */
    public function addCommand($class, $alias = null)
    {
        $name = $class;
        $name = substr($name, 0, -7);
        $name = self::dashToCamelCase(basename(str_replace('\\', '/', $name)));

        $name = $alias ?: $name;
        $this->commands[$name] = $class;

        return $this;
    }

    /**
     * 判断是否有无命令
     *
     * @param string $name 命令的别名
     *
     * @return bool
     */
    public function hasCommand($name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * 获取某个命令
     *
     * @param string $name 命令的别名
     *
     * @return mixed
     */
    public function getCommand($name)
    {
        if (!isset($this->commands[$name])) {
            throw new \InvalidArgumentException("Command '$name' does not exist");
        }
        return $this->commands[$name];
    }

    /**
     * 获取所有命令列表
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * 运行命令
     *
     * @param array|null $argv
     *
     * @return mixed
     */
    public function run(array $argv = null)
    {
        try {
            if ($argv === null) {
                $argv = isset($_SERVER['argv']) ? array_slice($_SERVER['argv'], 1) : [];
            }

            list($args, $options) = Input::parse($argv);

            $command = count($args) ? array_shift($args) : 'help';

            if (!isset($this->commands[$command])) {
                throw new \InvalidArgumentException("Command '$command' does not exist");
            }

            isset($options['no-ansi']) && Colour::setNoAnsi();
            if (isset($options['h']) || isset($options['help'])) {
                $help = new Help($this);
                $help->execute([$command]);
                exit(0);
            }

            $command = explode('::', $this->commands[$command]);

            return call_user_func_array([new $command[0]($this), isset($command[1]) ? $command[1] : 'execute'], [$args, $options]);
        } catch (\Exception $e) {
            Output::writeException($e);
            exit(1);
        }
    }
}
