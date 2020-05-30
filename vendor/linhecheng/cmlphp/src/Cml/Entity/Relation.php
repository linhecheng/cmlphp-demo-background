<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 关联实体相关方法
 * *********************************************************** */

namespace Cml\Entity;

use Cml\Entity\Relation\BelongsTo;
use Cml\Entity\Relation\BelongsToMany;
use Cml\Entity\Relation\HasMany;
use Cml\Entity\Relation\HasManyThrough;
use Cml\Entity\Relation\HasOneThrough;
use Cml\Entity\Relation\MorphMany;
use Cml\Entity\Relation\MorphOne;
use Cml\Entity\Relation\MorphTo;
use Cml\Entity\Relation\HasOne;
use Cml\Entity\Relation\OneToOne;
use Cml\Interfaces\Db;
use InvalidArgumentException;
use function Cml\getClassBasename;
use function Cml\humpToLine;
use function Cml\studlyCase;
use \Closure;

/**
 * 实体关联处理
 *
 * @mixin Entity
 */
trait Relation
{

    /**
     * 选项
     *
     * @var array
     */
    private $options = [
        'with' => false,
        'with_join' => false
    ];

    /**
     * 关联写信息
     *
     * @var array
     */
    private $writeWith = [];


    /**
     * 获取选项
     *
     * @param string $opt
     *
     * @return mixed
     */
    public function getOption($opt = null)
    {
        if (!$opt) return $this->options;
        return isset($this->options[$opt]) ? $this->options[$opt] : false;
    }

    /**
     * 设置选项
     *
     * @param string $opt
     * @param mixed $value
     *
     * @return static;
     */
    public function setOption($opt, $value)
    {
        $this->options[$opt] = $value;
        return $this;
    }

    /**
     * 关联数据写
     *
     * @param array $writeWithRelation 关联
     *
     * @return static
     */
    public function writeWith($writeWithRelation)
    {
        $writeWithRelation = (array)$writeWithRelation;
        foreach ($writeWithRelation as $relation) {
            if (isset($this->relateData[$relation])) {
                $this->writeWith[$relation] = $this->relateData[$relation];
            } elseif (isset($this->items[$relation])) {
                $this->writeWith[$relation] = $this->items[$relation];
                unset($this->items[$relation]);
            }
        }

        return $this;
    }

    /**
     * 查询已存在的关联
     *
     * @param string $relation 关联方法名
     * @param mixed $operator 比较操作符
     * @param int $count 个数
     * @param array | Closure $where （数组或者闭包）
     *
     * @return Entity
     */
    public static function has($relation, $operator = '>=', $count = 1, $where = [])
    {
        return (new static())
            ->$relation()
            ->has($operator, $count, $where);
    }

    /**
     * 带上条件查询已存在的关联（本方法执行的where会自动带上关联实体的表前缀）
     *
     * @param string $relation 关联方法名
     * @param array|Closure $where （数组或者闭包） 查询条件 eg:
     * UsersEntity::whereHas('logs',function(Entity $query) {
     *     $query->whereGt('ctime', Cml::$nowTime - 86400); //生成的where语句 `LoginLogEntity`.`ctime` > '1577354243'
     * })->select(0, 2);
     *  UsersEntity::whereHas('logs',[
     *     'ctime' => '1575203774' //生成where语句  `LoginLogEntity`.`ctime` = '1575203774'
     * ])->select(0, 2);
     * @param mixed $operator 比较操作符
     * @param int $count 个数
     *
     * @return Entity
     */
    public static function whereHas($relation, $where = null, $operator = '>=', $count = 1)
    {
        return self::has($relation, $operator, $count, $where);
    }

    /**
     * 查询不存在的关联
     *
     * @param string $relation 关联方法名
     * @param mixed $operator 比较操作符
     * @param int $count 个数
     * @param array | Closure $where （数组或者闭包）
     *
     * @return Entity
     */
    public static function doesntHave($relation, $operator = '>=', $count = 1, $where = [])
    {
        return (new static())
            ->$relation()
            ->has($operator, $count, $where, 'whereNotExists');
    }

