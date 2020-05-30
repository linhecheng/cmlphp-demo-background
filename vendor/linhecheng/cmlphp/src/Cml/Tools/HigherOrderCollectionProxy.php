<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 HigherOrderCollectionProxy
 * *********************************************************** */


namespace Cml\Tools;

/**
 * Collection代理类
 *
 * @mixin Collection
 *
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 */
class HigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * @var Collection
     */
    protected $collection;

    /**
     * 该方法是代理的。
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new proxy instance.
     *
     * @param Collection $collection
     * @param string $method
     */
    public function __construct(Collection $collection, string $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * 代理访问集合项的属性。
     *
     * @param $key
     *
     * @return Collection
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * 代理对集合项的方法调用。
     *
     * @param $method
     * @param array $parameters
     *
     * @return Collection
     */
    public function __call($method, array $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
