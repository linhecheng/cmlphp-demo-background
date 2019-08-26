<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-11-03 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行驱动抽象接口
 * *********************************************************** */
namespace Cml\Interfaces;

/**
 *  命令行驱动抽象接口
 *
 * @package Cml\Interfaces
 */
interface Console
{
    /**
     * 构造函数
     *
     * @param array $commands
     */
    public function __construct(array $commands = []);

    /**
     * 批量添加命令
     *
     * @param array $commands 命令列表
     * @return $this
     */
    public function addCommands(array $commands);

    /**
     * 注册一个命令
     *
     * @param string $class 类名
     * @param null $alias 命令别名
     *
     * @return $this
     */
    public function addCommand($class, $alias = null);

    /**
     * 判断是否有无命令
     *
     * @param string $name 命令的别名
     *
     * @return bool
     */
    public function hasCommand($name);

    /**
     * 获取某个命令
     *
     * @param string $name 命令的别名
     *
     * @return mixed
     */
    public function getCommand($name);

    /**
     * 获取所有命令列表
     *
     * @return array
     */
    public function getCommands();

    /**
     * 运行命令
     *
     * @param array|null $argv
     *
     * @return mixed
     */
    public function run(array $argv = null);
}