    /**
     * 带上条件查询不存在的关联（本方法执行的where会自动带上关联实体的表前缀）
     *
     * @param string $relation 关联方法名
     * @param array|Closure $where （数组或者闭包） 查询条件 eg:
     * UsersEntity::whereHas('logs',function(Entity $query) {
     *     $query->whereGt('ctime', Cml::$nowTime - 86400); //生成的where语句 `LoginLogEntity`.`ctime` > '1577354243'
     * })->select(0, 2);
     *  UsersEntity::whereHas('logs',[
     *     'ctime' => '1575203774' //生成where语句  `LoginLogEntity`.`ctime` = '1575203774'
     * ])->select(0, 2);
     * @param mixed $operator 比较操作符
     * @param int $count 个数
     *
     * @return Entity
     */
    public static function whereDoesntHave($relation, $where = null, $operator = '>=', $count = 1)
    {
        return self::doesntHave($relation, $operator, $count, $where);
    }

    /**
     * 预载入关联查询 JOIN方式
     *
     * @param Entity $entity
     * @param string $relation 关联方法名
     * @param mixed $subField 关联子表的字段
     * @param string $joinType JOIN类型
     * @param Closure $closure 闭包
     * @param bool $addMainTable 是否添加主表
     *
     * @return bool
     */
    public function associatedPreloadWithJoin($entity, $relation, $subField = '*', $joinType = '', Closure $closure = null, $addMainTable = false)
    {
        $relation = studlyCase($relation, false);
        $class = $this->$relation();

        if ($class instanceof OneToOne) {
            $class->associatedPreloadWithJoin($entity, $relation, $subField, $joinType, $closure, $addMainTable);
            return true;
        } else {
            throw new InvalidArgumentException('withJoin must be [HasOne、BelongsTo]');
        }
    }

    /**
     * 预载入关联查询 返回数据集
     *
     * @param array $resultSet 数据集
     * @param array $relations 关联名
     * @param bool $join 是否为JOIN方式
     *
     * @return void
     */
    public function collectAssociatedPreload(array &$resultSet, array $relations, $join = false)
    {
        foreach ($relations as $key => $relation) {
            $subRelation = [];
            $closure = null;
            $subField = '*';

            if ($relation instanceof Closure) {
                $closure = $relation;
                $relation = $key;
            } else if (is_array($relation)) {
                $subRelation = $relation;
                $relation = $key;
            } elseif (strpos($relation, '.')) {
                [$relation, $subRelation] = explode('.', $relation, 2);
                $subRelation = [$subRelation];
            } elseif (is_string($key) && is_string($relation)) {
                $subField = $relation;
                $relation = $key;
            }

            $relationName = $relation;
            $relation = studlyCase($relation, false);
            $relationResult = $this->$relation();

            /** @var OneToOne $relationResult * */
            $relationResult->collectAssociatedPreload($resultSet, $relationName, $subRelation, $subField, $closure, $join);
        }
    }

    /**
     * HAS ONE 关联定义
     *
     * @param string $entity 实体名
     * @param string $foreignKey 关联外键
     * @param string $localKey 当前主键
     *
     * @return HasOne
     */
    public function hasOne($entity, $foreignKey = '', $localKey = '')
    {
        // 记录当前关联信息
        $entity = $this->parseEntity($entity);
        $localKey = $localKey ?: $this->getPrimaryKey();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->getTableName());

