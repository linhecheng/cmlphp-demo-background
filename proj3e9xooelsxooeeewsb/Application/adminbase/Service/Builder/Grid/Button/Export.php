<?php
/* * *********************************************************
* 数据列表构建-导出数据类型按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class Export extends Base
{
    /**
     * 按钮的文本内容
     *
     * @var string
     */
    protected $text = '导出';

    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::EXPORT;

    /**
     * 导出数据的地址
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 导出数据的地址 输出的时候会自动使用 url模板标签输出
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

}
