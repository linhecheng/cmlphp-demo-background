<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Collection实现
 * *********************************************************** */

namespace Cml\Tools;

use ArrayAccess;
use Cml\Interfaces\ArrayAble;
use Cml\Interfaces\Jsonable;
use Countable;
use IteratorAggregate;
use stdClass;
use Traversable;
use JsonSerializable;
use Closure;

/**
 * Class Collection
 *
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 *
 * @package Cml\Tools
 */
class Collection implements ArrayAccess, ArrayAble, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    use ArrayJsonSpl, EnumeratesValues;

    /**
     * Create a new collection.
     *
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayAbleItems($items);
    }

    /**
     * 获取集合中的所有元素
     *
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * 获取集合中元素的个数
     *
     * @reutrn int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * 与给定列表交叉联接，返回所有可能的排列。
     *
     * @param array $lists
     *
     * @return Collection
     */
    public function crossJoin(...$lists)
    {
        return new static(Arr::crossJoin($this->items, ...array_map([$this, 'getArrayAbleItems'], $lists)));
    }

    /**
     * 获取集合中不在给定项中的项。
     *
     * @param mixed $items
     *
     * @return Collection
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayAbleItems($items)));
    }

    /**
     * 获取集合中键和值不在给定项中的项。
     *
     * @param mixed $items
     *
     * @return Collection
     */
    public function diffAssoc($items)
    {
        return new static(array_diff_assoc($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 获取集合中键和值不在给定项中的项。
     *
     * @param mixed $items
     * @param callable $callback
     *
     * @return Collection
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return new static(array_diff_uassoc($this->items, $this->getArrayAbleItems($items), $callback));
    }

    /**
     * 获取集合中键不在给定项中的项。
     *
     * @param mixed $items
     *
     * @return Collection
     */
    public function diffKeys($items)
    {
        return new static(array_diff_key($this->items, $this->getArrayAbleItems($items)));
    }

    /**
     * 获取集合中不在给定项中的项。
     *
     * @param mixed $items
     * @param callable $callback
     *
     * @return Collection
     */
    public function diffUsing($items, callable $callback)
    {
        return new static(array_udiff($this->items, $this->getArrayAbleItems($items), $callback));
    }

    /**
     * 使用回调函数获取集合中键不在给定项中的项
     *
     * @param mixed $items
     * @param callable $callback
     *
     * @return static
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return new static(array_diff_ukey($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * 从集合中检索重复项
     *
     * @param callable|null $callback
     * @param bool $strict
     *
     * @return static
     */
    public function duplicates($callback = null, $strict = false)
    {
        $items = $this->map($this->valueRetriever($callback));

        $uniqueItems = $items->unique(null, $strict);

        $compare = $this->duplicateComparator($strict);

        $duplicates = new static;

        foreach ($items as $key => $value) {
            if ($uniqueItems->isNotEmpty() && $compare($value, $uniqueItems->first())) {
                $uniqueItems->shift();
            } else {
                $duplicates[$key] = $value;
            }
        }

        return $duplicates;
    }

    /**
     * 使用严格比较从集合中检索重复项
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function duplicatesStrict($callback = null)
    {
        return $this->duplicates($callback, true);
    }

    /**
     * 获取比较函数以检测重复项
     *
     * @param bool $strict
     *
     * @return Closure
     */
    protected function duplicateComparator($strict)
    {
        if ($strict) {
            return function ($a, $b) {
                return $a === $b;
            };
        }

        return function ($a, $b) {
            return $a == $b;
        };
    }

    /**
     * 获取给定元素的平均值
     *
     * @param null|callable|string $callback
     *
     * @return float|int
     */
    public function avg($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        $items = $this->map(function ($value) use ($callback) {
            return $callback($value);
        })->filter(function ($value) {
            return !is_null($value);
        });
        if ($count = $items->count()) {
            return $items->sum() / $count;
        }
        return 0;
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
     * 通过字段或使用回调为关联数组设置键。
     *
     * @param callable|string $keyBy
     *
     * @return $this
     */
    public function keyBy($keyBy)
    {
        $keyBy = $this->valueRetriever($keyBy);
        $results = [];
        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);
            if (is_object($resolvedKey)) {
                $resolvedKey = (string)$resolvedKey;
            }
            $results[$resolvedKey] = $item;
        }
        return new static($results);
    }

    /**
     * 获取集合项的key
     *
     * @return $this
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * 将集合中的值用字符串连接
     *
     * @param string $glue
     * @param string $finalGlue
     *
     * @return string
     */
    public function join($glue, $finalGlue = '')
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }

        $count = $this->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $this->last();
        }

        $collection = new static($this->items);

        $finalItem = $collection->pop();

        return $collection->implode($glue) . $finalGlue . $finalItem;
    }

    /**
     * 取集合中的最后一个元素
     *
     * @param callable|null $callback
     * @param null $default
     *
     * @return mixed|null
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * 对集合执行array_pop
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * 对集合执行array_shift
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * push一个元素
     *
     * @param mixed $value
     *
     * @return $this;
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);
        return $this;
    }

    /**
     * 打乱集合并返回结果
     *
     * @param int|null $seed
     *
     * @return $this
     */
    public function shuffle($seed = null)
    {
        return new static(Arr::shuffle($this->items, $seed));
    }

    /**
     * Put一个元素slice
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this;
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * 从集合中随机获取一个或指定数量的项
     *
     * @param int|null $number
     *
     * @return static
     */
    public function random($number = null)
    {
        if (is_null($number)) {
            return Arr::random($this->items);
        }
        return new static(Arr::random($this->items, $number));
    }

    /**
     * 将项目推到集合的开头
     *
     * @param null|mixed $key
     * @param mixed $value
     *
     * @return $this;
     */
    public function prepend($value, $key = null)
    {
        $this->items = Arr::prepend($this->items, $value, $key);
        return $this;
    }

    /**
     * 从集合中获取并移除项
     *
     * @param null|mixed $default
     * @param mixed $key
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * 合并已有集合
     *
     * @param array|Traversable $source
     *
     * @return self
     */
    public function concat($source)
    {
        $result = new static($this);
        foreach ($source as $item) {
            $result->push($item);
        }
        return $result;
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
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * 返回顺序相反的集合
     *
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
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
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }
        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }
        return false;
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
        return $this->slice($count);
    }

    /**
     * slice集合
     *
     * @param int $offset
     * @param int|null $length
     *
     * @return $this
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * 分割成集合组
     *
     * @param int $numberOfGroups 集合组的数量
     *
     * @return static
     */
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new static();
        }
        $groups = new static();
        $groupSize = floor($this->count() / $numberOfGroups);
        $remain = $this->count() % $numberOfGroups;
        $start = 0;
        for ($i = 0; $i < $numberOfGroups; ++$i) {
            $size = $groupSize;
            if ($i < $remain) {
                ++$size;
            }
            if ($size) {
                $groups->push(new static(array_slice($this->items, $start, $size)));
                $start += $size;
            }
        }
        return $groups;
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
            return $this->slice($limit, abs($limit));
        }
        return $this->slice(0, $limit);
    }

    /**
     * 通过调用给定次数的回调来创建新集合
     *
     * @param int $number
     * @param callable|null $callback
     *
     * @return static
     */
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new static();
        }
        if (is_null($callback)) {
            return new static(range(1, $number));
        }
        return (new static(range(1, $number)))->map($callback);
    }

    /**
     * 将集合分块
     *
     * @param int $size 块的大小
     *
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static();
        }
        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }
        return new static($chunks);
    }

    /**
     * 将集合折叠为单个数组。
     *
     */
    public function collapse()
    {
        return new static(Arr::collapse($this->items));
    }

    /**
     * 返回数据中指定的一列
     *
     * @param string $columnKey 键名
     * @param string $indexKey 作为索引值的列
     *
     * @return array
     */
    public function column($columnKey, $indexKey = null)
    {
        return array_column($this->items, $columnKey, $indexKey);
    }

    /**
     * 通过对键使用此集合，对其值使用另一个集合来创建集合。
     *
     * @param $values
     *
     * @return $this
     */
    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->getArrayAbleItems($values)));
    }

    /**
     * 如果适用，将给定值包装在集合中
     *
     * @param $value
     *
     * @return static
     */
    public static function wrap($value)
    {
        return $value instanceof self ? new static($value) : new static(Arr::wrap($value));
    }

    /**
     * 过滤集合中的元素。只保留回调函数返回true的元素，如果没有提供回调函数，集合中所有返回 false 的元素都会被移除
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function filter(callable $callback)
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * 从集合中获取第一个项目。
     *
     * @param callable|null $callback
     * @param null $default
     *
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * 判断集合是否包含指定的集合项
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                $placeholder = new stdClass();

                return $this->first($key, $placeholder) !== $placeholder;
            }

            return in_array($key, $this->items);
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * 将多维数组展平到单个级别。
     *
     * @param int $depth
     *
     * @return $this
     */
    public function flatten($depth = INF)
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    /**
     * 翻转集合中的项目。
     *
     * @rteurn $this;
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * 按键从集合中移除项
     *
     * @param mixed $keys
     *
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array)$keys as $key) {
            $this->offsetUnset($key);
        }
        return $this;
    }

    /**
     * 使用给定的回调排序集合。
     *
     * @param $callback
     * @param int $options
     * @param bool $descending
     *
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];
        $callback = $this->valueRetriever($callback);
        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }
        $descending ? arsort($results, $options) : asort($results, $options);
        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }
        return new static($results);
    }

    /**
     * 使用给定的倒序回调排序集合
     *
     * @param $callback
     * @param int $options
     *
     * @return $this
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * 使用key对集合进行排序
     *
     * @param int $options
     * @param bool $descending
     *
     * @return $this
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        $items = $this->items;
        $descending ? krsort($items, $options) : ksort($items, $options);
        return new static($items);
    }

    /**
     * 使用key对集合进行排序-倒序
     *
     * @param int $options
     *
     * @return $this
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->sortKeys($options, true);
    }

    /**
     * 去掉集合中的某一部分并用其它值取代
     *
     * @param int $offset
     * @param int|null $length
     * @param array $replacement
     *
     * @return $this
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() === 1) {
            return new static(array_splice($this->items, $offset));
        }
        return new static(array_splice($this->items, $offset, $length, $replacement));
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
        return new static(array_replace($this->items, $this->getArrayableItems($items)));
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
        return new static(array_replace_recursive($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 使用集合中的值组成新的集合
     *
     * @return $this
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * 获取key获取集合的元素
     *
     * @param null|mixed $default
     * @param mixed $key
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * 通过字段或使用回调对关联数组进行分组
     *
     * @param $groupBy
     * @param bool $preserveKeys
     *
     * @return $this
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (is_array($groupBy)) {
            $nextGroups = $groupBy;
            $groupBy = array_shift($nextGroups);
        }
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int)$groupKey : $groupKey;
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static();
                }
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        $result = new static($results);
        if (!empty($nextGroups)) {
            return (new HigherOrderCollectionProxy($result, 'map'))->groupBy($nextGroups, $preserveKeys);
        }
        return $result;
    }

    /**
     * 通过键确定集合中是否存在项。
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if (!$this->offsetExists($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 将给定键的值连接为字符串
     *
     * @param $value
     * @param null $glue
     *
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();
        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }
        return implode($value, $this->items);
    }

    /**
     * 使集合与给定项相交
     *
     * @param $items
     *
     * @return $this
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 按键将集合与给定项相交
     *
     * @param $items
     *
     * @return $this
     */
    public function intersectByKeys($items)
    {
        return new static(array_intersect_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 获取给定key的值
     *
     * @param $value
     * @param string|null $key
     *
     * @return $this
     */
    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * 获取除具有指定键的项以外的所有项。
     *
     * @param $keys
     *
     * @return $this
     */
    public function except($keys)
    {
        if ($keys instanceof self) {
            $keys = $keys->all();
        } elseif (!is_array($keys)) {
            $keys = func_get_args();
        }
        return new static(Arr::except($this->items, $keys));
    }

    /**
     * 用回调函数处理数组中的元素
     *
     * @param callable $callback 回调
     *
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    /**
     * 在项目上运行字典 map
     * 回调应返回具有单个键/值对的关联数组。
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function mapToDictionary(callable $callback)
    {
        $dictionary = [];
        foreach ($this->items as $key => $item) {
            $pair = $callback($item, $key);
            $key = key($pair);
            $value = reset($pair);
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $value;
        }
        return new static($dictionary);
    }

    /**
     * 对每个项运行关联映射。
     * 回调应返回具有单个键/值对的关联数组。
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }
        return new static($result);
    }

    /**
     * 使用回调对每个项进行排序。
     *
     * @param callable $callback
     *
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);
        return new static($items);
    }

    /**
     * 获取给定key的中间值。
     *
     * @param string $key
     *
     * @return mixed
     */
    public function median($key = null)
    {
        $values = (isset($key) ? $this->pluck($key) : $this)->filter(function ($item) {
            return !is_null($item);
        })->sort()->values();
        $count = $values->count();
        if ($count == 0) {
            return null;
        }
        $middle = (int)($count / 2);
        if ($count % 2) {
            return $values->get($middle);
        }
        return (new static([
            $values->get($middle - 1),
            $values->get($middle),
        ]))->avg();
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
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
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
        return new static(array_merge_recursive($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 获取给定key的众数
     *
     * @param null|mixed $key
     *
     * @return null|array
     */
    public function mode($key = null)
    {
        if ($this->count() == 0) {
            return null;
        }
        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = new static();
        $collection->each(function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        $sorted = $counts->sort();
        $highestValue = $sorted->last();
        return $sorted->filter(function ($value) use ($highestValue) {
            return $value == $highestValue;
        })->sort()->keys()->all();
    }

    /**
     * 创建一个包含每个第n个元素的新集合。
     *
     * @param int $step
     * @param int $offset
     *
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        $new = [];
        $position = 0;
        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            ++$position;
        }
        return new static($new);
    }

    /**
     * 返回集合中所有指定键的集合项
     *
     * @param $keys
     *
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }
        if ($keys instanceof self) {
            $keys = $keys->all();
        }
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::only($this->items, $keys));
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
        return new static(array_pad($this->items, $size, $value));
    }

    /**
     * 使用回调转换集合中的每个项
     * @param callable $callback
     *
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();
        return $this;
    }

    /**
     * 将集合与给定项合并
     *
     * @param $items
     *
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * 从给定集合中获取基础项（如果适用）
     *
     * @param $value
     *
     * @return array
     */
    public static function unwrap($value)
    {
        return $value instanceof self ? $value->all() : $value;
    }

    /**
     * 将集合与一个或多个数组压缩在一起
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]].
     *
     * @param mixed ...$items
     *
     * @return static
     */
    public function zip($items)
    {
        $arrayAbleItems = array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, func_get_args());
        $params = array_merge([
            function () {
                return new static(func_get_args());
            },
            $this->items,
        ], $arrayAbleItems);
        return new static(call_user_func_array('array_map', $params));
    }
}
