<?php
/* * *********************************************************
* 数据列表构建-按钮基础抽象类
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;


class Base
{
    /**
     * 按钮的文本内容
     *
     * @var string
     */
    protected $text = '';

    /**
     * 附加到按钮class属性的额外样式
     *
     * @var string
     */
    protected $class = '';

    /**
     * 其它信息。直接输出如disable。
     *
     * @var string
     */
    protected $otherInfo = '';

    /**
     * add/edit时弹出层的宽高
     *
     * @var bool
     */
    protected $layerWidth = false;

    /**
     * 获取按钮的文本内容
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * 设置按钮的文本内容
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
     * 获取 附加到按钮class属性的额外样式
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * 设置 附加到按钮class属性的额外样式
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
     * 获取 其它信息。直接输出如disable。
     *
     * @return string
     */
    public function getOtherInfo()
    {
        return $this->otherInfo;
    }

    /**
     * 设置  其它信息。直接输出如disable。
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
     * 获取 add/edit时弹出层的宽高
     *
     * @return string
     */
    public function getLayerWidth()
    {
        return $this->layerWidth;
    }

    /**
     * 设置  add/edit时弹出层的宽高-这边设置的是全局的
     *
     * @param string $layerWidth
     *
     * @return $this;
     */
    public function setLayerWidth($layerWidth = '')
    {
        $this->layerWidth = $layerWidth;
        return $this;
    }

    /**
     * 将Button对象的属性转化为数组返回
     *
     * @return array
     */
    public function getFormatArray()
    {
        return [
            'text' => $this->getText(),
            'type' => $this->type,
            'url' => $this->newPageUrl,
            'title' => $this->title,
            'saveUrl' => $this->saveDataUrl,
            'class' => $this->getClass(),
            'other' => $this->getOtherInfo(),
            'status' => $this->saveDataUrl,
            'width' => $this->newPageWidth
        ];
    }

    public function __get($name)
    {
        switch ($name) {
            case 'newPageUrl':
                return method_exists($this, 'getUrl') ? $this->getUrl() : '';
            case 'saveDataUrl':
                return method_exists($this, 'getStatusField') ? $this->getStatusField() : '';
            case 'title':
                return method_exists($this, 'getMsgTip') ? $this->getMsgTip() : '';
        }

    }

    /**
     * 获取实例
     *
     *
     * @return Base
     */
    public static function getInstance()
    {
        return new static();
    }
}