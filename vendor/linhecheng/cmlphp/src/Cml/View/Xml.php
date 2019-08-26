<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图 Xml渲染引擎
 * *********************************************************** */
namespace Cml\View;

use Cml\Config;

/**
 * 视图 Json渲染引擎
 *
 * @package Cml\View
 */
class Xml extends Base
{

    /**
     * 输出数据
     *
     */
    public function display()
    {
        header('Content-Type: application/xml;charset=' . Config::get('default_charset'));
        exit($this->array2xml($this->args));
    }

    /**
     * 数组转xml
     *
     * @param array $arr 要转换的数组
     * @param int $level 层级
     *
     * @return string
     */
    private function array2xml($arr, $level = 1)
    {
        $str = ($level == 1) ? "<?xml version=\"1.0\" encoding=\"" . Config::get('default_charset') . "\"?>\r\n<root>\r\n" : '';
        $space = str_repeat("\t", $level);
        foreach ($arr as $key => $val) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            if (!is_array($val)) {
                if (is_string($val) && preg_match('/[&<>"\'\?]+/', $val)) {
                    $str .= $space . "<$key><![CDATA[" . $val . ']]>' . "</$key>\r\n";
                } else {
                    $str .= $space . "<$key>" . $val . "</$key>\r\n";
                }
            } else {
                $str .= $space . "<$key>\r\n" . self::array2xml($val, $level + 1) . $space . "</$key>\r\n";
            }
        }
        if ($level == 1) {
            $str .= '</root>';
        }
        return $str;
    }
}
