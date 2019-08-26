<?php
/* * *********************************************************
* 数据列表构建-Column组件基础类
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/3 14:26
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Column;

class Base
{
    /**
     * 固定类型
     *
     * @var int
     */
    protected $type;

    /**
     * 列的标题
     *
     * @var string
     */
    protected $text = '';

    /**
     * 对应数据列的字段
     *
     * @var string
     */
    protected $dataColumnFieldName = '';

    /**
     * 其它信息如 直接输出到标签属性
     *
     * @var string
     */
    protected $otherInfo = '';

    /**
     * 获取Column类型
     *
     * @return string
     */
    public function getColumnType()
    {
        return $this->type;
    }

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
     * 获取对应数据列的字段
     *
     * @return string
     */
    public function getDataColumnFieldName()
    {
        return $this->dataColumnFieldName;
    }

    /**
     * 设置对应数据列的字段--对于按钮的Column会将该字段的值通过Url传递到后端
     *
     * @param string $fieldName
     *
     * @return $this;
     */
    public function setDataColumnFieldName($fieldName = '')
    {
        $this->dataColumnFieldName = $fieldName;
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
        $this->otherInfo = $otherInfo;
        return $this;
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
