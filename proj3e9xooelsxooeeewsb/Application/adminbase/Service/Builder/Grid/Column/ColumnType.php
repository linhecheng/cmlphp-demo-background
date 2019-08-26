<?php
/* * *********************************************************
* Column的类型
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/3 15:56
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Column;

class ColumnType
{
    /**
     * 文本类型的Column列
     */
    const Text = 1;

    /**
     * 值为>0时打勾。<=0时打x
     */
    const Status = 2;

    /**
     * 按钮类型的Column列
     */
    const Button = 3;

    /**
     * Checkbox类型的Column列
     */
    const Checkbox = 5;

    /**
     * 显示为图片类型的Column列
     */
    const Image = 6;

    /**
     * 显示为超链接类型的Column列
     */
    const Link = 7;

    /**
     * 显示为html类型的Column列
     */
    const Html = 8;

    /**
     * 显示为日期类型的Column列-自动转换unix_timestamp
     */
    const Date = 9;

    /**
     * 显示为日期时间类型的Column列-自动转换unix_timestamp
     */
    const DateTime = 10;
}