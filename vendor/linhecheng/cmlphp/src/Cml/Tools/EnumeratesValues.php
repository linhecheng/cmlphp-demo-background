<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 EnumeratesValues实现
 * *********************************************************** */

namespace Cml\Tools;

use CachingIterator;
use Closure;
use Cml\Interfaces\ArrayAble;
use Cml\Interfaces\Jsonable;
use JsonSerializable;
use Traversable;
use function Cml\dump;

/**
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 *
 * @property-read HigherOrderCollectionProxy $average
 * @property-read HigherOrderCollectionProxy $avg
 * @property-read HigherOrderCollectionProxy $contains
 * @property-read HigherOrderCollectionProxy $each
 * @property-read HigherOrderCollectionProxy $every
 * @property-read HigherOrderCollectionProxy $filter
 * @property-read HigherOrderCollectionProxy $first
 * @property-read HigherOrderCollectionProxy $flatMap
 * @property-read HigherOrderCollectionProxy $groupBy
 * @property-read HigherOrderCollectionProxy $keyBy
 * @property-read HigherOrderCollectionProxy $map
 * @property-read HigherOrderCollectionProxy $max
 * @property-read HigherOrderCollectionProxy $min
 * @property-read HigherOrderCollectionProxy $partition
 * @property-read HigherOrderCollectionProxy $reject
 * @property-read HigherOrderCollectionProxy $sortBy
 * @property-read HigherOrderCollectionProxy $sortByDesc
 * @property-read HigherOrderCollectionProxy $sum
 * @property-read HigherOrderCollectionProxy $unique
 *
 * @method __construct($items = [])
 *
 * @mixin Collection
 */
trait EnumeratesValues
{
    /**
     * 可以被代理的方法
     *
     * @var array
     */
    protected static $proxies = [
        'average', 'avg', 'contains', 'each', 'every', 'filter', 'first',
        'flatMap', 'groupBy', 'keyBy', 'map', 'max', 'min', 'partition',
        'reject', 'some', 'sortBy', 'sortByDesc', 'sum', 'unique',
    ];

    /**
     * 实例化对象
     *
     * @param mixed $items
     *
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * 如果适用，将给定值包装在集合中。
     *
     * @param mixed $value
     *
     * @return static
     */
    public static function wrap($value)
    {
        return $value instanceof EnumeratesValues
            ? new static($value)
            : new static(Arr::wrap($value));
    }

    /**
     * 如果适用，则设置给定集合中的基础项
     *
     * @param array|static $value
     * @return array
     */
    public static function unwrap($value)
    {
        return $value instanceof EnumeratesValues ? $value->all() : $value;
    }

    /**
     * avg 的别名
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * contains方法的别名
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return bool
     */
    public function some($key, $operator = null, $value = null)
    {
        return $this->contains(...func_get_args());
    }

    /**
     * 判断集合是否包含指定的集合项 「严格」
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        if (func_num_args() === 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return Arr::dataGet($item, $key) === $value;
            });
        }

        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }

        foreach ($this as $item) {
            if ($item === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * 打印items并结束
     *
     * @param mixed ...$args
     * @return void
     */
    public function dd(...$args)
    {
        call_user_func_array([$this, 'dump'], $args);
        die(1);
    }

    /**
     * 打印items
     *
     * @return $this
     */
    public function dump()
    {
        (new static(func_get_args()))
            ->push($this)
            ->each(function ($item) {
                dump($item);
            });

        return $this;
    }

