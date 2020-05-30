<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 多态反向关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use BadMethodCallException;
use Closure;
use Cml\Entity\Entity;
use Cml\Lang;
use function Cml\studlyCase;

/**
 * 多态反向关联类
 */
class MorphTo extends Base
{
    /**
     * 多态关联外键
     *
     * @var string
     */
    protected $morphKey;

    /**
     * 多态字段
     *
     * @var string
     */
    protected $morphType;

    /**
     * 多态别名
     *
     * @var array
     */
    protected $alias = [];

    /**
     * 关联名
     *
     * @var string
     */
    protected $relation;

    /**
     * 架构函数
     *
     * @param Entity $parent 上级实体对象
     * @param string $morphType 多态字段名
     * @param string $morphKey 外键名
     * @param array $alias 多态别名定义
     */
    public function __construct(Entity $parent, $morphType, $morphKey, array $alias = [])
    {
        $this->parent = $parent;
        $this->morphType = $morphType;
        $this->morphKey = $morphKey;
        $this->alias = $alias;
    }

    /**
     * 延迟获取关联数据
     *
     * @return Entity
     */
    public function getRelation()
    {
        $morphKey = $this->morphKey;
        $morphType = $this->morphType;

        /**@var Entity $entity * */
        $entity = $this->parseEntity($this->parent->$morphType);
        $pk = $this->parent->$morphKey;

        return $entity::find($pk);
    }

    /**
     * 根据关联条件查询当前实体
     *
     * @param string $operator 比较操作符
     * @param int $count 个数
     * @param array | Closure $where （数组或者闭包）
     * @param string $exists whereExists| whereNotExists
     *
     * @return void
     */
    public function has($operator = '>=', $count = 1, $where = [], $exists = 'whereExists')
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        throw new BadMethodCallException(Lang::get('_ACTION_NOT_FOUND_', 'MorpthTo::' . ($trace[2]['function'] === 'whereHas' ? 'whereHas' : 'has')));
    }

    /**
     * 关联统计子查询
     *
     * @param Closure $closure 闭包
     * @param string $aggregate 聚合查询方法
     * @param string $aggregateField 字段
     *
     * @return string
     */
    public function getRelationAggregateSubSql(Closure $closure = null, $aggregate = 'count', $aggregateField = '*')
    {

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        throw new BadMethodCallException(Lang::get('_ACTION_NOT_FOUND_', 'MorpthTo::' . $trace[3]['function']));
    }

    /**
     * 解析实体的完整命名空间
     *
     * @param string $entity 实体名（或者完整类名）
     *
     * @return string
     */
    protected function parseEntity($entity)
    {
        if (isset($this->alias[$entity])) {
            $entity = $this->alias[$entity];
        }

        if (false === strpos($entity, '\\')) {
            $path = explode('\\', get_class($this->parent));
            array_pop($path);
            array_push($path, studlyCase($entity) . 'Entity');
            $entity = implode('\\', $path);
        }

        return $entity;
    }

    /**
     * 设置多态别名
     *
     * @param array $alias 别名定义
     *
     * @return static
     */
    public function setAlias(array $alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * 移除关联查询参数
     *
     * @return static
     */
    public function removeOption()
    {
        return $this;
    }

    /**
     * 预载入关联查询
     *
     * @param array $resultSet 数据集
     * @param string $relation 当前关联名
     * @param array $subRelation 子关联名
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure 闭包
     *
     * @return void
     */
    public function collectAssociatedPreload(array &$resultSet, $relation, array $subRelation, $subField = '*', Closure $closure = null)
    {
        $morphKey = $this->morphKey;
        $morphType = $this->morphType;
        $range = [];

        foreach ($resultSet as $result) {
            if ($result->$morphKey) {
                $range[$result->$morphType][] = $result->$morphKey;
            }
        }

        foreach ($range as $key => $val) {
            /**@var Entity $entity * */
            $entity = $this->parseEntity($key);
            $entity = new $entity;
            $pk = $entity->getPrimaryKey();

            $list = $entity->with($subRelation, $this->entity)->columns($subField)
                ->whereIn($pk, $val)->select(0, count($val));
            $data = [];
            foreach ($list as $item) {
                $data[$item->$pk] = $item;
            }

            foreach ($resultSet as $result) {
                if ($key == $result->$morphType) {
                    if (!isset($data[$result->$morphKey])) {
                        $relationEntity = null;
                    } else {
                        $relationEntity = $data[$result->$morphKey];
                    }
                    $result->setItem($relation, $relationEntity);
                }
            }
        }
    }

    /**
     * 保存（新增）当前关联数据对象
     *
     * @param Entity $item
     *
     * @return int
     */
    public function save(Entity $item)
    {
        throw new BadMethodCallException(Lang::get('_ACTION_NOT_FOUND_', static::class . '::save'));
    }

    /**
     * 保存（新增）当前关联数据对象
     *
     * @param array $item
     *
     * @return int
     */
    public function create(array $item)
    {
        throw new BadMethodCallException(Lang::get('_ACTION_NOT_FOUND_', static::class . '::save'));
    }
}
