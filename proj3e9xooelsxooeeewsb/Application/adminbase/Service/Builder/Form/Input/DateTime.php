<?php
/* * *********************************************************
* 表单构建-datetime类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class DateTime extends Base
{
    /**
     * 是否开启范围选择
     *
     * @var bool
     */
    private $range = false;

    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::DateTime;

    /**
     * 获取是否开启范围选择
     *
     * @return bool
     */
    public function getRange()
    {
        return $this->range ? 'true' : 'false';
    }

    /**
     * 设置是否开启范围选择
     *
     * @param bool $range
     *
     * @return $this;
     */
    public function setRange($range = false)
    {
        $this->range = $range;
        return $this;
    }
}