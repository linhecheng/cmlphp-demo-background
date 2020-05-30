<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 多态一对一关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Entity;
use function Cml\getClassBasename;

/**
 * 多态一对一关联类
 */
class MorphOne extends Base
{
    use OneToOne;

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
     * 多态类型
     *
     * @var string
     */
    protected $type;

    /**
     * 构造函数
     *
     * @param Entity $parent 上级实体对象
     * @param string $entity 实体名
     * @param string $morphKey 关联外键
     * @param string $morphType 多态字段名
     * @param string $type 多态类型
     */
    public function __construct(Entity $parent, $entity, $morphKey, $morphType, $type)
    {
        $this->parent = $parent;
        $this->type = $type;
        $this->morphKey = $morphKey;
        $this->morphType = $morphType;
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

        return $this->parent->table([$this->parent->getTableName() => $entity])
            ->{$exists}(function () use ($table, $entity, $relation, $where) {
                /**@var \Cml\Db\Base $inst * */
                $inst = (new $this->parent);
                $inst->table([$table => $relation])
                    ->setColumnsPrefix($relation);
                if (is_array($where)) {
                    $inst->where($where);
                } elseif ($where instanceof Closure) {
                    $where($inst);
                }
                $inst->setColumnsPrefix('')->columns("`{$relation}`.`{$this->morphKey}`")
                    ->whereRaw("`{$entity}`.`{$this->entity->getPrimaryKey()}` =`{$relation}`.`{$this->morphKey}`", [])
                    ->where("`{$relation}`.`{$this->morphType}`", $this->type);

                return $this->getSubSqlAndBindParams($inst);
            });
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
        $morphType = $this->morphType;
        $morphKey = $this->morphKey;
        $type = $this->type;
        $range = [];
        $pk = $this->parent->getPrimaryKey();
        foreach ($resultSet as $result) {
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }

        if ($range) {
            $data = $this->preloadMorphToOne([
                [$morphKey, $range, 'IN'],
                [$morphType, $type, '='],
            ], $subRelation, $subField, $closure);

            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $relationEntity = $this->getDefault();
                } else {
                    $relationEntity = $data[$result->$pk];
                }
                $result->setItem($relation, $relationEntity);
            }
        }
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
        $morphType = $this->morphType;
        $morphKey = $this->morphKey;

        $this->entity->where($morphType, $this->type);
        return "`{$entityTableName}`.`{$this->parent->getPrimaryKey()}`=`{$morphKey}`";
    }

    /**
     * 多态一对一 关联实体预查询
     *
     * @param array $where 关联预查询条件
     * @param array $subRelation 子关联
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure 闭包
     *
     * @return array
     */
    protected function preloadMorphToOne(array $where, array $subRelation = [], $subField = '*', $closure = null)
    {
        // 预载入关联查询 支持嵌套预载入
        if ($closure) {
            $this->execOnce = true;
            $closure($this->entity);
        }
        foreach ($where as $condition) {
            $this->entity->conditionFactory($condition[0], $condition[1], $condition[2]);
        }
        $list = $this->entity
            ->columns($subField)
            ->select();
        $morphKey = $this->morphKey;

        // 组装实体数据
        $data = [];

        foreach ($list as $set) {
            $data[$set->$morphKey] = $set;
        }

        return $data;
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
        // 保存关联表数据
        $pk = $this->parent->getPrimaryKey();

        $item[$this->morphKey] = $this->parent->$pk;
        $item[$this->morphType] = $this->type;

        return $entity->replaceInto($item);
    }

    /**
     * 执行基础查询（进执行一次）
     *
     * @return void
     */
    protected function execOnce()
    {
        if (empty($this->execOnce) && $this->parent->toArray()) {
            $pk = $this->parent->getPrimaryKey();

            $this->entity
                ->where($this->morphKey, $this->parent->$pk)
                ->where($this->morphType, $this->type);
            $this->execOnce = true;
        }
    }

    protected function collectAssociatedWithIn(array &$resultSet, $relation, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        // TODO: Implement collectAssociatedWithIn() method.
    }
}
