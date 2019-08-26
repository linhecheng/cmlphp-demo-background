<?php
/* * *********************************************************
* 数据列表构建-添加类型按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class Add extends Base
{
    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::ADD;


    /**
     * 打开的页面中数据保存的地址 输出的时候会自动使用 url模板标签输出
     *
     * @var string
     */
    protected $saveDataUrl = '';

    /**
     * 打开的页面的url 输出的时候会自动使用 url模板标签输出
     *
     * @var string
     */
    protected $newPageUrl = '';

    /**
     * 打开的页面的title type=del时这个参数传id
     *
     * @var string
     */
    protected $title = '';

    /**
     * 打开的页面的弹出层的宽高--参考layer文档设置
     *
     * @var string
     */
    protected $newPageWidth = '';

    /**
     * 获取打开的页面的url 输出的时候会自动使用 url模板标签输出
     *
     * @return string
     */
    public function getNewPageUrl()
    {
        return $this->newPageUrl;
    }

    /**
     * 设置打开的页面的url 输出的时候会自动使用 url模板标签输出
     *
     * @param string $newPageUrl
     *
     * @return $this;
     */
    public function setNewPageUrl($newPageUrl = '')
    {
        $this->newPageUrl = $newPageUrl;

        return $this;
    }

    /**
     * 打开的页面的title
     *
     * @return string
     */
    public function getNewPageTitle()
    {
        return $this->title;
    }

    /**
     * 设置 打开的页面的title
     *
     * @param string $newPageTitle
     *
     * @return $this;
     */
    public function setNewPageTitle($newPageTitle = '')
    {
        $this->title = $newPageTitle;
        return $this;
    }

    /**
     * 获取 打开的页面中数据保存的地址 输出的时候会自动使用 url模板标签输出
     *
     * @return string
     */
    public function getSaveDataUrl()
    {
        return $this->saveDataUrl;
    }

    /**
     * 设置 打开的页面中数据保存的地址 输出的时候会自动使用 url模板标签输出 type=del时这个参数为msg。
     *
     * @param string $saveDataUrl
     *
     * @return $this;
     */
    public function setSaveDataUrl($saveDataUrl = '')
    {
        $this->saveDataUrl = $saveDataUrl;
        return $this;
    }

    /**
     * add/edit时。获取弹出层的宽高-针对当时按钮
     *
     * @return string
     */
    public function getNewPageWidth()
    {
        return $this->newPageWidth;
    }

    /**
     * add/edit时。设置弹出层的宽高-针对当时按钮
     *
     * @param string $width 参考layer官方文档,如： "['100%', '100%']"
     *
     * @return $this
     */
    public function setNewPageWidth($width = '')
    {
        $this->newPageWidth = $width;
        return $this;
    }
}
