<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 实体集合
 * *********************************************************** */

namespace Cml\Entity;

use Cml\Interfaces\ArrayAble;
use Cml\Interfaces\Jsonable;
use JsonSerializable;

/**
 * 实体集合
 * Class Collection
 * @package Cml\Entity
 */
class Collection extends \Cml\Tools\Collection
{
    /**
     * 将数据组删除
     *
     * @return bool
     */
    public function remove()
    {
        $this->each(function (Entity $model) {
            $model->remove();
        });

        return true;
    }

    /**
     * 更新数据集
     *
     * @param array $data 数据数组
     *
     * @return bool
     */
    public function update(array $data)
    {
        $this->each(function (Entity $model) use ($data) {
            $model->save($data);
        });

        return true;
    }

    /**
     * 当前集合转换成数组返回
     *
     * @param bool $transform 是否自动使用获取器转换
     *
     * @return array
     */
    public function toArray($transform = true)
    {
        return array_map(function ($value) use ($transform) {
            /**
             * @var Entity $value
             */
            return $value instanceof ArrayAble ? $value->toArray($transform) : $value;
        }, $this->items);
    }

    /**
     * 将元素转换为可序列化JSON
     *
     * @param bool $transform 是否自动使用获取器转换
     *
     * @return array
     */
    public function jsonSerialize($transform = true)
    {
        return array_map(function ($value) use ($transform) {
            /**
             * @var static $value
             */
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize($transform);
            }
            if ($value instanceof Jsonable) {
                return json_decode($value->toJson($transform), true);
            }
            if ($value instanceof ArrayAble) {
                return $value->toArray($transform);
            }
            return $value;
        }, $this->items);
    }

    /**
     * 获取元素转换成json的结果
     *
     * @param bool $transform 是否自动使用获取器转换
     * @param int $options
     *
     * @return false|string
     */
    public function toJson($transform = true, $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->jsonSerialize($transform), $options);
    }

    public function load($with)
    {
        if (!$this->items) {
            return;
        }
        $this->items[0]->collectAssociatedPreload($this->items, (array)$with, false);
    }
}
