<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图驱动抽象接口
 * *********************************************************** */
namespace Cml\Interfaces;

/**
 * 视图驱动抽象接口
 *
 * @package Cml\Interfaces
 */
interface View
{
    /**
     * 变量赋值
     *
     * @param string | array $key 赋值到模板的key,数组或字符串为数组时批量赋值
     * @param mixed $val 赋值到模板的值
     *
     * @return $this
     */
    public function assign($key, $val = null);

    /**
     * 引用赋值
     *
     * @param string | array $key 赋值到模板的key,数组或字符串为数组时批量赋值
     * @param mixed $val
     *
     * @return $this
     */
    public function assignByRef($key, &$val = null);

    /**
     * 获取赋到模板的值
     *
     * @param string $key 要获取的值的key,数组或字符串为数组时批量赋值
     *
     * @return mixed
     */
    public function getValue($key = null);

    /**
     * 抽象display
     *
     * @return mixed
     */
    public function display();
}
