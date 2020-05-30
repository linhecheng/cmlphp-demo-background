<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 多对多关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Collection;
use Cml\Entity\Entity;
use Cml\Entity\Pivot;
use Cml\Lang;
use Exception;
use InvalidArgumentException;
use function Cml\getClassBasename;

/**
 * 多对多关联类
 */
class BelongsToMany extends Base
{
    /**
     * 中间表表名
     *
     * @var string
     */
    protected $middle;

    /**
     * 中间表实体名称
     *
     * @var string
     */
    protected $pivotName;

    /**
     * 中间表实体对象
     *
     * @var Pivot
     */
    protected $pivot;

    /**
     * 中间表数据名称
     *
     * @var string
     */
    protected $pivotDataName = 'pivot';

    /**
     * 架构函数
     *
     * @param Entity | \Cml\Db\Base $parent 上级实体对象
     * @param string $entity 实体名
     * @param string $middle 中间表/实体名
     * @param string $foreignKey 关联实体外键
     * @param string $localKey 当前实体关联键
     */
    public function __construct(Entity $parent, $entity, $middle, $foreignKey, $localKey)
    {
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        if (false !== strpos($middle, '\\')) {
            $this->pivotName = $middle;
            $this->middle = getClassBasename($middle);
        } else {
            $this->middle = $middle;
        }

        $this->entity = new $entity;
        $this->pivot = $this->newPivot();
    }

    /**
     * 定义中间表实体
     *
     * @param Entity $pivotName
     *
     * @return static
     */
    public function using($pivotName)
    {
        $this->pivotName = $pivotName;
        return $this;
    }

    /**
     * 自定义 pivot 属性名称
     *
     * @param string $pivotDataName
     *
     * @return static
     */
    public function as($pivotDataName)
    {
        $this->pivotDataName = $pivotDataName;
        return $this;
    }

    /**
     * 实例化中间表实体
     *
     * @param array $data
     *
     * @return Pivot
     */
    protected function newPivot(array $data = [])
    {
        $class = $this->pivotName ?: Pivot::class;
        $pivot = new $class($data, $this->parent, $this->middle);

        if ($pivot instanceof Pivot) {
            return $pivot;
        } else {
            throw new InvalidArgumentException(Lang::get('_PARAM_ERROR_', ['pivot Entity', '\Cml\Entity\Pivot']));
        }
    }

