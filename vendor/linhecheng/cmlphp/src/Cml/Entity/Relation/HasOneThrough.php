<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 远程一对一关联类
 * *********************************************************** */

namespace Cml\Entity\Relation;

use Closure;
use Cml\Entity\Entity;

/**
 * 远程一对一关联类
 */
class HasOneThrough extends HasManyThrough
{
    use OneToOne;

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
                // 关联实体
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
        $throughKeys = $this->through->conditionFactory($where[0], $where[1], $where[2])->pluck($this->throughPk, $this->foreignKey, count($where[1]));

        if ($closure) {
            $closure($this->entity);
        }

        $list = $this->entity->columns($subField)
            ->whereIn($this->throughKey, $throughKeys)
            ->select(0, count($throughKeys));

        $data = [];
        $throughKeys = array_flip($throughKeys);

        foreach ($list as $set) {
            $data[$throughKeys[$set->{$this->throughKey}]] = $set;
        }

        return $data;
    }

    protected function collectAssociatedWithIn(array &$resultSet, $relation, array $subRelation = [], $subField = '*', Closure $closure = null)
    {
        // TODO: Implement collectAssociatedWithIn() method.
    }
}
