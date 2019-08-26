<?php
/* * *********************************************************
* 数据列表构建-顶部搜索组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/4 15:45
* *********************************************************** */

namespace adminbase\Service\Builder\Grid;

use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Comm\Option;

class SearchItem
{
    /**
     * 表单元素的name属性
     *
     * @var string
     */
    private $name = '';

    /**
     * 表单元素的placeholder属性
     *
     * @var string
     */
    private $placeholder = '';

    /**
     * 表单元素的类型
     *
     * @var string
     */
    private $type = InputType::Text;

    /**
     * 表单元素的value 为数组的时候是select的option选项 如：[['1’, '是', 'selected']] 生成的html <option value="1" selected >是</option>
     *
     * @var mixed
     */
    private $value = '';

    /**
     * 附加到按钮class属性的额外样式
     *
     * @var string
     */
    private $class = '';

    /**
     * 其它信息。直接输出如disable。
     *
     * @var string
     */
    private $otherInfo = '';

    /**
     * 获取表单元素的name属性
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置表单元素的name属性
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
     * 获取表单元素的placeholder属性
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * 设置表单元素的placeholder属性
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
     * 获取表单元素的类型
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 设置表单元素的类型
     *
     * @param string $type
     *
     * @return $this;
     */
    public function setType($type = InputType::Text)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 获取表单元素的value
     *
     * @return mixed
     */
    public function getValue()
    {
        if ($this->value instanceof Option) {
            $this->value[] = $this->value;
        }

        if (is_array($this->value)) {
            $return = [];
            foreach ($this->value as $value) {
                if (!$value instanceof Option) {
                    throw new \InvalidArgumentException('select 类型的value值必须是 Builder\Comm\Option 对象的数组');
                }
                $return[] = $value->getFormatArray();
            }

            return $return;
        } else {
            return $this->value;
        }
    }

    /**
     * 设置表单元素的value
     *
     * @param mixed $value 表单元素的value。select时传入 Builder\Comm\Option 对象的数组
     *
     * @return $this;
     */
    public function setValue($value = '')
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 获取 附加到表单元素class属性的额外样式
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * 设置 附加到表单元素class属性的额外样式
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