    /**
     * 合成中间表实体
     *
     * @param Collection $collection
     */
    protected function hydratePivot($collection)
    {
        foreach ($collection as $entity) {
            $pivot = [];

            foreach ($entity->toArray() as $key => $val) {
                if (strpos($key, '___')) {
                    [$name, $attr] = explode('___', $key, 2);

                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($entity->$key);
                    }
                }
            }
            $entity->setItem($this->pivotDataName, $this->newPivot($pivot));
        }
    }

    /**
     * 创建关联查询Query对象
     *
     * @return Entity
     */
    protected function buildQuery()
    {
        $foreignKey = $this->foreignKey;
        $localKey = $this->localKey;

        // 关联查询
        $pk = $this->parent->getPrimaryKey();

        return $this->belongsToManyQuery($foreignKey, ['pivot.' . $localKey, $this->parent->$pk, '=']);
    }

    /**
     * 延迟获取关联数据
     *
     * @return Collection
     */
    public function getRelation()
    {
        $result = $this->buildQuery()
            ->select();
        $this->hydratePivot($result);

        return $result;
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
        $pivotTable = $this->pivot->getTableName();
        $pk = $this->entity->getPrimaryKey();
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        return $this->parent->table([$this->parent->getTableName() => $entity])
            ->{$exists}(function () use ($table, $entity, $relation, $localKey, $foreignKey, $pivotTable, $pk, $operator, $count, $where) {
                /**@var Entity | \Cml\Db\Base $inst * */
                $inst = (new $this->parent);
                $inst = $inst->table([$table => $relation])
                    ->setColumnsPrefix($relation);

                if (is_array($where)) {
                    $inst->where($where);
                } elseif ($where instanceof Closure) {
                    $where($inst);
                }

                $inst->setColumnsPrefix('')->join([$pivotTable => $pivotTable], "`{$pivotTable}`.`$foreignKey`=`{$relation}`.`{$pk}`")
                    ->columns("`{$pivotTable}`.`{$localKey}`")
                    ->whereRaw("`{$entity}`.`{$pk}`=`{$pivotTable}`.`{$localKey}`", [])
                    ->groupBy("`{$pivotTable}` . `{$localKey}`")
                    ->having('count(*)', $operator, $count);

                return $this->getSubSqlAndBindParams($inst);
            });
    }

    /**
     * 设置中间表的查询条件
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return static
     */
    public function wherePivot($column, $value = null, $operator = '=')
    {
        $this->entity->conditionFactory('pivot.' . $column, $value, $operator);
        return $this;
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
    public function collectAssociatedPreload(array &$resultSet, $relation, array $subRelation, $subField = '*', Closure $closure = null)
    {
        $localKey = $this->localKey;
        $pk = $resultSet[0]->getPrimaryKey();
        $range = [];

        foreach ($resultSet as $result) {
            // 获取关联外键列表
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }

        if ($range) {
            $data = $this->collectAssociatedExec(['pivot.' . $localKey, $range, 'IN'], $subRelation, $subField, $closure);

            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $data[$result->$pk] = [];
                }
                $result->setItem($relation, Collection::make($data[$result->$pk]));
            }
        }
    }

    /**
     * 关联统计子查询
     *
     * @param Closure $closure 闭包
     * @param string $aggregate 聚合查询方法
     * @param string $aggregateField 字段
     *
     * @return array
     */
    public function getRelationAggregateSubSql(Closure $closure = null, $aggregate = 'count', $aggregateField = '*')
    {
        $aggregateTableName = $this->entity->getTableName();
        $aggregateField == '*' || $aggregateField = "`{$aggregateTableName}`.`{$aggregateField}`";

        $inst = $this->belongsToManyQuery($this->foreignKey, [
            "`pivot`.`{$this->localKey}`", '`' . $this->parent->getTableName(true) . '`.`' . $this->parent->getPrimaryKey() . '`', 'column'
        ], false)
            ->when(($closure instanceof Closure), function () use ($closure, $aggregateTableName) {
                $this->entity->setColumnsPrefix($aggregateTableName);
                $closure($this->entity);
                $this->entity->setColumnsPrefix('');
            })
            ->columns("{$aggregate}($aggregateField)");
        return $this->getSubSqlAndBindParams($inst);
    }

    /**
     * 多对多 关联实体预查询
     *
     * @param array $where 关联预查询条件
     * @param array $subRelation 子关联
     * @param mixed $subField 关联子表的字段
     * @param Closure $closure 闭包
     *
     * @return array
     */
    protected function collectAssociatedExec(array $where, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        if ($closure) {
            $this->entity->setColumnsPrefix($this->entity->getTableName());
            $closure($this->entity);
            $this->entity->setColumnsPrefix('');
        }

        // 预载入关联查询 支持嵌套预载入
        $list = $this->belongsToManyQuery($this->foreignKey, $where, $subField)
            ->with($subRelation, $this->entity)
            ->select();

        // 组装实体数据
        $data = [];
        foreach ($list as $set) {
            $pivot = [];
            foreach ($set->toArray() as $key => $val) {
                if (strpos($key, '___')) {
                    [$name, $attr] = explode('___', $key, 2);
                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($set->$key);
                    }
                }
            }

            $key = $pivot[$this->localKey];

            $set->setItem($this->pivotDataName, $this->newPivot($pivot));

            $data[$key][] = $set;
        }

        return $data;
    }

    /**
     * BELONGS TO MANY 关联查询
     *
     * @param string $foreignKey 关联实体关联键
     * @param array $condition 关联查询条件
     * @param bool|string $needAddColumns 是否自动添加字段
     *
     * @return Entity
     */
    protected function belongsToManyQuery($foreignKey, array $condition = [], $needAddColumns = '*')
    {

        $tableName = $this->entity->getTableName();
        $table = $this->pivot->getTableName();

        if ($needAddColumns) {
            $columns = array_keys($this->entity->getDbFields($table));
            array_walk($columns, function (&$col) {
                $col = "`pivot`.`$col` AS `pivot___{$col}`";
            });

            $this->entity->setColumnsPrefix($tableName)->columns($needAddColumns)->setColumnsPrefix('');
            $this->entity->columns($columns);
        }

        if (empty($this->execOnce)) {
            $relationFk = $this->entity->getPrimaryKey();
            $this->entity->table([$tableName => $tableName])
                ->join([$table => 'pivot'], 'pivot.' . $foreignKey . '=' . $tableName . '.' . $relationFk)
                ->conditionFactory($condition[0], $condition[1], $condition[2]);
        }

        return $this->entity;
    }

    /**
     * 保存多条数据
     *
     * @param array|Collection $items 数据集
     * @param array $pivot 中间表额外数据
     * @param bool $samePivot 额外数据是否相同
     *
     * @return array|false
     */
    public function saveMany($items, array $pivot = [], $samePivot = false)
    {
        $result = [];

        foreach ($items as $key => $data) {
            if (!$samePivot) {
                $pivotData = $pivot[$key] ?? [];
            } else {
                $pivotData = $pivot;
            }

            $result[] = $this->attach($data, $pivotData);
        }

        return empty($result) ? false : $result;
    }

    /**
     * 添加关联中间表数据
     *
     * @param mixed $id id或实体或数组 eg:
     * ->attach(1);
     * ->attach(1, ['ext_field' => 1]);
     * ->attach([1 => ['ext_field' => 1]), 2 => ['ext_field' => 2])]);
     * @param array $pivot 中间表额外字段值。配合Id用
     *
     * @return array|Pivot
     */
    public function attach($id, array $pivot = [])
    {
        $result = $pivotData = [];
        if ($id instanceof Entity) {
            $id = $id->{$id->getPrimaryKey()};
        }

        $ids = is_array($id) ? $id : [$id => $pivot];

        foreach ($ids as $id => $pivotData) {
            if (!is_array($pivotData)) {
                $id = $pivotData;
                $pivotData = [];
            }
            $pivotData[$this->localKey] = $this->parent->{$this->parent->getPrimaryKey()};
            $pivotData[$this->foreignKey] = $id;
            $inst = $this->pivot::make($pivotData);
            $inst->save([], false);
            $result[] = $this->newPivot($pivotData);
        }

        if (count($result) === 1) {
            $result = $result[0];
        }

        return $result;
    }

    /**
     * 判断是否存在关联数据
     *
     * @param mixed $id id、实体
     *
     * @return Pivot|false
     */
    public function attached($id)
    {
        if ($id instanceof Entity) {
            $id = $id->{$id->getPrimaryKey()};
        }

        $pivot = $this->pivot
            ->where($this->localKey, $this->parent->{$this->parent->getPrimaryKey()})
            ->where($this->foreignKey, $id)
            ->getOne();

        return $pivot ?: false;
    }

    /**
     * 移除关联中间表数据
     *
     * @param int|array $id id、实体、数组
     * @param bool $syncDelete 是否同时删除关联表数据
     *
     * @return int
     */
    public function detach($id = null, $syncDelete = false)
    {
        if ($id instanceof Entity) {
            $id = $id->{$id->getPrimaryKey()};
        }

        is_null($id) || $id = (array)$id;
        $result = $this->pivot->where($this->localKey, $this->parent->{$this->parent->getPrimaryKey()})
            ->when($id, function ($entity) use ($id) {
                $entity->whereIn($this->foreignKey, $id);
            })
            ->delete();

        // 删除关联表数据
        if ($id && $syncDelete) {
            $entity = $this->entity;
            $entity::destroy($id);
        }

        return $result;
    }

    /**
     * 同步关联关系：中间表记录中，所有未在 ID 数组中的记录都将会被移除
     *
     * @param mixed $id 中间表数组
     *
     * @return array
     */
    public function sync(array $id)
    {
        $attached = $this->pivot
            ->where($this->localKey, $this->parent->{$this->parent->getPrimaryKey()})
            ->pluck($this->foreignKey);

        try {
            $realId = [];
            foreach ($id as $key => $val) {
                $realId[] = is_array($val) ? $key : $val;
            }

            $this->pivot->startTransAction();
            $this->pivot->where($this->localKey, $this->parent->{$this->parent->getPrimaryKey()})
                ->whereNotIn($this->foreignKey, $realId)
                ->delete();

            $ids = [];
            foreach ($id as $key => $val) {
                in_array(is_array($val) ? $key : $val, $attached) || $ids[$key] = $val;
            }
            $res = $ids ? $this->attach($ids) : true;

            $this->pivot->commit();
        } catch (Exception $e) {
            $this->pivot->rollBack();
            throw  $e;
        }

        return $res;
    }
}
