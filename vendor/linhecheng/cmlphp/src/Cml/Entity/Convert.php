<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 数组转换为集合
 * *********************************************************** */

namespace Cml\Entity;

/**
 * 数组转换为集合
 * Class Convert
 * @package Cml\Entity
 */
class Convert
{
    /**
     * 查询结果转换为数据对象
     *
     * @param array $res 查询结果
     * @param Entity $entity
     *
     * @return Collection
     */
    public static function convertResToCollection(array $res, $entity)
    {

        $options = $entity->getOption();

        foreach ($res as $key => &$item) {
            $item = $entity::make($item)->itemIsExists(true, true);
        }
        unset($item);

        if ($options['with']) {
            $entity->collectAssociatedPreload($res, $options['with'], false);
        }

        if ($options['with_join']) {
            $entity->collectAssociatedPreload($res, $options['with_join'], true);
        }

        return Collection::make($res);
    }
}
