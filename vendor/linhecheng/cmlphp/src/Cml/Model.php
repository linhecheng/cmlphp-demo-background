<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统默认Model
 * *********************************************************** */

namespace Cml;

use Cml\Db\Query;
use Cml\Interfaces\Db;
use Cml\Model\Base;
use Cml\Model\QuickMethod;

/**
 * Model
 *
 * @method Db|Model table(string |array $table = '', string | null $tablePrefix = null) 定义操作的表
 * @method Db|Model join(string | array $table, string $on, string | null $tablePrefix = null) join内联结
 * @method Db|Model leftJoin(string | array $table, string $on, string | null $tablePrefix = null) leftJoin左联结
 * @method Db|Model rightJoin(string | array $table, string $on, string | null $tablePrefix = null) rightJoin右联结
 * @method Db|Model noCache() 标记本次查询不使用缓存
 * @method array select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false) 获取多条数据
 * @method array paginate($limit, $useMaster = false, $page = null, $fieldAsKey = false) 分页获取数据
 * @method array|false getOne($useMaster = false) 获取一条数据
 * @method int insertId() 获取上一INSERT的主键值
 * @method int update($key, $data = null, $and = true, $tablePrefix = null)
 *
 * @mixin Query
 * @package Cml
 */
class Model
{
    use Base;
    use QuickMethod;
}
