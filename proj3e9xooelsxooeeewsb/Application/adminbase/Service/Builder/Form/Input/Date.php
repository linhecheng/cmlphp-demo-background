<?php
/* * *********************************************************
* 表单构建-date类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class Date extends DateTime
{
    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::Date;
}