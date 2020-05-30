<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 BelongsTo关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Entity;
use function Cml\getClassBasename;

/**
 * BelongsTo关联类
 */
class BelongsTo extends Base
{
    use OneToOne;

    /**
     * @param Entity | \Cml\Db\Base $parent 上级实体对象
     * @param string $entity 实体名
     * @param string $foreignKey 关联外键
     * @param string $localKey 关联主键
     */
    public function __construct(Entity $parent, $entity, $foreignKey, $localKey)
    {
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->entity = new $entity;
    }

    /**
     * 设置外键
     *
     * @param mixed $refId id、实体
     *
     * @return $this
     */
    public function associate($refId)
    {
        if ($refId instanceof Entity) {
            $refId = $refId->{$refId->getPrimaryKey()};
        }
        $this->parent->{$this->foreignKey} = $refId;
        return $this;
    }

    /**
     * 取消外键
     *
     * @return $this
     */
    public function dissociate()
    {
        $this->parent->{$this->foreignKey} = null;
        return $this;
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
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        return $this->parent->table([$this->parent->getTableName() => $entity])
            ->{$exists}(function () use ($table, $entity, $relation, $localKey, $foreignKey, $where) {
                /**@var Entity | \Cml\Db\Base $inst * */
                $inst = (new $this->parent);
                $inst = $inst->table([$table => $relation])
                    ->setColumnsPrefix($relation);

                if (is_array($where)) {
                    $inst->where($where);
                } elseif ($where instanceof Closure) {
                    $where($inst);
                }

                $inst->setColumnsPrefix('')->columns($relation . '.' . $localKey)
                    ->whereRaw("{$entity}.{$foreignKey} ={$relation}.{$localKey}", []);

                return $this->getSubSqlAndBindParams($inst);
            });
    }

    /**
     * 覆盖聚合操作where条件
     *
     * @param string $aggregateEntityName
     * @param string $entityTableName
     *
     * @return string
     */
    protected function overrideAggregateSubSqlWhere($aggregateEntityName, $entityTableName)
    {
        return "`{$entityTableName}`.`{$this->foreignKey}`=`{$this->localKey}`";
    }

    /**
     * 预载入关联查询（数据集）
     *
     * @param array $resultSet 数据集
     * @param string $relation 当前关联名
     * @param array $subRelation 子关联名
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure 闭包
     *
     * @return void
     */
    protected function collectAssociatedWithIn(array &$resultSet, $relation, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        $range = [];
        foreach ($resultSet as $result) {
            // 获取关联外键列表
            if (isset($result->$foreignKey)) {
                $result->$foreignKey && $range[] = $result->$foreignKey;
            }
        }

        if ($range) {
            $data = $this->collectAssociatedExec([$localKey, $range, 'IN'], $localKey, $subRelation, $subField, $closure);

            // 关联数据封装
            foreach ($resultSet as $result) {
                // 关联实体
                if (!isset($data[$result->$foreignKey])) {
                    $relationEntity = $this->getDefault();
                } else {
                    $relationEntity = $data[$result->$foreignKey];
                }
                $result->setItem($relation, $relationEntity);
            }
        }
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
                $this->entity->where($this->localKey, $this->parent->{$this->foreignKey});
            }

            $this->execOnce = true;
        }
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
        /** @var $entity Entity* */
        $entity = new $this->entity;
        return $entity->replaceInto($item);
    }
}
