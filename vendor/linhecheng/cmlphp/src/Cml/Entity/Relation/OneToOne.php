<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 一对一关联trait
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Entity;
use InvalidArgumentException;
use function Cml\getClassBasename;
use function Cml\humpToLine;

/**
 * 一对一关联trait
 *
 * @mixin Base
 * @package Cml\Entity\Relation
 */
trait OneToOne
{
    protected $withDefault = null;

    /**
     * 延迟获取关联数据
     *s
     * @return Entity
     */
    public function getRelation()
    {
        $this->execOnce();
        return $this->entity->getOne() ?: $this->getDefault();
    }

    /**
     * 预载入关联查询（JOIN方式）
     *
     * @param Entity $entity 查询对象
     * @param string $relation 关联名
     * @param mixed $subField 关联子表的字段
     * @param string $joinType JOIN方式
     * @param Closure $closure 闭包条件
     * @param bool $addMainTable 是否添加主表
     *
     * @return void
     */
    public function associatedPreloadWithJoin(Entity $entity, $relation, $subField = '*', $joinType = '', Closure $closure = null, $addMainTable = false)
    {
        $name = humpToLine(lcfirst(getClassBasename($this->parent)));

        if ($addMainTable) {
            $table = $entity->getTableName();

            /**@var \Cml\Db\Base $entity * */
            $entity->table([$table => $name]);
            $columns = $entity->getColumnsAndClear();
            $entity->setColumnsPrefix($name)->columns($columns ? $columns : "*")->setColumnsPrefix('');
        }

        // 预载入封装
        $joinTable = $this->entity->getTableName();
        $joinAlias = $relation;

        $joinType = strtolower($joinType);
        if (!in_array($joinType, ['inner', 'left', 'right'])) {
            throw new InvalidArgumentException("joinType must in ['INNER', 'LEFT', 'RIGHT']");
        }
        $joinType = $joinType === 'inner' ? 'join' : $joinType . 'Join';

        $columns = $subField === '*' ? array_keys($this->entity->getDbFields($joinTable)) : array_map('trim', explode(',', $subField));
        array_walk($columns, function (&$col) use ($joinAlias) {
            $col = "`{$joinAlias}`.`$col` AS `{$joinAlias}___{$col}`";
        });
        $entity->columns(implode(', ', $columns));

        if ($this instanceof BelongsTo) {
            $joinOn = $name . '.' . $this->foreignKey . '=' . $joinAlias . '.' . $this->localKey;
        } else {
            $joinOn = $name . '.' . $this->localKey . '=' . $joinAlias . '.' . $this->foreignKey;
        }

        if ($closure) {
            $entity->setColumnsPrefix($joinAlias);
            $closure($entity);
            $entity->setColumnsPrefix('');
        }

        $entity->{$joinType}([$joinTable => $joinAlias], $joinOn);
    }

    /**
     *  预载入关联查询（数据集）
     *
     * @param array $resultSet
     * @param string $relation
     * @param array $subRelation
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure
     *
     * @return mixed
     */
    abstract protected function collectAssociatedWithIn(array &$resultSet, $relation, array $subRelation = [], $subField = '*', Closure $closure = null);

    /**
     * 预载入关联查询（数据集）
     *
     * @param array $resultSet 数据集
     * @param string $relation 当前关联名
     * @param array $subRelation 子关联名
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure 闭包
     * @param bool $join 是否为JOIN方式
     *
     * @return void
     */
    public function collectAssociatedPreload(array &$resultSet, $relation, array $subRelation = [], $subField = '*', Closure $closure = null, $join = false)
    {
        if ($join) {
            foreach ($resultSet as $result) {
                $this->transFormJoinRelationField($this->entity, $relation, $result);
            }
        } else {
            $this->collectAssociatedWithIn($resultSet, $relation, $subRelation, $subField, $closure);
        }
    }

    /**
     * 一对一 关联实体预查询拼装
     *
     * @param string $relationEntityName 实体名称
     * @param string $relation 关联名
     * @param Entity $entity 实体对象实例
     *
     * @return void
     */
    protected function transFormJoinRelationField($relationEntityName, $relation, $entity)
    {
        $relationData = [];
        foreach ($entity->toArray() as $key => $val) {
            if (strpos($key, '___')) {
                [$name, $attr] = explode('___', $key, 2);
                if ($name == $relation) {
                    $relationData[$name][$attr] = $val;
                    unset($entity->$key);
                }
            }
        }

        if (isset($relationData[$relation]) && null !== current($relationData[$relation])) {
            $relationEntity = new $relationEntityName($relationData[$relation]);
        } else {
            $relationEntity = $this->getDefault();
        }
        $entity->{$relation} = $relationEntity;
    }

    /**
     * 一对一 关联实体预查询（IN方式）
     *
     * @param array $where 关联预查询条件
     * @param string $key 关联键名
     * @param array $subRelation 子关联
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure
     *
     * @return array
     */
    protected function collectAssociatedExec(array $where, $key, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        if ($closure) {
            $closure($this->entity);
        }

        $list = $this->entity
            ->columns($subField)
            ->conditionFactory($where[0], $where[1], $where[2])
            ->with($subRelation, $this->entity)
            ->select();
        // 组装实体数据
        $data = [];
        foreach ($list as $set) {
            if (!isset($data[$set->$key])) {
                $data[$set->$key] = $set;
            }
        }
        return $data;
    }

    /**
     * 给定关系为 null 时，将会返回默认实体
     *
     * @param array|Closure $item
     *
     * @return $this;
     */
    public function withDefault($item = [])
    {
        $this->withDefault = $item;
        return $this;
    }

    /**
     * 获取默认对象
     *
     * @return \Cml\Db\Base|Entity|null
     */
    protected function getDefault()
    {
        if (is_null($this->withDefault)) {
            return null;
        } else {
            $entity = $this->entity->make(is_callable($this->withDefault) ? [] : $this->withDefault);
            if (is_callable($this->withDefault)) {
                call_user_func_array($this->withDefault, [$entity, $this->parent]);
            }
            return $entity;
        }
    }
}
