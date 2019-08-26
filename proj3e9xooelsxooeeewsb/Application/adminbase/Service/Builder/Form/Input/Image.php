<?php
/* * *********************************************************
* 表单构建-image类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class Image extends File
{
    /**
     * 支持的上传文件类型
     *
     * @var string
     */
    protected $accent = 'images';

    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::Image;
}