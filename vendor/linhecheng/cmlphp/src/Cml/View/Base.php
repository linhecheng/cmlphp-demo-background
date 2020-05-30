<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图渲染引擎 抽象基类
 * *********************************************************** */

namespace Cml\View;

use Cml\Interfaces\View;

/**
 * 视图渲染引擎 抽象基类
 *
 * @package Cml\View
 */
abstract class Base implements View
{
    /**
     * 要传到模板的数据
     *
     * @var array
     */
    protected $args = [];

    /**
     * 响应头
     *
     * @var array
     */
    protected $header = [];

    /**
     * 获取响应头
     *
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * 设置要响应的头
     *
     * @param string $key
     * @param string $val
     *
     * @return $this
     */
    public function setHeader($key, $val)
    {
        $this->header[$key] = $val;
        return $this;
    }

    /**
     * 发送响应头
     *
     * @return $this
     */
    public function sendHeader()
    {
        foreach ($this->header as $key => $val) {
            header("{$key}: {$val}");
        }
        return $this;
    }

    /**
     * 变量赋值
     *
     * @param string | array $key 赋值到模板的key,数组或字符串为数组时批量赋值
     * @param mixed $val 赋值到模板的值
     *
     * @return $this
     */
    public function assign($key, $val = null)
    {
        if (is_array($key)) {
            $this->args = array_merge($this->args, $key);
        } else {
            $this->args[$key] = $val;
        }
        return $this;
    }

    /**
     * 引用赋值
     *
     * @param string | array $key 赋值到模板的key,数组或字符串为数组时批量赋值
     * @param mixed $val
     *
     * @return $this
     */
    public function assignByRef($key, &$val = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => &$v) {
                $this->args[$k] = $v;
            }
        } else {
            $this->args[$key] = $val;
        }
        return $this;
    }

    /**
     * 获取赋到模板的值
     *
     * @param string $key 要获取的值的key,数组或字符串为数组时批量赋值
     *
     * @return mixed
     */
    public function getValue($key = null)
    {
        if (is_null($key)) {//返回所有
            return $this->args;
        } elseif (isset($this->args[$key])) {
            return $this->args[$key];
        } else {
            return null;
        }
    }
}
