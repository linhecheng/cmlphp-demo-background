<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 远程一对多关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use BadMethodCallException;
use Closure;
use Cml\Entity\Collection;
use Cml\Entity\Entity;
use Cml\Lang;
use function Cml\getClassBasename;

/**
 * 远程一对多关联类
 */
class HasManyThrough extends Base
{
    /**
     * 中间表外键
     *
     * @var string
     */
    protected $throughKey;

    /**
     * 中间主键
     *
     * @var string
     */
    protected $throughPk;

    /**
     * 中间表查询对象
     *
     * @var Entity|\Cml\Db\Base
     */
    protected $through;

    /**
     * 架构函数
     *
     * @param Entity $parent 上级实体对象
     * @param string $entity 关联实体名
     * @param string $through 中间实体名
     * @param string $foreignKey 关联外键
     * @param string $throughKey 中间关联外键
     * @param string $localKey 当前实体主键
     * @param string $throughPk 中间实体主键
     */
    public function __construct(Entity $parent, $entity, $through, $foreignKey, $throughKey, $localKey, $throughPk)
    {
        $this->parent = $parent;
        $this->through = new $through;
        $this->foreignKey = $foreignKey;
        $this->throughKey = $throughKey;
        $this->localKey = $localKey;
        $this->throughPk = $throughPk;
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
        $entity = getClassBasename($this->parent, true);
        $throughTable = $this->through->getTableName();
        $throughKey = $this->throughKey;
        /**@var Entity $relation * */
        $relation = new $this->entity;
        $relationTable = $relation->getTableName();

        return $this->parent->table([$this->parent->getTableName() => $entity])
            ->{$exists}(function () use ($throughTable, $relationTable, $entity, $relation, $throughKey, $where, $operator, $count) {
                /**@var \Cml\Db\Base $inst * */
                $inst = (new $this->parent);
                $inst->table([$throughTable => $throughTable])
                    ->setColumnsPrefix($relationTable);
                if (is_array($where)) {
                    $inst->where($where);
                } elseif ($where instanceof Closure) {
                    $where($inst);
                }

                $inst->setColumnsPrefix('')->columns("`{$throughTable}`.`{$this->foreignKey}`")
                    ->whereRaw("`{$throughTable}`.`{$this->foreignKey}`=`{$entity}`.`{$this->localKey}`", [])
                    ->join([$relationTable => $relationTable], "`{$relationTable}`.`{$throughKey}`=`{$throughTable}`.`{$this->throughPk}`")
                    ->groupBy("`{$throughTable}`.`{$this->foreignKey}`")
                    ->having('count(*)', $operator, $count);

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
    public function collectAssociatedPreload(array &$resultSet, $relation, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        $range = [];
        foreach ($resultSet as $result) {
            // 获取关联外键列表
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if ($range) {
            $data = $this->collectAssociatedExec([$this->foreignKey, $range, 'IN'], $foreignKey, $subRelation, $subField, $closure);
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
     * 关联实体预查询
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
            $this->execOnce = true;
            $closure($this->entity, $this->through);
        }

        $throughList = $this->through->conditionFactory($where[0], $where[1], $where[2])->select();
        $keys = $throughList->column($this->throughPk, $this->throughPk);

        $list = $this->entity
            ->columns($subField)
            ->whereIn($this->throughKey, $keys)
            ->select();

        // 组装实体数据
        $data = [];
        $keys = $throughList->column($this->foreignKey, $this->throughPk);

        foreach ($list as $set) {
            $key = $keys[$set->{$this->throughKey}];
            $data[$key][] = $set;
        }

        return $data;
    }

    /**
     * 关联统计子查询
     *
     * @param Closure $closure 闭包
     * @param string $aggregate 聚合查询方法
     * @param string $aggregateField 字段
     * @param string $name 统计字段别名
     *
     * @return array
     */
    public function getRelationAggregateSubSql(Closure $closure = null, $aggregate = 'count', $aggregateField = '*', &$name = null)
    {
        $this->execOnce(true);
        $aggregateTableName = getClassBasename($this->entity, true);
        $throughTable = $this->through->getTableName();
        $entityTableName = $this->parent->getTableName(true);

        $aggregateField == '*' || $aggregateField = "`{$aggregateTableName}`.`{$aggregateField}`";
        $inst = $this->entity->columns("{$aggregate}({$aggregateField})")
            ->whereRaw("`{$throughTable}`.`{$this->foreignKey}`=`$entityTableName`.`{$this->localKey}`", []);

        return $this->getSubSqlAndBindParams($inst);
    }

    /**
     * 执行基础查询（仅执行一次）
     *
     * @param bool $aggregate 是否为关联聚合
     *
     * @return void
     */
    protected function execOnce($aggregate = false)
    {
        if (!$this->execOnce) {
            $relateEntityName = getClassBasename($this->entity, true);
            $throughTable = $this->through->getTableName();
            $pk = $this->throughPk;
            $throughKey = $this->throughKey;

            $this->entity->table([$this->entity->getTableName() => $relateEntityName])
                ->join([$throughTable => $throughTable], "`{$throughTable}`.`{$pk}`=`{$relateEntityName}`.`{$throughKey}`");
            if (!$aggregate) {
                $this->entity->columns("`{$relateEntityName}`.*")
                    ->where("`{$throughTable}`.`{$this->foreignKey}`", $this->parent->{$this->localKey});
            }

            $this->execOnce = true;
        }
    }
}
