<?php
/* * *********************************************************
* 数据列表构建-打开新tab标签类型按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class NewTab extends Base
{
    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::TAB;

    /**
     * 新TAB的url
     *
     * @var string
     */
    protected $url = '';


    /**
     * 新tab页的标题
     *
     * @var string
     */
    protected $title = '';

    /**
     * 新tab的url 输出的时候会自动使用 url模板标签输出
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 新tab的url 输出的时候会自动使用 url模板标签输出
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
     * 获取新tab的标题
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * 设置新tab的标题
     *
     * @param string $title
     *
     * @return $this;
     */
    public function setTitle($title = '')
    {
        $this->title = $title;

        return $this;
    }

}
