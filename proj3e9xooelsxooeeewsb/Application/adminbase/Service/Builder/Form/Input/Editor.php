<?php
/* * *********************************************************
* 表单构建-editor类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;

class Editor extends Base
{
    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::Editor;

    /**
     * 获取上传图片/文件的服务器地址
     *
     * @return mixed
     */
    public function getUploadServerUrl()
    {
        return $this->options;
    }

    /**
     * 获取上传图片/文件的服务器地址
     *
     * @param string $url
     *
     * @return $this;
     */
    public function setUploadServerUrl($url)
    {
        $this->options = $url;
        return $this;
    }
}