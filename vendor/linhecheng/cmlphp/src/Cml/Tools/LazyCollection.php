<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 LazyCollection 实现
 * *********************************************************** */

namespace Cml\Tools;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Cml\Interfaces\ArrayAble;
use Cml\Interfaces\Jsonable;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use stdClass;
use Traversable;

/**
 * Class LazyCollection
 *
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 *
 * @package Cml\Tools
 */
class LazyCollection implements ArrayAccess, ArrayAble, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    use EnumeratesValues, ArrayJsonSpl;

    /**
     * 生成项的源.
     *
     * @var callable|static
     */
    public $source;

    /**
     * 创新一个LazyCollection
     *
     * @param mixed $source
     *
     * @return void
     */
    public function __construct($source = null)
    {
        if ($source instanceof Closure || $source instanceof self) {
            $this->source = $source;
        } elseif (is_null($source)) {
            $this->source = static::empty();
        } else {
            $this->source = $this->getArrayAbleItems($source);
        }
    }

    /**
     * 创建一个空实例
     *
     * @return static
     */
    public static function empty()
    {
        return new static([]);
    }

    /**
     * 通过调用给定次数的回调来创建新实例。
     *
     * @param int $number
     * @param callable $callback
     *
     * @return static
     */
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new static;
        }

        $instance = new static(function () use ($number) {
            for ($current = 1; $current <= $number; $current++) {
                yield $current;
            }
        });

        return is_null($callback) ? $instance : $instance->map($callback);
    }

    /**
     * 创建具有给定范围的可枚举项。
     *
     * @param int $from
     * @param int $to
     *
     * @return static
     */
    public static function range($from, $to)
    {
        return new static(function () use ($from, $to) {
            for (; $from <= $to; $from++) {
                yield $from;
            }
        });
    }

    /**
     * 获取可枚举项中的所有项。
     *
     * @return array
     */
    public function all()
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    /**
     * 急切地将所有项加载到由数组支持的新惰性集合中。
     *
     * @return static
     */
    public function eager()
    {
        return new static($this->all());
    }

    /**
     * 枚举时缓存值。
     *
     * @return static
     */
    public function remember()
    {
        $iterator = $this->getIterator();

        $iteratorIndex = 0;

        $cache = [];

        return new static(function () use ($iterator, &$iteratorIndex, &$cache) {
            for ($index = 0; true; $index++) {
                if (array_key_exists($index, $cache)) {
                    yield $cache[$index][0] => $cache[$index][1];

                    continue;
                }

                if ($iteratorIndex < $index) {
                    $iterator->next();

                    $iteratorIndex++;
                }

                if (!$iterator->valid()) {
                    break;
                }

                $cache[$index] = [$iterator->key(), $iterator->current()];

                yield $cache[$index][0] => $cache[$index][1];
            }
        });
    }

    /**
     * 获取给定key的平均值。
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function avg($callback = null)
    {
        return $this->collect()->avg($callback);
    }

    /**
     * 获取给定key的中值
     *
     * @param string|array|null $key
     *
     * @return mixed
     */
    public function median($key = null)
    {
        return $this->collect()->median($key);
    }

    /**
     * G获取给定key的众数
     *
     * @param string|array|null $key
     *
     * @return array|null
     */
    public function mode($key = null)
    {
        return $this->collect()->mode($key);
    }

    /**
     * 将项目集合折叠到单个数组中。
     *
     * @return static
     */
    public function collapse()
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof EnumeratesValues) {
                    foreach ($values as $value) {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * 确定可枚举项中是否存在项
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1 && $this->useAsCallable($key)) {
            $placeholder = new stdClass;

            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            $needle = $key;

            foreach ($this as $value) {
                if ($value == $needle) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * 交叉连接给定的iterable，返回所有可能的置换。
     *
     * @param array ...$arrays
     *
     * @return static
     */
    public function crossJoin(...$arrays)
    {
        return $this->passthru('crossJoin', func_get_args());
    }

    /**
     * 获取给定项中不存在的项
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diff($items)
    {
        return $this->passthru('diff', func_get_args());
    }

    /**
     * 使用回调设置给定项中不存在的项。
     *
     * @param mixed $items
     * @param callable $callback
     *
     * @return static
     */
    public function diffUsing($items, callable $callback)
    {
        return $this->passthru('diffUsing', func_get_args());
    }

    /**
     * 获取其键和值不在给定项中的项。
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffAssoc($items)
    {
        return $this->passthru('diffAssoc', func_get_args());
    }

    /**
     * 获取集合中键和值不在给定项中的项
     *
     * @param mixed $items
     * @param callable $callback
     *
     * @return static
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return $this->passthru('diffAssocUsing', func_get_args());
    }

    /**
     * 获取集合中键不在给定项中的项
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffKeys($items)
    {
        return $this->passthru('diffKeys', func_get_args());
    }

    /**
     * 使用回调函数获取其键不在给定项中的项
     *
     * @param mixed $items
     * @param callable $callback
     *
     * @return static
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return $this->passthru('diffKeysUsing', func_get_args());
    }

    /**
     * 检索重复项
     *
     * @param callable|null $callback
     * @param bool $strict
     *
     * @return static
     */
    public function duplicates($callback = null, $strict = false)
    {
        return $this->passthru('duplicates', func_get_args());
    }

    /**
     * 使用严格比较检索重复项
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function duplicatesStrict($callback = null)
    {
        return $this->passthru('duplicatesStrict', func_get_args());
    }

    /**
     * 获取除具有指定键的项以外的所有项
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function except($keys)
    {
        return $this->passthru('except', func_get_args());
    }

    /**
     * 对每个项目运行筛选器
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if (is_null($callback)) {
            $callback = function ($value, $key) {
                return (bool)$value;
            };
        }
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * 从通过给定真值测试的可枚举项中获取第一项
     *
     * @param callable|null $callback
     * @param mixed $default
     *
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        $iterator = $this->getIterator();

        if (is_null($callback)) {
            if (!$iterator->valid()) {
                return self::value($default);
            }

            return $iterator->current();
        }

        foreach ($iterator as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return self::value($default);
    }

    private static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * 获取集合中项目的展开列表
     *
     * @param int $depth
     *
     * @return static
     */
    public function flatten($depth = INF)
    {
        $instance = new static(function () use ($depth) {
            foreach ($this as $item) {
                if (!is_array($item) && !$item instanceof EnumeratesValues) {
                    yield $item;
                } elseif ($depth === 1) {
                    yield from $item;
                } else {
                    yield from (new static($item))->flatten($depth - 1);
                }
            }
        });

        return $instance->values();
    }

    /**
     * 翻转集合中的项目
     *
     * @return static
     */
    public function flip()
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $value => $key;
            }
        });
    }

    /**
     * 获取key获取一个项目
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return null;
        }

        foreach ($this as $outerKey => $outerValue) {
            if ($outerKey == $key) {
                return $outerValue;
            }
        }

        return self::value($default);
    }

    /**
     * 通过字段或使用回调对关联数组进行分组
     *
     * @param array|callable|string $groupBy
     * @param bool $preserveKeys
     *
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        return $this->passthru('groupBy', func_get_args());
    }

    /**
     * 通过字段或使用回调为关联数组设置键
     *
     * @param callable|string $keyBy
     *
     * @return static
     */
    public function keyBy($keyBy)
    {
        return new static(function () use ($keyBy) {
            $keyBy = $this->valueRetriever($keyBy);

            foreach ($this as $key => $item) {
                $resolvedKey = $keyBy($item, $key);

                if (is_object($resolvedKey)) {
                    $resolvedKey = (string)$resolvedKey;
                }

                yield $resolvedKey => $item;
            }
        });
    }

    /**
     * 通过键确定集合中是否存在项
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());
        $count = count($keys);

        foreach ($this as $key => $value) {
            if (array_key_exists($key, $keys) && --$count == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 将给定键的值连接为字符串
     *
     * @param string $value
     * @param string $glue
     *
     * @return string
     */
    public function implode($value, $glue = null)
    {
        return $this->collect()->implode(...func_get_args());
    }

    /**
     * 使集合与给定项相交
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersect($items)
    {
        return $this->passthru('intersect', func_get_args());
    }

    /**
     * 按键将集合与给定项相交
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersectByKeys($items)
    {
        return $this->passthru('intersectByKeys', func_get_args());
    }

    /**
     * 确定项目是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->getIterator()->valid();
    }

    /**
     * 将集合中的值用字符串连接
     *
     * @param string $glue
     * @param string $finalGlue
     *
     *
     * @return string
     */
    public function join($glue, $finalGlue = '')
    {
        return $this->collect()->join(...func_get_args());
    }

    /**
     * 获取集合项的键
     *
     * @return static
     */
    public function keys()
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * 从集合中获取最后一个项目
     *
     * @param callable|null $callback
     * @param mixed $default
     *
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        $needle = $placeholder = new stdClass;

        foreach ($this as $key => $value) {
            if (is_null($callback) || $callback($value, $key)) {
                $needle = $value;
            }
        }

        return $needle === $placeholder ? self::value($default) : $needle;
    }

    /**
     * 获取给定key的值
     *
     * @param string|array $value
     * @param string|null $key
     *
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(function () use ($value, $key) {
            list($value, $key) = $this->explodePluckParameters($value, $key);

            foreach ($this as $item) {
                $itemValue = Arr::dataGet($item, $value);

                if (is_null($key)) {
                    yield $itemValue;
                } else {
                    $itemKey = Arr::dataGet($item, $key);

                    if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                        $itemKey = (string)$itemKey;
                    }

                    yield $itemKey => $itemValue;
                }
            }
        });
    }

    /**
     * 用回调函数处理数组中的元素
     *
     * @param callable $callback
     *
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * 在项目上运行字典 map
     * 回调应返回具有单个键/值对的关联数组。
     *
     * @param callable $callback
     *
     * @return static
     */
    public function mapToDictionary(callable $callback)
    {
        return $this->passthru('mapToDictionary', func_get_args());
    }

    /**
     * 对每个项运行关联映射。
     * 回调应返回具有单个键/值对的关联数组。
     *
     * @param callable $callback
     *
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield from $callback($value, $key);
            }
        });
    }

    /**
     * 将集合与给定项合并。
     *
     * @param mixed $items
     *
     * @return static
     */
    public function merge($items)
    {
        return $this->passthru('merge', func_get_args());
    }

    /**
     * 递归地将集合与给定项合并
     *
     * @param mixed $items
     *
     * @return static
     */
    public function mergeRecursive($items)
    {
        return $this->passthru('mergeRecursive', func_get_args());
    }

    /**
     * 通过对键使用此集合，对其值使用另一个集合来创建集合。
     *
     * @param mixed $values
     *
     * @return static
     */
    public function combine($values)
    {
        return new static(function () use ($values) {
            $values = $this->makeIterator($values);

            $errorMessage = 'Both parameters should have an equal number of elements';

            foreach ($this as $key) {
                if (!$values->valid()) {
                    trigger_error($errorMessage, E_USER_WARNING);

                    break;
                }

                yield $key => $values->current();

                $values->next();
            }

            if ($values->valid()) {
                trigger_error($errorMessage, E_USER_WARNING);
            }
        });
    }

    /**
     * 将集合与给定项合并
     *
     * @param mixed $items
     *
     * @return static
     */
    public function union($items)
    {
        return $this->passthru('union', func_get_args());
    }

    /**
     * 创建一个包含每个第n个元素的新集合
     *
     * @param int $step
     * @param int $offset
     *
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        return new static(function () use ($step, $offset) {
            $position = 0;

            foreach ($this as $item) {
                if ($position % $step === $offset) {
                    yield $item;
                }

                $position++;
            }
        });
    }

    /**
     * 返回集合中所有指定键的集合项
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function only($keys)
    {
        if ($keys instanceof EnumeratesValues) {
            $keys = $keys->all();
        } elseif (!is_null($keys)) {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        return new static(function () use ($keys) {
            if (is_null($keys)) {
                yield from $this;
            } else {
                $keys = array_flip($keys);

                foreach ($this as $key => $value) {
                    if (array_key_exists($key, $keys)) {
                        yield $key => $value;

                        unset($keys[$key]);

                        if (empty($keys)) {
                            break;
                        }
                    }
                }
            }
        });
    }

    /**
     * 合并已有集合
     *
     * @param iterable $source
     *
     * @return static
     */
    public function concat($source)
    {
        return (new static(function () use ($source) {
            yield from $this;
            yield from $source;
        }))->values();
    }

    /**
     * 从集合中随机获取一个或指定数量的项
     *
     * @param int|null $number
     *
     * @return static|mixed
     */
    public function random($number = null)
    {
        $result = $this->collect()->random(...func_get_args());

        return is_null($number) ? $result : new static($result);
    }

    /**
     * reduce集合
     *
     * @param callable $callback
     * @param mixed $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        $result = $initial;

        foreach ($this as $value) {
            $result = $callback($result, $value);
        }

        return $result;
    }

    /**
     * 类似 merge，但是，不仅可以覆盖匹配到的相同字符串键的集合项，而且也可以覆盖数字键的集合项：
     *
     * @param mixed $items
     *
     * @return static
     */
    public function replace($items)
    {
        return new static(function () use ($items) {
            $items = $this->getArrayableItems($items);

            foreach ($this as $key => $value) {
                if (array_key_exists($key, $items)) {
                    yield $key => $items[$key];

                    unset($items[$key]);
                } else {
                    yield $key => $value;
                }
            }

            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * 类似 replace, 但是会以递归的形式将数组替换到具有相同键的集合项中
     *
     * @param mixed $items
     *
     * @return static
     */
    public function replaceRecursive($items)
    {
        return $this->passthru('replaceRecursive', func_get_args());
    }

    /**
     * 返回顺序相反的集合
     *
     * @return static
     */
    public function reverse()
    {
        return $this->passthru('reverse', func_get_args());
    }

    /**
     * 在集合中搜索给定的值并返回它的键。如果没有找到，则返回 false
     *
     * @param mixed $value
     * @param bool $strict
     *
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        $predicate = $this->useAsCallable($value)
            ? $value
            : function ($item) use ($value, $strict) {
                return $strict ? $item === $value : $item == $value;
            };

        foreach ($this as $key => $item) {
            if ($predicate($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * 打乱集合并返回结果
     *
     * @param int $seed
     *
     * @return static
     */
    public function shuffle($seed = null)
    {
        return $this->passthru('shuffle', func_get_args());
    }

    /**
     * 返回除了给定的集合项数目的新集合
     *
     * @param int $count
     *
     * @return static
     */
    public function skip($count)
    {
        return new static(function () use ($count) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $count--) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * 返回除了给定的集合项数目的新集合
     *
     * @param int $offset
     * @param int $length
     *
     * @return static
     */
    public function slice($offset, $length = null)
    {
        if ($offset < 0 || $length < 0) {
            return $this->passthru('slice', func_get_args());
        }

        $instance = $this->skip($offset);

        return is_null($length) ? $instance : $instance->take($length);
    }

    /**
     * 分割成集合组
     *
     * @param int $numberOfGroups
     *
     * @return static
     */
    public function split($numberOfGroups)
    {
        return $this->passthru('split', func_get_args());
    }

    /**
     * 将集合分块
     *
     * @param int $size
     *
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return static::empty();
        }

        return new static(function () use ($size) {
            $iterator = $this->getIterator();

            while ($iterator->valid()) {
                $chunk = [];

                while (true) {
                    $chunk[$iterator->key()] = $iterator->current();

                    if (count($chunk) < $size) {
                        $iterator->next();

                        if (!$iterator->valid()) {
                            break;
                        }
                    } else {
                        break;
                    }
                }

                yield new static($chunk);

                $iterator->next();
            }
        });
    }

    /**
     * 使用回调对每个项进行排序
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function sort(callable $callback = null)
    {
        return $this->passthru('sort', func_get_args());
    }

    /**
     * 使用给定的回调排序集合
     *
     * @param callable|string $callback
     * @param int $options
     * @param bool $descending
     *
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        return $this->passthru('sortBy', func_get_args());
    }

    /**
     * 使用给定的倒序回调排序集合
     *
     * @param callable|string $callback
     * @param int $options
     *
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->passthru('sortByDesc', func_get_args());
    }

    /**
     * 使用key对集合进行排序
     *
     * @param int $options
     * @param bool $descending
     *
     * @return static
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        return $this->passthru('sortKeys', func_get_args());
    }

    /**
     * 使用key对集合进行排序
     *
     * @param int $options
     *
     * @return static
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->passthru('sortKeysDesc', func_get_args());
    }

    /**
     * 取第一个或最后一个{$limit}项
     *
     * @param int $limit
     *
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->passthru('take', func_get_args());
        }

        return new static(function () use ($limit) {
            $iterator = $this->getIterator();

            while ($limit--) {
                if (!$iterator->valid()) {
                    break;
                }

                yield $iterator->key() => $iterator->current();

                if ($limit) {
                    $iterator->next();
                }
            }
        });
    }

    /**
     * lazily 将集合中的每个项传递给给定的回调
     *
     * @param callable $callback
     *
     * @return static
     */
    public function tapEach(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);

                yield $key => $value;
            }
        });
    }

    /**
     * 通过对键使用此集合，对其值使用另一个集合来创建集合。
     *
     * @return static
     */
    public function values()
    {
        return new static(function () {
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

    /**
     * 将集合与一个或多个数组压缩在一起
     *
     * e.g. new LazyCollection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param mixed ...$items
     *
     * @return static
     */
    public function zip($items)
    {
        $iterables = func_get_args();

        return new static(function () use ($iterables) {
            $iterators = Collection::make($iterables)->map(function ($iterable) {
                return $this->makeIterator($iterable);
            })->prepend($this->getIterator());

            while ($iterators->contains->valid()) {
                yield new static($iterators->map->current());

                $iterators->each->next();
            }
        });
    }

    /**
     * 使用值将集合填充到指定长度
     *
     * @param int $size
     * @param mixed $value
     *
     * @return static
     */
    public function pad($size, $value)
    {
        if ($size < 0) {
            return $this->passthru('pad', func_get_args());
        }

        return new static(function () use ($size, $value) {
            $yielded = 0;

            foreach ($this as $index => $item) {
                yield $index => $item;

                $yielded++;
            }

            while ($yielded++ < $size) {
                yield $value;
            }
        });
    }

    /**
     * 获取迭代器
     *
     * @return Traversable
     */
    public function getIterator()
    {
        return $this->makeIterator($this->source);
    }

    /**
     * 获取集合中元素的个数
     *
     * @return int
     */
    public function count()
    {
        if (is_array($this->source)) {
            return count($this->source);
        }

        return iterator_count($this->getIterator());
    }

    /**
     * 从给定的源生成迭代器
     *
     * @param mixed $source
     *
     * @return Traversable
     */
    protected function makeIterator($source)
    {
        if ($source instanceof IteratorAggregate) {
            return $source->getIterator();
        }

        if (is_array($source)) {
            return new ArrayIterator($source);
        }

        return $source();
    }

    /**
     * 分解传递给“cluck”的“value”和“key”参数。
     *
     * @param string|array $value
     * @param string|array|null $key
     *
     * @return array
     */
    protected function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * 通过collection类上的方法传递此惰性集合。
     *
     * @param string $method
     * @param array $params
     *
     * @return static
     */
    protected function passthru($method, array $params)
    {
        return new static(function () use ($method, $params) {
            yield from $this->collect()->$method(...$params);
        });
    }

    /**
     * __debugInfo
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->items;
    }
}
