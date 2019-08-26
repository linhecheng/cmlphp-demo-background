<?php
/* * *********************************************************
* 数据列表构建-查看内容类型按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class View extends NewTab
{
    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::VIEW;

}
