<?php
/* * *********************************************************
* 数据列表构建-Checkbox Column组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/3 14:26
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Column;

class Checkbox extends Button
{
    /**
     * 固定类型为5
     *
     * @var int
     */
    protected $type = ColumnType::Checkbox;
}
