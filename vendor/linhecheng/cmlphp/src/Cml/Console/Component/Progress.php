<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-121 下午19:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-进度条组件
 * *********************************************************** */

namespace Cml\Console\Component;

use Cml\Console\IO\Output;

/**
 * 命令行工具-进度条组件
 *
 * @package Cml\Console\Component
 */
class Progress
{
    /**
     * 完成的百分比
     *
     * @var int
     */
    protected $percent = 0;

    /**
     * 开始时间
     *
     * @var int
     */
    protected $startTime;

    /**
     * Progress constructor.
     */
    public function __construct()
    {
        $this->start();
    }

    /**
     * 获取百分比
     *
     * @return int
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * 开始任务
     *
     * @return $this
     */
    public function start()
    {
        $this->percent = 0;
        $this->startTime = time();
        return $this;
    }

    /**
     * 进入+ x
     *
     * @param int $value
     *
     * @return $this
     */
    public function increment($value = 1)
    {
        $this->percent += $value;

        $percentage = (double)($this->percent / 100);

        $progress = floor($percentage * 50);
        $output = "\r[" . str_repeat('>', $progress);
        if ($progress < 50) {
            $output .= ">" . str_repeat(' ', 50 - $progress);
        } else {
            $output .= '>';
        }
        $output .= sprintf('] %s%% ', round($percentage * 100, 0));

        $speed = (time() - $this->startTime) / $this->percent;
        $remaining = number_format(round($speed * (100 - $this->percent), 2), 2);
        $percentage == 100 || $output .= " $remaining seconds remaining";

        Output::write($output);
        return $this;
    }

    /**
     * 任务完成
     *
     * @return $this
     */
    public function success()
    {
        Output::writeln();
        return $this;
    }
}
