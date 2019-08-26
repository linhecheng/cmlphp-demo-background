<?php
/* * *********************************************************
* 数据列表构建-按钮Column组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/3 14:26
* *********************************************************** */

namespace adminbase\Service\Builder\Grid\Column;

class Button extends Base
{
    /**
     * 固定类型为3
     *
     * @var int
     */
    protected $type = ColumnType::Button;

    /**
     * 保存的按钮数组
     *
     * @var array
     */
    protected $buttons = [];


    /**
     * 添加一个按钮
     *
     * @param \adminbase\Service\Builder\Grid\Button\Base|callable $button
     *
     * @return $this
     */
    public function addButton($button)
    {
        is_callable($button) && $button = $button();
        $this->buttons[] = $button;
        return $this;
    }

    /**
     * 批量添加按钮
     *
     * @param array $buttons
     *
     * @return $this
     */
    public function addButtons(array $buttons = [])
    {
        $this->buttons = array_merge($this->buttons, $buttons);
        return $this;
    }

    /**
     * 获取已经添加的按钮
     *
     * @return array
     */
    public function getButtons()
    {
        $return = [];
        foreach ($this->buttons as $button) {
            $return[] = $button->getFormatArray();
        }
        return $return;
    }

}
