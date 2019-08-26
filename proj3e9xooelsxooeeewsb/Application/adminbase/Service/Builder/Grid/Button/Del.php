<?php
/* * *********************************************************
* 数据列表构建-删除类型按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class Del extends Base
{
    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::DEL;

    /**
     *删除/禁用数据请求的url
     *
     * @var string
     */
    protected $url = '';

    /**
     * 删除/禁用前的提示语
     *
     * @var string
     */
    protected $msgTip = '';

    /**
     * 删除/禁用数据请求的url 输出的时候会自动使用 url模板标签输出
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 删除/禁用数据请求的url 输出的时候会自动使用 url模板标签输出
     *
     * @param string $url
     *
     * @return $this;
     */
    public function setUrl($url = '')
    {
        $this->url = $url;

        return $this;
    }

    /**
     * 获取 删除/禁用按钮的确认提示语
     *
     * @return string
     */
    public function getMsgTip()
    {
        return $this->msgTip;
    }

    /**
     * 删除/禁用按钮的确认提示语
     *
     * @param string $tip
     *
     * @return $this;
     */
    public function setMsgTip($tip = '')
    {
        $this->msgTip = $tip;
        return $this;
    }
}