<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 中间表实体
 * *********************************************************** */

namespace Cml\Entity;

/**
 * 多对多中间表实体类
 */
class Pivot extends Entity
{
    /**
     * 父表实体
     *
     * @var Entity
     */
    public $parent;

    /**
     * 架构函数
     *
     * @param array $items 数据
     * @param Entity $parent 父表实体
     * @param string $table 中间数据表名
     */
    public function __construct(array $items = [], Entity $parent = null, $table = '')
    {
        $this->parent = $parent;

        if (is_null($this->table)) {
            $this->table = $table;
        }

        parent::__construct($items);
    }
}
