<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 一对一 关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Entity;
use function Cml\getClassBasename;

/**
 * 一对一关联类
 */
class HasOne extends Base
{
    use OneToOne;

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
                /**@var \Cml\Db\Base $inst * */
                $inst = (new $this->parent);
                $inst->table([$table => $relation])
                    ->setColumnsPrefix($relation);
                if (is_array($where)) {
                    $inst->where($where);
                } elseif ($where instanceof Closure) {
                    $where($inst);
                }
                $inst->setColumnsPrefix('')->columns($relation . '.' . $foreignKey)
                    ->whereRaw("`{$entity}`.`{$localKey}` =`{$relation}`.`{$foreignKey}`", []);

                return $this->getSubSqlAndBindParams($inst);
            });
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
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if ($range) {
            $data = $this->collectAssociatedExec([$foreignKey, $range, 'IN'], $foreignKey, $subRelation, $subField, $closure);

            foreach ($resultSet as $result) {
                if (!isset($data[$result->$localKey])) {
                    $relationEntity = $this->getDefault();
                } else {
                    $relationEntity = $data[$result->$localKey];
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
                $this->entity->where($this->foreignKey, $this->parent->{$this->localKey});
            }

            $this->execOnce = true;
        }
    }
}
