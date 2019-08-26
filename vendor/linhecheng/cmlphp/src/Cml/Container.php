<?php

namespace Cml;
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-09-10 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 容器
 * *********************************************************** */

class Container
{

    /**
     * 绑定的规则
     *
     * @var array
     */
    protected $binds = [];

    /**
     * 可执行实例
     *
     * @var array
     */
    protected $instances = [];

    /**
     * 别名
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * 绑定服务
     *
     * @param mixed $abstract 要绑定的服务，传数组的时候则设置别名
     * @param mixed $concrete 实际执行的服务
     * @param bool $singleton 是否为单例
     *
     * @return $this
     */
    public function bind($abstract, $concrete = null, $singleton = false)
    {
        if (is_array($abstract)) {
            list($abstract, $alias) = [key($abstract), current($abstract)];
            $this->alias($abstract, $alias);
        }

        $abstract = $this->filter($abstract);
        $concrete = $this->filter($concrete);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->binds[$abstract] = compact('concrete', 'singleton');
        return $this;
    }

    /**
     * 判断是否绑定过某服务
     *
     * @param string $abstract 服务的名称
     *
     * @return bool
     */
    public function isBind($abstract)
    {
        return isset($this->binds[$abstract]);
    }

    /**
     * 绑定一个别名
     *
     * @param  string $abstract 服务的名称
     * @param  string $alias 别名
     *
     * @return $this
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $this->filter($abstract);
        return $this;
    }

    /**
     * 获取绑定的别名
     *
     * @param  string $alias 别名
     * @return mixed
     */
    public function getAlias($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : false;
    }

    /**
     * 判断别名是否存在
     *
     * @param  string $alias 别名
     *
     * @return bool
     */
    public function isExistAlias($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * 过滤
     *
     * @param mixed $abstract 服务的名称
     *
     * @return string
     */
    private function filter($abstract)
    {
        return is_string($abstract) ? ltrim($abstract, '\\') : $abstract;
    }

    /**
     * 绑定单例服务
     *
     * @param  string|array $abstract 服务的名称
     * @param  \Closure|string|null $concrete
     * @return $this
     */
    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * 实例化服务
     *
     * @param mixed $abstract 服务的名称
     * @param mixed $parameters 参数
     *
     * @return mixed
     */
    public function make($abstract, $parameters = [])
    {
        if ($alias = $this->getAlias($abstract)) {
            $abstract = $alias;
        }

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->binds[$abstract])) {
            throw new \InvalidArgumentException(Lang::get('_CONTAINER_MAKE_PARAMS_ERROR_', $abstract));
        }

        if ($this->binds[$abstract]['concrete'] instanceof \Closure) {
            array_unshift($parameters, $this);
            $instance = call_user_func_array($this->binds[$abstract]['concrete'], (array)$parameters);
        } else {
            $concrete = $this->binds[$abstract]['concrete'];
            $instance = new $concrete($parameters);
        }
        $this->binds[$abstract]['singleton'] && $this->instances[$abstract] = $instance;

        return $instance;
    }
}
