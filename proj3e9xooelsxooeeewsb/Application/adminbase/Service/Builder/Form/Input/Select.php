<?php
/* * *********************************************************
* 表单构建-select类型 Input组件
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2018/12/11 16:24
* *********************************************************** */

namespace adminbase\Service\Builder\Form\Input;

use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Comm\Option;

class Select extends Base
{
    /**
     * 固定类型
     *
     * @var int
     */
    protected $type = InputType::Select;

    /**
     * 获取options选项
     *
     * @return array|mixed
     */
    public function getOptions()
    {
        if ($this->options instanceof Option) {
            $this->options[] = $this->options;
        }

        if (is_array($this->options)) {
            $return = [];
            foreach ($this->options as $value) {
                if (!$value instanceof Option) {
                    throw new \InvalidArgumentException('select 类型的value值必须是 Builder\Comm\Option 对象的数组');
                }
                $return[] = $value->getFormatArray();
            }

            return $return;
        } else {
            return [];
        }
    }
}