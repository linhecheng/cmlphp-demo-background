<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 ArrayAccess, ArrayAble, JsonSerializable, Jsonable实现
 * *********************************************************** */

namespace Cml\Tools;

use JsonSerializable;
use ArrayIterator;
use Cml\Interfaces\ArrayAble;
use Cml\Interfaces\Jsonable;

trait ArrayJsonSpl
{
    /**
     * 元素
     *
     * @var array
     */
    protected $items = [];

    /**
     * 将元素转换为可序列化JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            if ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            }
            if ($value instanceof ArrayAble) {
                return $value->toArray();
            }
            return $value;
        }, $this->items);
    }

    /**
     * 判断集合是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * 获取元素转换成json的结果
     *
     * @param int $options
     *
     * @return false|string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * 判断元素是否存在
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * 获取一个元素
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * 设置一个元素
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset一个元素
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * 当前集合转换成数组返回
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof ArrayAble ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * 获取元素的迭代器
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function __toString()
    {
        return $this->toJson();
    }
}