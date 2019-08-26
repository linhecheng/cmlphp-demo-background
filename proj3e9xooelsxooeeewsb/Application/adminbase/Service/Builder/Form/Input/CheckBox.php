<?php
/* * *********************************************************
* 表单构建-Checkbox类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class CheckBox extends Base
{
    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::Checkbox;

    /**
     * 获取checkbox的value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->options;
    }

    /**
     * 设置checkbox的value
     *
     * @param $value
     *
     * @return $this;
     */
    public function setValue($value)
    {
        $this->options = $value;
        return $this;
    }
}