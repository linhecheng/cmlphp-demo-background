<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-控制台颜色处理类
 * *********************************************************** */

namespace Cml\Console\Format;

/**
 * 命令行工具-控制台颜色处理类
 *
 * @see http://www.linuxselfhelp.com/howtos/Bash-Prompt/Bash-Prompt-HOWTO-6.html
 *
 * @package Cml\Console\Format
 */
class Colour
{
    const BLACK = 0;
    const RED = 1;
    const GREEN = 2;
    const YELLOW = 3;
    const BLUE = 4;
    const MAGENTA = 5;
    const CYAN = 6;
    const WHITE = 7;

    const HIGHLIGHT = 1;
    const UNDERSCORE = 4;
    const BLINK = 5;
    const REVERSE = 7;
    const CONCEAL = 8;

    /**
     * 不输出 ansi字符
     *
     * @var bool
     */
    private static $noAnsi = false;

    /**
     * 英文对应的颜色code
     *
     * @var array
     */
    private static $colors = [
        'black' => self::BLACK,
        'red' => self::RED,
        'green' => self::GREEN,
        'yellow' => self::YELLOW,
        'blue' => self::BLUE,
        'magenta' => self::MAGENTA,
        'cyan' => self::CYAN,
        'white' => self::WHITE
    ];

    /**
     * 格式化字符对应的code
     *
     * @var array
     */
    private static $options = [
        'highlight' => self::HIGHLIGHT,
        'underscore' => self::UNDERSCORE,
        'blink' => self::BLINK,
        'reverse' => self::REVERSE,
        'conceal' => self::CONCEAL
    ];

    /**
     * 不输出 ansi字符
     *
     */
    public static function setNoAnsi()
    {
        self::$noAnsi = true;
    }

    /**
     * 返回格式化后的字符串
     *
     * @param string $text 要着色的文本
     * @param string|array|int $foregroundColors 前景色  eg: red、red+highlight、Colors::BLACK、[Colors::BLACK, Colors::HIGHLIGHT]
     * @param string|int $backgroundColors 背景色
     *
     * @return string
     */
    public static function colour($text, $foregroundColors = null, $backgroundColors = null)
    {
        if (self::$noAnsi) {
            return $text;
        }

        $colour = function ($text) use ($foregroundColors, $backgroundColors) {
            $colors = [];
            if ($backgroundColors) {
                $bColor = self::charToCode($backgroundColors);
                $colors[] = "4{$bColor[0]}";
            }

            if ($foregroundColors) {
                list($fColor, $option) = self::charToCode($foregroundColors);
                $option && $colors[] = $option;
                $colors[] = "3{$fColor}";
            }

            $colors && $text = sprintf("\033[%sm", implode(';', $colors)) . $text . "\033[0m";//关闭所有属性;

            return $text;
        };

        $lines = explode("\n", $text);
        foreach ($lines as &$line) {
            $line = $colour($line);
        }
        return implode("\n", $lines);
    }


    /**
     * 返回颜色对应的数字编码
     *
     * @param int|string|array $color
     *
     * @return array
     */
    private static function charToCode($color)
    {
        if (is_array($color)) {
            return $color;
        } else if (is_string($color)) {
            list($color, $option) = explode('+', strtolower($color));
            if (!isset(self::$colors[$color])) {
                throw new \InvalidArgumentException("Unknown color '$color'");
            }

            if ($option && !isset(self::$options[$option])) {
                throw new \InvalidArgumentException("Unknown option '$option'");
            }

            $code = self::$colors[$color];
            $option = $option ? self::$options[$option] : 0;
            return [$code, $option];
        } else {
            return [$color, 0];
        }
    }

    /**
     * 静态方法
     *
     * @param string $method
     * @param array $args
     *
     * @return string
     */
    public static function __callStatic($method, $args)
    {
        return self::colour($args[0], $method);
    }
}
