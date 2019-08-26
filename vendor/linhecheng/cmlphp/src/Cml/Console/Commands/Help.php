<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-帮助命令
 * *********************************************************** */

namespace Cml\Console\Commands;

use Cml\Cml;
use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\Format\Format;

/**
 * 默认的帮助命令
 *
 * @package Cml\Console\Commands
 */
class Help extends Command
{
    private $commandLength = 10;

    protected $description = "help command";

    protected $arguments = [
        'command' => 'input command to show command\'s help',
    ];


    /**
     * 执行命令入口
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->writeln("CmlPHP Console " . Cml::VERSION . "\n", ['foregroundColors' => [Colour::GREEN, Colour::HIGHLIGHT]]);
        $format = new Format(['indent' => 2]);

        if (empty($args)) {
            $this->writeln("Usage:");
            $this->writeln($format->format("input 'command [options] [args]' to run command or input 'help command ' to display command help info"));
            $this->writeln('');

            $options = $this->formatOptions();
            $cmdList = $this->formatCommand();

            $this->writeln("Options:");
            $this->formatEcho($format, $options);
            $this->writeln('');

            $this->writeln('Available commands:');
            $this->formatEcho($format, $cmdList[0]);
            $this->formatEcho($format, $cmdList[1]);
        } else {
            $class = new \ReflectionClass($this->console->getCommand($args[0]));
            $property = $class->getDefaultProperties();
            $description = isset($property['description']) ? $property['description'] : '';
            $help = isset($property['help']) ? $property['help'] : false;
            $arguments = isset($property['arguments']) ? $property['arguments'] : [];
            $options = isset($property['options']) ? $property['options'] : [];

            $this->writeln("Usage:");
            $this->writeln($format->format("{$args[0]} [options] [args]"));
            $this->writeln('');

            count($arguments) > 0 && $arguments = $this->formatArguments($arguments);
            $options = $this->formatOptions($options, 'this');

            $this->writeln("Options:");
            $this->formatEcho($format, $options);
            $this->writeln('');

            if (count($arguments)) {
                $this->writeln("Arguments");
                $this->formatEcho($format, $arguments);
                $this->writeln('');
            }

            $this->writeln("Help:");
            $this->writeln($format->format($help ? $help : $description));
        }
        $this->write("\n");
    }

    /**
     * 格式化命令
     *
     * @return array
     */
    private function formatCommand()
    {
        $cmdGroup = [];
        $noGroup = [];
        foreach ($this->console->getCommands() as $name => $class) {
            if ($class !== __CLASS__) {
                $class = new \ReflectionClass($class);
                $property = $class->getDefaultProperties();
                $property = isset($property['description']) ? $property['description'] : '';

                $hadGroup = strpos($name, ':');
                $group = substr($name, 0, $hadGroup);
                $name = Colour::colour($name, Colour::GREEN);
                $this->commandLength > strlen($name) || $this->commandLength = strlen($name) + 3;

                if ($hadGroup) {
                    $cmdGroup[$group][$name] = $property;
                } else {
                    $noGroup[$name] = $property;
                }
            }
        }
        return [$noGroup, $cmdGroup];
    }

    /**
     * 格式化选项
     *
     * @param array $options
     * @param string $command
     *
     * @return array
     */
    private function formatOptions($options = [], $command = '')
    {
        $dumpOptions = [
            '-h | --help' => "display {$command}command help info",
            '--no-ansi' => "disable ansi output"
        ];

        count($options) > 0 && $dumpOptions = array_merge($dumpOptions, $options);

        $optionsDump = [];
        foreach ($dumpOptions as $name => $desc) {
            $name = Colour::colour($name, Colour::GREEN);
            $this->commandLength > strlen($name) || $this->commandLength = strlen($name) + 3;
            $optionsDump[$name] = $desc;
        }
        return $optionsDump;
    }

    /**
     * 格式化参数
     *
     * @param array $arguments
     *
     * @return array
     */
    private function formatArguments(Array $arguments)
    {
        $echoArguments = [];
        $argsLength = 0;
        foreach ($arguments as $argument => $desc) {
            $argument = Colour::colour($argument, Colour::GREEN);
            $echoArguments[$argument] = $desc;
            $argsLength > strlen($argument) || $argsLength = strlen($argument);
        }
        return $echoArguments;
    }

    /**
     * 格式化输出
     *
     * @param Format $format
     * @param array $args
     */
    private function formatEcho(Format $format, $args)
    {
        foreach ($args as $group => $list) {
            if (is_array($list)) {
                $this->writeln($format->format($group));
                foreach ($list as $name => $desc) {
                    $this->writeln($format->format('  ' . $name . str_repeat(' ', $this->commandLength - 2 - strlen($name)) . $desc));
                }
            } else {
                $this->writeln($format->format($group . str_repeat(' ', $this->commandLength - strlen($group)) . $list));
            }
        }
    }
}
