<?php
/* * *********************************************************
* 表单构建-file类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class File extends Editor
{
    /**
     * 支持的上传文件类型
     *
     * @var string
     */
    protected $accent = 'file';

    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::File;

    /**
     * 获取上传类型
     *
     * @return mixed
     */
    public function getAccept()
    {
        return $this->accent;
    }

    /**
     * 设置上传类型
     *
     * @param string $accept file/images/video/audio
     *
     * @return $this;
     */
    public function setAccept($accept)
    {
        $this->accent = $accept;
        return $this;
    }
}