    /**
     * 给每个元素执行个回调
     *
     * @param callable $callback 回调
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * 对每个嵌套的项块执行回调
     *
     * @param callable $callback
     *
     * @return static
     */
    public function eachSpread(callable $callback)
    {
        return $this->each(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * 验证集合中的每一个元素是否通过指定的条件测试
     *
     * @param string|callable $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return bool
     */
    public function every($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);

            foreach ($this as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * 集合中含有指定键 / 值对的第一个元素
     *
     * @param string $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return mixed
     */
    public function firstWhere($key, $operator = null, $value = null)
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * 判断集合是否不为空
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * 在每个嵌套的项上运行map
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function mapSpread(callable $callback)
    {
        return $this->map(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;
            return $callback(...$chunk);
        });
    }

    /**
     * 对项目运行分组map
     * 回调应返回具有单个键/值对的关联数组。
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function mapToGroups(callable $callback)
    {
        $groups = $this->mapToDictionary($callback);
        return $groups->map([$this, 'make']);
    }

    /**
     * 映射集合并将结果展平一个级别。
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * 迭代元素到一个新的类中
     *
     * @param string $class
     *
     * @return static
     */
    public function mapInto($class)
    {
        return $this->map(function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    /**
     * 获取给定key的最小值
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->map(function ($value) use ($callback) {
            return $callback($value);
        })->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $value) {
            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * 获取给定key的最大值
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * 通过将集合分割成较小的集合来“分页”。
     *
     * @param int $page
     * @param int $perPage
     *
     * @return $this
     */
    public function forPage($page, $perPage)
    {
        $offset = max(0, ($page - 1) * $perPage);
        return $this->slice($offset, $perPage);
    }

    /**
     * 用来分开通过指定条件的元素以及那些不通过指定条件的元素
     *
     * @param callable|string $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return static
     */
    public function partition($key, $operator = null, $value = null)
    {
        $passed = [];
        $failed = [];

        $callback = func_num_args() === 1
            ? $this->valueRetriever($key)
            : $this->operatorForWhere(...func_get_args());

        foreach ($this as $key => $item) {
            if ($callback($item, $key)) {
                $passed[$key] = $item;
            } else {
                $failed[$key] = $item;
            }
        }

        return new static([new static($passed), new static($failed)]);
    }

    /**
     * 获取给定元素的和
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            $callback = function ($value) {
                return $value;
            };
        } else {
            $callback = $this->valueRetriever($callback);
        }

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * 如果值为true，则应用回调
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     *
     * @return $this
     */
    public function when(bool $value, callable $callback, callable $default = null)
    {
        if ($value) {
            return $callback($this, $value);
        }
        if ($default) {
            return $default($this, $value);
        }
        return $this;
    }

    /**
     * 如果集合为空，则应用回调
     *
     * @param callable $callback
     * @param callable $default
     *
     * @return static|mixed
     */
    public function whenEmpty(callable $callback, callable $default = null)
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }

    /**
     * 如果集合不为空，则应用回调
     *
     * @param callable $callback
     * @param callable $default
     *
     * @return static|mixed
     */
    public function whenNotEmpty(callable $callback, callable $default = null)
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }

    /**
     * 如果值错误，则应用回调
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     *
     * @return $this
     */
    public function unless(bool $value, callable $callback, callable $default = null)
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * 除非集合为空，否则应用回调
     *
     * @param callable $callback
     * @param callable $default
     * @return static|mixed
     */
    public function unlessEmpty(callable $callback, callable $default = null)
    {
        return $this->whenNotEmpty($callback, $default);
    }

    /**
     * 除非集合不为空，否则应用回调
     *
     * @param callable $callback
     * @param callable $default
     * @return static|mixed
     */
    public function unlessNotEmpty(callable $callback, callable $default = null)
    {
        return $this->whenEmpty($callback, $default);
    }

    /**
     * 根据字段条件过滤数组中的元素
     *
     * @param string $key 字段名
     * @param mixed $operator 操作符
     * @param mixed $value 值
     *
     * @return static
     */
    public function where($key, $operator = null, $value = null)
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * 根据字段条件过滤数组中的元素 全等
     *
     * @param string $key 字段名
     * @param mixed $value 值
     *
     * @return static
     */
    public function whereStrict(string $key, $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * IN过滤
     *
     * @param string $key 字段名
     * @param array $values 值
     * @param bool $strict 是否严格模式
     *
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(Arr::dataGet($item, $key), $values, $strict);
        });
    }


