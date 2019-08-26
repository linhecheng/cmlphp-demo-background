<?php
/* * *********************************************************
* 表单构建-password类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class Password extends Base
{
    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::Password;
}