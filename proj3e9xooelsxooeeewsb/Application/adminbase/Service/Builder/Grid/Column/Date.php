<?php
/* * *********************************************************
* 数据列表构建-Date类型Column组件 自动转换unix_timestamp
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/3 14:26
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Column;

class Date extends Base
{
    /**
     * 固定类型为9
     *
     * @var int
     */
    protected $type = ColumnType::Date;
}