    /**
     * IN过滤-严格模式
     *
     * @param string $key 字段名
     * @param array $values 值
     *
     * @return static
     */
    public function whereInStrict(string $key, $values)
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * NOT IN过滤
     *
     * @param string $key 字段名
     * @param array $values 值
     * @param bool $strict 是否严格模式
     *
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);
        return $this->reject(function ($item) use ($key, $values, $strict) {
            return in_array(Arr::dataGet($item, $key), $values, $strict);
        });
    }

    /**
     * Not IN过滤-严格模式
     *
     * @param string $key 字段名
     * @param array $values 值
     *
     * @return static
     */
    public function whereNotInStrict($key, $values)
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * InstanceOf过滤
     *
     * @param mixed $type
     *
     * @return static
     */
    public function whereInstanceOf($type)
    {
        return $this->filter(function ($value) use ($type) {
            return $value instanceof $type;
        });
    }

    /**
     * BETWEEN 过滤
     *
     * @param string $key 字段名
     * @param mixed $values 值
     *
     * @return static
     */
    public function whereBetween($key, $values)
    {
        return $this->where($key, '>=', reset($values))->where($key, '<=', end($values));
    }

    /**
     * NOT BETWEEN 过滤
     *
     * @param string $key 字段名
     * @param mixed $values 值
     *
     * @return static
     */
    public function whereNotBetween($key, $values)
    {
        return $this->filter(function ($item) use ($key, $values) {
            return Arr::dataGet($item, $key) < reset($values) || Arr::dataGet($item, $key) > end($values);
        });
    }


    /**
     * like 过滤
     *
     * @param string $key 字段名
     * @param string $value 值
     *
     * @return static
     */
    public function whereLike($key, $value)
    {
        return $this->where($key, 'like', $value);
    }

    /**
     * not like过滤
     *
     * @param string $key 字段名
     * @param string $value 值
     *
     * @return static
     */
    public function whereNotLike($key, $value)
    {
        return $this->where($key, 'not like', $value);
    }

    /**
     * 将集合传递给给定的回调并返回结果
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * 将集合传递给给定的回调，然后返回它
     *
     * @param callable $callback
     *
     * @return static
     */
    public function tap(callable $callback)
    {
        $callback(new static($this->items));
        return $this;
    }

    /**
     * 过滤集合中的元素。如果回调函数返回 true 就会把对应的集合项从集合中移除
     *
     * @param callable|mixed $callback
     *
     * @return static
     */
    public function reject($callback = true)
    {
        $useAsCallable = $this->useAsCallable($callback);

        return $this->filter(function ($value, $key) use ($callback, $useAsCallable) {
            return $useAsCallable
                ? !$callback($value, $key)
                : $value != $callback;
        });
    }

    /**
     * 仅返回集合数组中的唯一项
     *
     * @param null $key
     * @param bool $strict
     *
     * @return $this
     */
    public function unique($key = null, bool $strict = false)
    {
        $callback = $this->valueRetriever($key);
        $exists = [];
        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }
            $exists[] = $id;
            return false;
        });
    }

    /**
     * 使用严格比较仅返回集合数组中的唯一项
     *
     * @param null $key
     *
     * @return $this
     */
    public function uniqueStrict($key = null)
    {
        return $this->unique($key, true);
    }

    /**
     * 返回一个新的 Collection 实例，其中包含当前集合中的项目
     *
     * @return Collection
     */
    public function collect()
    {
        return new Collection($this->all());
    }


    /**
     * Get a CachingIterator instance.
     *
     * @param int $flags
     *
     * @return CachingIterator
     */
    public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * 计算集合中每个值的出现次数。默认情况下，该方法计算每个元素的出现次数
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function countBy($callback = null)
    {
        if (is_null($callback)) {
            $callback = function ($value) {
                return $value;
            };
        }

        return new static($this->groupBy($callback)->map(function ($value) {
            /**@var Collection $value * */
            return $value->count();
        }));
    }


    /**
     * 将方法添加到代理方法的列表中。
     *
     * @param string $method
     *
     * @return void
     */
    public static function proxy($method)
    {
        static::$proxies[] = $method;
    }

    /**
     * 动态访问收集代理
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (!in_array($key, static::$proxies)) {
            return null;
        }

        return new HigherOrderCollectionProxy($this, $key);
    }


    /**
     * 获取where回调
     *
     * @param string $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return Closure
     */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return function ($item) use ($key, $operator, $value) {
            $retrieved = Arr::dataGet($item, $key);
            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });
            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }
            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
                case 'like':
                    return is_string($retrieved) && false !== mb_stripos($retrieved, $value);
                case 'not like':
                    return is_string($retrieved) && false === mb_stripos($retrieved, $value);
            }
        };
    }

    /**
     * 判断是否为callable
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     *  获取检索回调的值。
     *
     * @param mixed $value
     *
     * @return Closure|mixed
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        return function ($item) use ($value) {
            return Arr::dataGet($item, $value);
        };
    }

    /**
     * 处理元素返回array
     *
     * @param mixed $items
     *
     * @return mixed
     */
    protected function getArrayAbleItems($items)
    {
        if (is_array($items)) {
            return $items;
        }
        if ($items instanceof self) {
            return $items->all();
        }
        if ($items instanceof ArrayAble) {
            return $items->toArray();
        }
        if ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        }
        if ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        }
        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        return (array)$items;
    }
}
