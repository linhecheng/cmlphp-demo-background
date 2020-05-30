<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Arr实现
 * *********************************************************** */

namespace Cml\Tools;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

/**
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 */
class Arr
{
    /**
     * 判断给定的值是否为 ArrayAccess
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * 如果不存在，则使用.将元素添加到数组中。
     *
     * @param array $array
     * @param $key
     * @param $value
     *
     * @return array
     */
    public static function add(array $array, $key, $value)
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }
        return $array;
    }

    /**
     * 将数组折叠为单个数组。
     *
     * @param array $array
     *
     * @return array
     */
    public static function collapse(array $array)
    {
        $results = [];
        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (!is_array($values)) {
                continue;
            }
            $results[] = $values;
        }
        return array_merge([], ...$results);
    }

    /**
     * 交叉连接给定的数组，返回所有可能的置换。
     *
     * @param array ...$arrays
     *
     * @return array
     */
    public static function crossJoin(...$arrays)
    {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }
            $results = $append;
        }
        return $results;
    }

    /**
     * 把一个数组分成键、值两个数组。
     *
     * @param array $array
     *
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * 用点展平多维关联数组。
     *
     * @param array $array
     * @param string $prepend
     *
     * @return array
     */
    public static function dot(array $array, $prepend = '')
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    /**
     * 获取除指定键数组之外的所有给定数组。
     *
     * @param array $array
     * @param $keys
     *
     * @return array
     */
    public static function except(array $array, $keys)
    {
        static::forget($array, $keys);
        return $array;
    }

    /**
     * 确定给定的key是否存在于所提供的数组中。
     *
     * @param array|ArrayAccess $array
     * @param int|string $key
     *
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * 返回数组中通过给定真值测试的第一个元素。
     *
     * @param array $array
     * @param callable|null $callback
     * @param null $default
     *
     * @return mixed
     */
    public static function first(array $array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return self::value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }
        return self::value($default);
    }

    /**
     * 返回数组中通过给定真值测试的最后一个元素。
     *
     * @param array $array
     * @param callable|null $callback
     * @param null $default
     *
     * @return mixed|null
     */
    public static function last(array $array, callable $callback = null, $default = null)
    {

        if (is_null($callback)) {
            return empty($array) ? ($default instanceof Closure ? $default() : $default) : end($array);
        }
        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * 将多维数组展平到单个级别。
     *
     * @param array $array
     * @param $depth
     *
     * @return array
     */
    public static function flatten(array $array, $depth = INF)
    {
        $result = [];
        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, static::flatten($item, $depth - 1));
            }
        }
        return $result;
    }

    /**
     * 用.符号从给定数组中移除一个或多个数组项。
     *
     * @param array $array
     * @param $keys
     */
    public static function forget(array &$array, $keys)
    {
        $original = &$array;
        $keys = (array)$keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }

    private static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * 使用.从数组中获取项。
     *
     * @param array|ArrayAccess $array
     * @param null|int|string $key
     * @param mixed $default
     *
     * @return array|ArrayAccess|mixed
     */
    public static function get($array, $key = null, $default = null)
    {
        if (!static::accessible($array)) {
            return self::value($default);
        }
        if (is_null($key)) {
            return $array;
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        if (!is_string($key) || strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : self::value($default);
        }
        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return self::value($default);
            }
        }
        return $array;
    }

    /**
     * 使用.表示法检查数组中是否存在项或项。
     *
     * @param array|ArrayAccess $array
     * @param array|string $keys
     *
     * @return bool
     */
    public static function has($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }
        $keys = (array)$keys;
        if (!$array) {
            return false;
        }
        if ($keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            $subKeyArray = $array;
            if (static::exists($array, $key)) {
                continue;
            }
            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 确定数组是否关联。
     * 如果数组没有以零开头的连续数字键，则它是“关联的”。
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * 从给定数组中获取指定项的子集。
     *
     * @param array $array
     * @param $keys
     *
     * @return array
     */
    public static function only(array $array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * 使用.符号从数组或对象中获取项。
     *
     * @param array|int|string $key
     * @param null|mixed $default
     * @param mixed $target
     *
     * @return mixed
     */
    public static function dataGet($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return self::value($default);
                }
                $result = [];
                foreach ($target as $item) {
                    $result[] = self::dataGet($item, $key);
                }
                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return self::value($default);
            }
        }
        return $target;
    }

    /**
     * 从数组中提取值数组。
     *
     * @param array $array
     * @param $value
     * @param null $key
     *
     * @return array
     */
    public static function pluck(array $array, $value, $key = null)
    {
        $results = [];
        [$value, $key] = static::explodePluckParameters($value, $key);
        foreach ($array as $item) {
            $itemValue = self::dataGet($item, $value);
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = self::dataGet($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string)$itemKey;
                }
                $results[$itemKey] = $itemValue;
            }
        }
        return $results;
    }

    /**
     * 将元素加数组的开头。
     *
     * @param array $array
     * @param $value
     * @param null $key
     *
     * @return array
     */
    public static function prepend(array $array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }

    /**
     * 从数组中获取一个值，并将其移除。
     *
     * @param array $array
     * @param string $key
     * @param null $default
     *
     * @return array|ArrayAccess|mixed
     */
    public static function pull(array &$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);
        return $value;
    }

    /**
     * 从数组中获取一个或指定数量的随机值。
     *
     * @param array $array
     * @param int|null $number
     *
     * @return array|mixed
     */
    public static function random(array $array, int $number = null)
    {
        $requested = is_null($number) ? 1 : $number;
        $count = count($array);
        if ($requested > $count) {
            throw new InvalidArgumentException("You requested {$requested} items, but there are only {$count} items available.");
        }
        if (is_null($number)) {
            return $array[array_rand($array)];
        }
        if ((int)$number === 0) {
            return [];
        }
        $keys = array_rand($array, $number);
        $results = [];
        foreach ((array)$keys as $key) {
            $results[] = $array[$key];
        }
        return $results;
    }

    /**
     * 使用“.”符号将数组项设置为给定值。
     * 如果没有为该方法指定键，则将替换整个数组。
     *
     * @param array|ArrayAccess $array
     * @param null|int|string $key
     * @param mixed $value
     *
     * @return array
     */
    public static function set(array &$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        if (!is_string($key)) {
            $array[$key] = $value;
            return $array;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * 打乱给定的数组并返回结果
     *
     * @param array $array
     * @param int|null $seed
     *
     * @return array
     */
    public static function shuffle(array $array, int $seed = null)
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            srand($seed);
            usort($array, function () {
                return rand(-1, 1);
            });
        }
        return $array;
    }

    /**
     * 使用给定的回调或.符号对数组排序。
     *
     * @param array $array
     * @param null $callback
     *
     * @return array
     */
    public static function sort(array $array, $callback = null)
    {
        return Collection::make($array)->sortBy($callback)->all();
    }

    /**
     * 按键和值递归排序数组
     *
     * @param array $array
     *
     * @return array
     */
    public static function sortRecursive(array $array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }
        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }
        return $array;
    }

    /**
     * 将数组转换为查询字符串
     *
     * @param array $array
     *
     * @return string
     */
    public static function query(array $array)
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 使用给定的回调筛选数组。
     *
     * @param array $array
     * @param callable $callback
     *
     * @return array
     */
    public static function where(array $array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * 如果给定值不是数组且不为空，将其包装为一个数组。
     *
     * @param mixed $value
     *
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }
        return !is_array($value) ? [$value] : $value;
    }

    /**
     * 使数组元素唯一。
     *
     * @param array $array
     *
     * @return array
     */
    public static function unique(array $array)
    {
        $result = [];
        foreach ($array ?? [] as $key => $item) {
            if (is_array($item)) {
                $result[$key] = self::unique($item);
            } else {
                $result[$key] = $item;
            }
        }

        if (!self::isAssoc($result)) {
            return array_unique($result);
        }

        return $result;
    }

    /**
     * 分解传递给“cluck”的“value”和“key”参数。
     *
     * @param array|string $value
     * @param null|array|string $key
     *
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);
        return [$value, $key];
    }
}
