<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-121 下午19:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-输出内容格式化为长方形框组件
 * *********************************************************** */

namespace Cml\Console\Component;

/**
 * 命令行工具-输出内容格式化为长方形框组件
 *
 * @package Cml\Console\Component
 */
class Box
{
    /**
     * 要显示的文本
     *
     * @var string
     */
    protected $text;

    /**
     * 外围标识符
     *
     * @var string
     */
    protected $periphery;

    /**
     * 间隔
     *
     * @var int
     */
    protected $padding;

    /**
     * Box constructor.
     *
     * @param string $text 要处理的文本
     * @param string $periphery 外围字符
     * @param int $padding 内容与左右边框的距离
     */
    public function __construct($text = '', $periphery = '*', $padding = 2)
    {
        $this->text = $text;
        $this->periphery = $periphery;
        $this->padding = $padding;
    }

    /**
     * 渲染文本并返回
     *
     * @return string
     */
    public function render()
    {
        $lines = explode("\n", $this->text);
        $maxWidth = 0;
        foreach ($lines as $line) {
            if (strlen($line) > $maxWidth) {
                $maxWidth = strlen($line);
            }
        }

        $maxWidth += $this->padding * 2 + 2;
        $output = str_repeat($this->periphery, $maxWidth) . "\n";//first line
        foreach ($lines as $line) {
            $space = $maxWidth - (strlen($line) + 2 + $this->padding * 2);
            $output .= $this->periphery . str_repeat(' ', $this->padding) . $line . str_repeat(' ', $space + $this->padding) . $this->periphery . "\n";
        }
        $output .= str_repeat($this->periphery, $maxWidth);
        return $output;
    }


    /**
     * 渲染文本并返回
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
