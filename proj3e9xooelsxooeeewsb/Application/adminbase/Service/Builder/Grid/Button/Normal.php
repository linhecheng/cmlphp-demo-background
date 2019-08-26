<?php
/* * *********************************************************
* 数据列表构建-什么操作都没有的按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class Normal extends Base
{
    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::Normal;
}

