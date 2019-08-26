<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-输入解析类
 * *********************************************************** */

namespace Cml\Console\IO;

/**
 * 命令行工具-输入解析类
 *
 * @package Cml\Console\IO
 */
class Input
{
    /**
     * 解析参数
     *
     * @param array $argv
     *
     * @return array
     */
    public static function parse(array $argv)
    {
        $args = [];
        $options = [];

        for ($i = 0, $num = count($argv); $i < $num; $i++) {
            $arg = $argv[$i];
            if ($arg === '--') {//后缀所有内容都为参数
                $args[] = implode(' ', array_slice($argv, $i + 1));
                break;
            }
            if (substr($arg, 0, 2) === '--') {
                $key = substr($arg, 2);
                $value = true;
                if (($hadValue = strpos($arg, '=')) !== false) {
                    $key = substr($arg, 2, $hadValue - 2);
                    $value = substr($arg, $hadValue + 1);
                }
                if (array_key_exists($key, $options)) {
                    if (!is_array($options[$key])) {
                        $options[$key] = [$options[$key]];
                    }
                    $options[$key][] = $value;
                } else {
                    $options[$key] = $value;
                }
            } else if (substr($arg, 0, 1) === '-') {
                foreach (str_split(substr($arg, 1)) as $key) {
                    $options[$key] = true;
                }
            } else {
                $args[] = $arg;
            }
        }

        return [$args, $options];
    }
}
