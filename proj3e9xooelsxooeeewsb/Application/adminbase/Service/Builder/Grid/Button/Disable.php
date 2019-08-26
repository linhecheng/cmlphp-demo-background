<?php
/* * *********************************************************
* 数据列表构建-禁用类型按钮
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/8/6 15:09
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Button;

use adminbase\Service\Builder\Comm\ButtonType;

class Disable extends Del
{
    /**
     * 按钮的类型
     *
     * @var string
     */
    protected $type = ButtonType::DISABLE;

    /**
     * 要切换状态的字段
     *
     * @var string
     */
    protected $field = 'status';

    /**
     * 获取 禁用按钮-要切换状态的字段
     *
     * @return string
     */
    public function getStatusField()
    {
        return $this->field;
    }

    /**
     * 设置 禁用按钮-要切换状态的字段
     *
     * @param string $statusField
     *
     * @return $this;
     */
    public function setStatusField($statusField = 'status')
    {
        $this->field = $statusField;
        return $this;
    }
}