        return new HasOne($this, $entity, $foreignKey, $localKey);
    }

    /**
     * BELONGS TO 关联定义
     *
     * @param string $entity 实体名
     * @param string $foreignKey 关联外键
     * @param string $localKey 关联主键
     *
     * @return BelongsTo
     */
    public function belongsTo($entity, $foreignKey = '', $localKey = '')
    {
        /**@var Entity $entity */
        $entity = $this->parseEntity($entity);
        $foreignKey = $foreignKey ?: $this->getForeignKey($entity::getInstance()->getTableName());
        $localKey = $localKey ?: $entity::getInstance()->getPrimaryKey();
        return new BelongsTo($this, $entity, $foreignKey, $localKey);
    }

    /**
     * HAS MANY 关联定义
     *
     * @param string $entity 实体名
     * @param string $foreignKey 关联外键
     * @param string $localKey 当前主键
     *
     * @return HasMany
     */
    public function hasMany($entity, $foreignKey = '', $localKey = '')
    {
        // 记录当前关联信息
        $entity = $this->parseEntity($entity);
        $localKey = $localKey ?: $this->getPrimaryKey();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->getTableName());

        return new HasMany($this, $entity, $foreignKey, $localKey);
    }

    /**
     * HAS MANY 远程关联定义
     *
     * @param string $entity 实体名
     * @param string $through 中间实体名
     * @param string $foreignKey 外键
     * @param string $throughKey 中间表外键
     * @param string $localKey 当前主键
     * @param string $throughPk 中间表主键
     *
     * @return HasManyThrough
     */
    public function hasManyThrough($entity, $through, $foreignKey = '', $throughKey = '', $localKey = '', $throughPk = '')
    {
        /**@var Entity $entity */
        $entity = $this->parseEntity($entity);
        /**@var Entity $through */
        $through = $this->parseEntity($through);
        $localKey = $localKey ?: $this->getPrimaryKey();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->getTableName());
        $throughKey = $throughKey ?: $this->getForeignKey($through::getInstance()->getTableName());
        $throughPk = $throughPk ?: $through::getInstance()->getPrimaryKey();

        return new HasManyThrough($this, $entity, $through, $foreignKey, $throughKey, $localKey, $throughPk);
    }

    /**
     * HAS ONE 远程关联定义
     *
     * @param string $entity 实体名
     * @param string $through 中间实体名
     * @param string $foreignKey 关联外键
     * @param string $throughKey 关联外键
     * @param string $localKey 当前主键
     * @param string $throughPk 中间表主键
     *
     * @return HasOneThrough
     */
    public function hasOneThrough($entity, $through, $foreignKey = '', $throughKey = '', $localKey = '', $throughPk = '')
    {
        /**@var Entity $entity */
        $entity = $this->parseEntity($entity);
        /**@var Entity $through */
        $through = $this->parseEntity($through);
        $localKey = $localKey ?: $this->getPrimaryKey();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->getTableName());
        $throughKey = $throughKey ?: $this->getForeignKey($through::getInstance()->getTableName());
        $throughPk = $throughPk ?: $through::getInstance()->getPrimaryKey();

        return new HasOneThrough($this, $entity, $through, $foreignKey, $throughKey, $localKey, $throughPk);
    }

    /**
     * BELONGS TO MANY 关联定义
     *
     * @param string $entity 实体名
     * @param string $middle 中间表/实体名
     * @param string $foreignKey 关联外键
     * @param string $localKey 当前实体关联键
     *
     * @return BelongsToMany
     */
    public function belongsToMany($entity, $middle = '', $foreignKey = '', $localKey = '')
    {
        // 记录当前关联信息
        $entity = $this->parseEntity($entity);
        $name = humpToLine(getClassBasename($entity));
        $middle = $middle ?: getClassBasename($this, true) . '_' . $name;
        $foreignKey = $foreignKey ?: $name . '_id';
        $localKey = $localKey ?: $this->getForeignKey($this->getTableName());

        return new BelongsToMany($this, $entity, $middle, $foreignKey, $localKey);
    }

    /**
     * MORPH  One 关联定义
     *
     * @param string $entity 实体名
     * @param string|array $morph 多态字段信息
     * @param string $type 多态类型
     *
     * @return MorphOne
     */
    public function morphOne($entity, $morph = null, $type = '')
    {
        if (is_array($morph)) {
            [$morphType, $foreignKey] = $morph;
        } else {
            $morph || $morph = 'ref';
            $morphType = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        $type = $type ?: str_replace('_entity', '', getClassBasename($this, true));

        return new MorphOne($this, $this->parseEntity($entity), $foreignKey, $morphType, $type);
    }

    /**
     * MORPH  MANY 关联定义
     *
     * @param string $entity 实体名
     * @param string|array $morph 多态字段信息
     * @param string $type 当前实体对应的多态类型
     *
     * @return MorphMany
     */
    public function morphMany($entity, $morph = null, $type = '')
    {
        $entity = $this->parseEntity($entity);

        if (is_array($morph)) {
            [$morphType, $foreignKey] = $morph;
        } else {
            $morph || $morph = 'ref';
            $morphType = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        $type = $type ?: str_replace('_entity', '', getClassBasename($this, true));
        return new MorphMany($this, $entity, $foreignKey, $morphType, $type);
    }

    /**
     * MORPH TO 关联定义
     *
     * @param string|array $morph 多态字段信息
     * @param array $alias 多态别名定义 如： ['user' => UserChangeEntity::class]   表里存的关联类型是user,如果不定义别名关联的是UserEntity::class
     *
     * @return MorphTo
     */
    public function morphTo($morph = null, array $alias = [])
    {
        // 记录当前关联信息
        if (is_array($morph)) {
            [$morphType, $foreignKey] = $morph;
        } else {
            $morph || $morph = 'ref';
            $morphType = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        return new MorphTo($this, $morphType, $foreignKey, $alias);
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
        if (false === strpos($entity, '\\')) {
            $path = explode('\\', static::class);
            $entity = array_pop($path);
            array_push($path, studlyCase($entity, false));
            $entity = implode('\\', $path);
        }

        return $entity;
    }

    /**
     * 获取实体的默认外键名
     *
     * @param string $name 实体名
     *
     * @return string
     */
    public function getForeignKey(string $name)
    {
        if (strpos($name, '\\')) {
            $name = getClassBasename($name);
        }
        return humpToLine($name) . '_id';
    }

    /**
     * 关联新增、写入 OneToOne
     *
     * @return void
     */
    protected function writeWithSave()
    {
        foreach ($this->writeWith as $rel => $val) {
            if ($val instanceof Entity) {
                $val = $val->toArray();
            }
            /**@var Entity $relate * */
            $relate = $this->$rel();
            $relate->save($val);
        }
    }

    /**
     * 关联删除 OneToOne、OneToMany
     *
     * @return void
     */
    protected function writeWithDelete()
    {
        foreach ($this->writeWith as $name => $val) {
            if ($val instanceof Entity) {
                $pk = $val->getPrimaryKey();
                $val->where($pk, $val->{$pk})->delete();
            } elseif ($val instanceof Collection) {
                $val->isEmpty() || $pk = $val[0]->getPrimaryKey();
                foreach ($val as $entity) {
                    $entity->where($pk, $entity->{$pk})->delete();
                }
            }
        }
    }

    /**
     * 关联预载入 In方式
     *
     * @param array|string $with 关联方法名，如:'prifile'、['prifile', 'logs]、['profile' => 'id , title']只取profile的id,title字段、
     * ['profile'=> function(Entity $entity) {$entity->whereGt('id', 1);}]子条件
     * ['logs' => ['detail', 'detail2']] 嵌套查询
     * ['logs.detail'] 嵌套查询
     * @param Entity $entity 以实例方法调用时传入对象 可用来配置要查询的字段
     *
     * @return static
     */
    public static function with($with, Entity $entity = null)
    {
        $entity || $entity = new static();
        return $entity->setOption('with', (array)$with);
    }

    /**
     * 关联预载入 JOIN方式-只适用于HasOne和BelongsTo
     *
     * @param array|string $with 关联方法名，如:'prifile'、['profile'=>'id , title']只取profile的id,title字段、['profile'=> function(Entity $entity) {$entity->whereGt('id', 1);}]子条件
     * @param Entity $entity 以实例方法调用时传入对象  可用来配置要查询的字段
     * @param string $joinType JOIN方式
     *
     * @return static
     */
    public static function withJoin($with, $entity = null, $joinType = 'inner')
    {
        $entity || $entity = new static();

        $with = (array)$with;
        $addMainTable = true;

        foreach ($with as $key => $relation) {
            $closure = null;
            $subField = '*';

            if ($relation instanceof Closure) {
                $closure = $relation;
                $relation = $key;
            } elseif (is_string($relation)) {
                $subField = $relation;
                $relation = $key;
            }

            $entity->associatedPreloadWithJoin($entity, $relation, $subField, $joinType, $closure, $addMainTable);
            $addMainTable = false;
        }
        $entity->setOption('with_join', $with);
        return $entity;
    }

    /**
     * 关联统计
     *
     * @param array|string $relations 关联方法名
     * @param string $aggregate 聚合查询方法
     * @param string $aggregateField 字段
     * @param mixed $columns 要查询的主表字段 *
     *
     * @return static
     */
    protected static function withAggregate($relations, $aggregate = 'count', $aggregateField = '*', $columns = '*')
    {
        $entity = new static();
        $columns && $entity->columns($columns);
        $tableName = $entity->getTableName();
        /**@var Db $entity * */
        $entity->table($tableName);
        return $entity->relationAggregate($entity, (array)$relations, $aggregate, $aggregateField);
    }

    /**
     * 关联统计
     *
     * @param string|array $relation 关联方法名
     * @param string $aggregateField 聚合的字段名
     * @param mixed $columns 要查询的主表字段 *
     *
     * @return static
     */
    public static function withCount($relation, $aggregateField = '*', $columns = '*')
    {
        return static::withAggregate($relation, 'count', $aggregateField, $columns);
    }

    /**
     * 关联统计Sum
     *
     * @param string|array $relation 关联方法名
     * @param string $aggregateField 聚合的字段名
     * @param mixed $columns 要查询的主表字段 *
     *
     * @return static
     */
    public static function withSum($relation, $aggregateField, $columns = '*')
    {
        return static::withAggregate($relation, 'sum', $aggregateField, $columns);
    }

    /**
     * 关联统计Max
     *
     * @param string|array $relation 关联方法名
     * @param string $aggregateField 聚合的字段名
     * @param mixed $columns 要查询的主表字段 *
     *
     * @return static
     */
    public static function withMax($relation, $aggregateField, $columns = '*')
    {
        return static::withAggregate($relation, 'max', $aggregateField, $columns);
    }

    /**
     * 关联统计Min
     *
     * @param string|array $relation 关联方法名
     * @param string $aggregateField 聚合的字段名
     * @param mixed $columns 要查询的主表字段 *
     *
     * @return static
     */
    public static function withMin($relation, $aggregateField, $columns = '*')
    {
        return static::withAggregate($relation, 'min', $aggregateField, $columns);
    }

    /**
     * 关联统计Avg
     *
     * @param string|array $relation 关联方法名
     * @param string $aggregateField 聚合的字段名
     * @param mixed $columns 要查询的主表字段 *
     *
     * @return static
     */
    public static function withAvg($relation, $aggregateField, $columns = '*')
    {
        return static::withAggregate($relation, 'avg', $aggregateField, $columns);
    }

    /**
     * 关联聚合
     *
     * @param Entity $entity 实体
     * @param array $relations 关联名
     * @param string $aggregate 聚合查询方法
     * @param string $aggregateField 字段
     *
     * @return Entity
     */
    public function relationAggregate(Entity $entity, array $relations, $aggregate = 'sum', $aggregateField = '*')
    {
        foreach ($relations as $key => $relation) {
            $closure = $name = null;
            if ($relation instanceof Closure) {
                $closure = $relation;
                $relation = $key;
            } elseif (is_string($key)) {
                $name = $relation;
                $relation = $key;
            }

            $relation = studlyCase($relation, false);

            $count = $this->$relation()->getRelationAggregateSubSql($closure, $aggregate, $aggregateField);

            if (empty($name)) {
                $name = humpToLine($relation) . '_' . $aggregate;
            }

            $entity->addRawColumnPleaseUseCautiousIsMaybeUnsafe("({$count[0]}) AS {$name}", $count[1]);
        }
        return $entity;
    }
}
