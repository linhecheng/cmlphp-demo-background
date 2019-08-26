<?php
/* * *********************************************************
* 表单元素 SELECT OPTION 组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/4 17:49
* *********************************************************** */

namespace adminbase\Service\Builder\Comm;

class Option
{
    /**
     * option的value属性
     *
     * @var string
     */
    private $value = '';


    /**
     * option的text
     *
     * @var string
     */
    private $text = '';

    /**
     * option 的 selected 、disabled等
     *
     * @var string
     */
    private $otherInfo = '';


    /**
     * 获取option的value属性
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 设置option的value属性
     *
     * @param string $value
     *
     * @return $this;
     */
    public function setValue($value = '')
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 获取option的text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * 设置option的text
     *
     * @param string $text
     *
     * @return $this;
     */
    public function setText($text = '')
    {
        $this->text = $text;
        return $this;
    }

    /**
     * 获取option 的 selected 、disabled等
     *
     * @return string
     */
    public function getOtherInfo()
    {
        return $this->otherInfo;
    }

    /**
     * 设置option 的 selected 、disabled等
     *
     * @param string $otherInfo
     *
     * @return $this;
     */
    public function setOtherInfo($otherInfo = '')
    {
        $this->otherInfo = $otherInfo;
        return $this;
    }

    /**
     * 将Option对象的属性转化为数组返回
     *
     * @return array
     */
    public function getFormatArray()
    {
        return [
            $this->getValue(),
            $this->getText(),
            $this->getOtherInfo()
        ];
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
