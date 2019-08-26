<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-输出格式化
 * *********************************************************** */

namespace Cml\Console\Format;

/**
 * 格式化输出处理类
 *
 * @package Cml\Console\Format
 */
class Format
{

    /**
     * 缩进多少个空格
     *
     * @var int
     */
    protected $indent = 0;

    /**
     * 前置符号
     *
     * @var string
     */
    protected $quote = '';

    /**
     * 前景色
     *
     * @var int
     */
    protected $foregroundColors;

    /**
     * 背景色
     *
     * @var int
     */
    protected $backgroundColors;

    /**
     * 构造方法
     *
     * @param array $options 配置参数
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * 设置参数
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        if (isset($options['indent'])) {
            $this->indent = $options['indent'];
        }
        if (isset($options['quote'])) {
            $this->quote = $options['quote'];
        }
        if (isset($options['foregroundColors'])) {
            $this->foregroundColors = $options['foregroundColors'];
        }
        if (isset($options['backgroundColors'])) {
            $this->backgroundColors = $options['backgroundColors'];
        }
        return $this;
    }

    /**
     * 格式化文本
     *
     * @param string $text
     *
     * @return string
     */
    public function format($text)
    {
        $lines = explode("\n", $text);
        foreach ($lines as &$line) {
            $line = ($this->quote) . str_repeat(' ', $this->indent) . $line;
        }
        return Colour::colour(implode("\n", $lines), $this->foregroundColors, $this->backgroundColors);
    }
}
