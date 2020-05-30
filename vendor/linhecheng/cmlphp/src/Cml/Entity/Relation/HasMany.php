<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 一对多关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Collection;
use Cml\Entity\Entity;
use Exception;
use function Cml\getClassBasename;

/**
 * 一对多关联类
 */
class HasMany extends Base
{
    /**
     * 架构函数
     *
     * @param Entity $parent 上级实体对象
     * @param string $entity 实体名
     * @param string $foreignKey 关联外键
     * @param string $localKey 当前实体主键
     */
    public function __construct(Entity $parent, $entity, $foreignKey, $localKey)
    {
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->entity = new $entity;
    }

    /**
     * 延迟获取关联数据
     *
     * @return Collection
     */
    public function getRelation()
    {
        $this->execOnce();
        return $this->entity->select();
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
        $localKey = $this->localKey;
        $range = [];

        foreach ($resultSet as $result) {
            // 获取关联外键列表
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if ($range) {
            $data = $this->collectAssociatedExec([$this->foreignKey, $range, 'IN'], $subRelation, $subField, $closure);

            // 关联数据封装
            foreach ($resultSet as $result) {
                $pk = $result->$localKey;
                if (!isset($data[$pk])) {
                    $data[$pk] = [];
                }
                $result->setItem($relation, Collection::make($data[$pk]));
            }
        }
    }

    /**
     * 一对多 关联实体预查询
     *
     * @param array $where 关联预查询条件
     * @param array $subRelation 子关联
     * @param Closure $closure
     * @param mixed $subField 关联子表的字段
     * @return array
     */
    protected function collectAssociatedExec(array $where, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        $foreignKey = $this->foreignKey;

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
            $key = $set->$foreignKey;
            $data[$key][] = $set;
        }

        return $data;
    }

    /**
     * 根据关联条件查询当前实体
     *
     * @param string $operator 比较操作符
     * @param int $count 个数
     * @param array | Closure $where （数组或者闭包）
     * @param string $exists whereExists| whereNotExists
     *
     * @return Entity
     */
    public function has($operator = '>=', $count = 1, $where = [], $exists = 'whereExists')
    {
        $table = $this->entity->getTableName();
        $entity = getClassBasename($this->parent, true);
        $relation = getClassBasename($this->entity, true);


        $foreignKey = $this->foreignKey;
        $localKey = $this->localKey;


        return $this->parent->table([$this->parent->getTableName() => $entity])
            ->{$exists}(function () use ($table, $entity, $relation, $localKey, $foreignKey, $where, $operator, $count) {
                /**@var Entity | \Cml\Db\Base $inst * */
                $inst = (new $this->parent);
                $inst->table([$table => $relation])
                    ->setColumnsPrefix($relation);
                if (is_array($where)) {
                    $inst->where($where);
                } elseif ($where instanceof Closure) {
                    $where($inst);
                }
                $inst->setColumnsPrefix('')->columns($relation . '.' . $foreignKey)
                    ->whereRaw("`{$entity}`.`{$localKey}` =`{$relation}`.`{$foreignKey}`", [])
                    ->groupBy($relation . '.' . $this->foreignKey)
                    ->having('count(*)', $operator, $count);

                return $this->getSubSqlAndBindParams($inst);
            });

    }

    /**
     * 执行基础查询（仅执行一次）
     *
     * @return void
     */
    protected function execOnce()
    {
        if (!$this->execOnce) {
            if (isset($this->parent->{$this->localKey})) {
                $this->entity->where($this->foreignKey, $this->parent->{$this->localKey});
            }

            $this->execOnce = true;
        }
    }

    /**
     * 新增、保存多条关联数据
     *
     * @param array $items 数据数组
     *
     * @return array|false
     */
    public function saveMany($items)
    {
        foreach ($items as &$data) {
            $data = $data->toArray();
        }
        return $this->createMany($items);
    }


    /**
     * 新增、保存多条关联数据
     *
     * @param array $items 数据数组
     *
     * @return array|false
     */
    public function createMany($items)
    {
        $result = [];
        try {
            $this->startTransAction();
            foreach ($items as $data) {
                $result[] = $this->create($data);
            }
            return $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            $result = [];
        }
        return $result ? false : $result;
    }

}
