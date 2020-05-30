<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 实体关联基础类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Entity;
use Cml\Interfaces\Db;
use function Cml\getClassBasename;

/**
 * 实体关联基础类
 *
 * @mixin Entity
 */
abstract class Base
{
    /**
     * 上级实体对象
     *
     * @var Entity | \Cml\Db\Base
     */
    protected $parent;

    /**
     * 实体名
     *
     * @var Entity | \Cml\Db\Base
     */
    protected $entity;

    /**
     * 关联表外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * 关联表主键
     *
     * @var string
     */
    protected $localKey;

    /**
     * 是否执行关联基础查询
     *
     * @var bool
     */
    protected $execOnce;

    /**
     * 获取实体属性
     *
     * @return \Cml\Db\Base|Entity
     */
    public function getEntity()
    {
        return $this->entity;
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
        $aggregateEntityName = getClassBasename($this->entity, true);
        if ($closure instanceof Closure) {
            $closure($this->entity);
        }
        $entityTableName = $this->parent->getTableName(true);

        $where = method_exists($this, 'overrideAggregateSubSqlWhere') ? $this->overrideAggregateSubSqlWhere($aggregateEntityName, $entityTableName) : "`{$this->foreignKey}`=`{$entityTableName}`.`{$this->localKey}`";

        $inst = $this->entity->table($this->entity->getTableName())
            ->whereRaw($where, [])
            ->columns("{$aggregate}({$aggregateField})");
        return $this->getSubSqlAndBindParams($inst);
    }

    /**
     * 获取子查询的语句与绑定参数
     *
     * @param Entity|\Cml\Db\Base $inst
     *
     * @return array
     */
    protected function getSubSqlAndBindParams(&$inst)
    {
        $subSqlBindParams = $inst->getBindParams();
        $subSql = $inst->buildSql(null, null, true)[0];
        $inst->resetAndClear();
        return [
            $subSql,
            $subSqlBindParams
        ];
    }

    /**
     * 关联基础查询
     *
     * @return void
     */
    protected function execOnce()
    {
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
        return $this->create($item->toArray());
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
        $item[$this->foreignKey] = $this->parent->{$this->localKey};

        return $entity->replaceInto($item);
    }

    /**
     * 当访问model中不存在的方法时直接调用$this->db()的相关方法
     *
     * @param $dbMethod
     * @param $arguments
     *
     * @return Entity | $this
     */
    public function __call($dbMethod, $arguments)
    {
        $this->execOnce();
        $res = call_user_func_array([$this->entity, $dbMethod], $arguments);

        if ($res instanceof Entity && $res == $this->entity) {
            return $this;//不是返回数据直接返回当前实例
        } else {
            return $res;
        }
    }
}
