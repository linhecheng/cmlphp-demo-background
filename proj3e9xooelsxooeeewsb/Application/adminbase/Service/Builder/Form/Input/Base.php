<?php
/* * *********************************************************
* 数据列表构建-Column组件基础类
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/3 14:26
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

class Base
{
    /**
     * 表单的type-固定类型
     *
     * @var int
     */
    protected $type;

    /**
     * label文字
     *
     * @var string
     */
    protected $label = '';

    /**
     * name属性
     *
     * @var string
     */
    protected $name = '';

    /**
     * placeholder
     *
     * @var string
     */
    protected $placeholder = '';

    /**
     * class 样式
     *
     * @var string
     */
    protected $class = '';

    /**
     * 其它信息如 直接输出到标签属性
     *
     * @var string
     */
    protected $otherInfo = '';


    /**
     *
     *      select时传option 如：[['1', '是', 'selected']] 生成的html <option value="1" selected >是</option>、
     *      editor时为图片上传的服务端地址
     *
     * @var mixed
     */
    protected $options = [];


    /**
     * 获取Column类型
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 获取label文字
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * 设置label文字
     *
     * @param string $label
     *
     * @return $this;
     */
    public function setLabel($label = '')
    {
        $this->label = $label;
        return $this;
    }

    /**
     * 获取name属性
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置name属性
     *
     * @param string $name
     *
     * @return $this;
     */
    public function setName($name = '')
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 获取placeholder属性
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * 设置name属性
     *
     * @param string $placeholder
     *
     * @return $this;
     */
    public function setPlaceholder($placeholder = '')
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 获取class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * 设置name属性
     *
     * @param string $class
     *
     * @return $this;
     */
    public function setClass($class = '')
    {
        $this->class = $class;
        return $this;
    }

    /**
     * 获取其它信息 直接输出到标签属性
     *
     * @return string
     */
    public function getOtherInfo()
    {
        return $this->otherInfo;
    }

    /**
     * 设置其它信息 直接输出到标签属性
     *
     * @param string $otherInfo 其它信息 直接输出到标签属性
     *
     * @return $this;
     */
    public function setOtherInfo($otherInfo = '')
    {
        $this->otherInfo .= ' ' . $otherInfo;
        return $this;
    }

    /**
     * 获取options信息
     *
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * 设置options信息
     *
     * @param mixed $options $type 为 checkbox和radio时为value 、
     *      select时传 Builder\Comm\Option实例
     *      editor时为图片上传的服务端地址
     *
     * @return $this;
     */
    public function setOptions($options = '')
    {
        $this->options = $options;
        return $this;
    }

    /**
     * 获取实例
     *
     *
     * @return $this;
     */
    public static function getInstance()
    {
        return new static();
    }
}
