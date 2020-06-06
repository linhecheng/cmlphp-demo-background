<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 暴露到Model、Entity中的方法
 * *********************************************************** */

namespace Cml\Db;

use Cml\Cml;
use Cml\Debug;
use Cml\Lang;
use Exception;
use InvalidArgumentException;
use Pdo;

/**
 * Trait Query
 *
 * @property Pdo $wlink
 * @property Pdo $rlink
 *
 * @package Cml\Db
 */
trait Query
{
    /**
     * where条件组装 相等
     *
     * @param string|array $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名) 当$column为数组时 批量设置
     * @param string |int $value 当$column为数组时  此时$value为false时条件为or 否则为and
     *
     * @return static
     */
    public function where($column, $value = '')
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->whereNeedAddAndOrOr > 0 && ($value === false ? $this->_or() : $this->_and());
                $this->conditionFactory($key, $val, '=');
            }
        } else {
            $this->conditionFactory($column, $value, '=');
        }
        return $this;
    }

    /**
     * where条件组装 两个列相等
     *
     * @notice 所有外部输入请使用 pdo预处理
     *
     * @param string $column eg：username | user.username
     * @param string $column2 eg: nickname | user.nickname
     *
     * @return static
     */
    public function whereColumn($column, $column2)
    {
        $this->conditionFactory($column, $column2, 'COLUMN');
        return $this;
    }

    /**
     * where条件原生条件
     *
     * @notice 所有外部输入请使用 pdo预处理，如下示例
     *
     * @param string $where eg：utime > ctime + ?
     * @param array $params eg: [10]
     *
     * @return static
     */
    public function whereRaw($where, $params)
    {
        $this->conditionFactory($where, $params, 'RAW');
        return $this;
    }

    /**
     * where条件组装 不等
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return static
     */
    public function whereNot($column, $value)
    {
        $this->conditionFactory($column, $value, '!=');
        return $this;
    }

    /**
     * where条件组装 大于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return static
     */
    public function whereGt($column, $value)
    {
        $this->conditionFactory($column, $value, '>');
        return $this;
    }

    /**
     * where条件组装 小于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return static
     */
    public function whereLt($column, $value)
    {
        $this->conditionFactory($column, $value, '<');
        return $this;
    }

    /**
     * where条件组装 大于等于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return static
     */
    public function whereGte($column, $value)
    {
        $this->conditionFactory($column, $value, '>=');
        return $this;
    }

    /**
     * where条件组装 小于等于
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return static
     */
    public function whereLte($column, $value)
    {
        $this->conditionFactory($column, $value, '<=');
        return $this;
    }

    /**
     * where条件组装 in
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array $value
     *
     * @return static
     */
    public function whereIn($column, $value)
    {
        $this->conditionFactory($column, $value, 'IN');
        return $this;
    }

    /**
     * where条件组装 not in
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array $value [1,2,3]
     *
     * @return static
     */
    public function whereNotIn($column, $value)
    {
        $this->conditionFactory($column, $value, 'NOT IN');
        return $this;
    }

    /**
     * where条件组装 REGEXP
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return static
     */
    public function whereRegExp($column, $value)
    {
        $this->conditionFactory($column, $value, 'REGEXP');
        return $this;
    }

    /**
     * where条件组装 LIKE
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param bool $leftBlur 是否开始左模糊匹配
     * @param string |int $value
     * @param bool $rightBlur 是否开始右模糊匹配
     *
     * @return static
     */
    public function whereLike($column, $leftBlur = false, $value = null, $rightBlur = false)
    {
        $this->conditionFactory(
            $column,
            ($leftBlur ? '%' : '') . $this->filterLike($value) . ($rightBlur ? '%' : ''),
            'LIKE'
        );
        return $this;
    }

    /**
     * where条件组装 LIKE
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param bool $leftBlur 是否开始左模糊匹配
     * @param string |int $value
     * @param bool $rightBlur 是否开始右模糊匹配
     *
     * @return static
     */
    public function whereNotLike($column, $leftBlur = false, $value = null, $rightBlur = false)
    {
        $this->conditionFactory(
            $column,
            ($leftBlur ? '%' : '') . $this->filterLike($value) . ($rightBlur ? '%' : ''),
            'NOT LIKE'
        );
        return $this;
    }

    /**
     * where条件组装 BETWEEN
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int | array $value
     * @param string |int | null $value2
     *
     * @return static
     */
    public function whereBetween($column, $value, $value2 = null)
    {
        if (is_null($value2)) {
            if (!is_array($value)) {
                throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_WHERE_BETWEEN_'));
            }
            $val = $value;
        } else {
            $val = [$value, $value2];
        }
        $this->conditionFactory($column, $val, 'BETWEEN');
        return $this;
    }

    /**
     * where条件组装 NOT BETWEEN
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int | array $value
     * @param string |int | null $value2
     *
     * @return static
     */
    public function whereNotBetween($column, $value, $value2 = null)
    {
        if (is_null($value2)) {
            if (!is_array($value)) {
                throw new InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_WHERE_BETWEEN_'));
            }
            $val = $value;
        } else {
            $val = [$value, $value2];
        }
        $this->conditionFactory($column, $val, 'NOT BETWEEN');
        return $this;
    }

    /**
     * where条件组装 IS NULL
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *
     * @return static
     */
    public function whereNull($column)
    {
        $this->conditionFactory($column, '', 'IS NULL');
        return $this;
    }

    /**
     * where条件组装 IS NOT NULL
     *
     * @param string $column 如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *
     * @return static
     */
    public function whereNotNull($column)
    {
        $this->conditionFactory($column, '', 'IS NOT NULL');
        return $this;
    }

    /**
     * 增加 and条件操作符
     *
     * @param callable $callable 如果传入函数则函数内执行的条件会被()包围
     *
     * @return static
     */
    public function _and(callable $callable = null)
    {
        $history = $this->whereNeedAddAndOrOr;
        $this->whereNeedAddAndOrOr = 1;

        if (is_callable($callable)) {
            $history === 0 && $this->whereNeedAddAndOrOr = 0;
            $this->lBrackets();
            call_user_func($callable, $this);
            $this->rBrackets();
        }

        return $this;
    }

    /**
     * 增加or条件操作符
     *
     * @param callable $callable 如果传入函数则函数内执行的条件会被()包围
     *
     * @return static
     */
    public function _or(callable $callable = null)
    {
        $history = $this->whereNeedAddAndOrOr;
        $this->whereNeedAddAndOrOr = 2;

        if (is_callable($callable)) {
            $history === 0 && $this->whereNeedAddAndOrOr = 0;
            $this->lBrackets();
            call_user_func($callable, $this);
            $this->rBrackets();
        }

        return $this;
    }

    /**
     * where条件增加左括号
     *
     * @return static
     */
    public function lBrackets()
    {
        if ($this->sql['where'] == '') {
            $this->sql['where'] = 'WHERE ';
        } else {
            if ($this->whereNeedAddAndOrOr === 1) {
                $this->sql['where'] .= ' AND ';
            } else if ($this->whereNeedAddAndOrOr === 2) {
                $this->sql['where'] .= ' OR ';
            }
        }
        $this->sql['where'] .= ' (';
        //移除下一次where操作默认加上AND
        $this->whereNeedAddAndOrOr = 0;
        return $this;
    }

    /**
     * where条件增加右括号
     *
     * @return static
     */
    public function rBrackets()
    {
        $this->sql['where'] .= ') ';
        return $this;
    }

    /**
     * 选择列
     *
     * @param string|array $columns 默认选取所有 ['id, 'name']
     * 选取id,name两列，['article.id' => 'aid', 'article.title' =>　'article_title'] 别名
     *
     * @return static
     */
    public function columns($columns = '*')
    {
        if (is_array($columns)) {
            $result = [];
            foreach ($columns as $key => $val) {
                if (is_int($key)) {
                    $result = array_merge($result, $this->parseStringColumn($this->addColumnPrefix($val)));
                } else {
                    $result[] = $this->formatColumnKey($this->addColumnPrefix($key)) . " AS " . $this->formatColumnKey($val);
                }
            }
        } else {
            $result = $this->parseStringColumn(func_get_args());
        }

        $this->sql['columns'] == '' || ($this->sql['columns'] .= ' ,');
        $this->sql['columns'] .= implode(', ', $result);
        return $this;
    }

    /**
     * @warn 不建议使用本方法
     * 直接使用columns--本方法用来查询子sql(请自行过滤子sql)
     *
     * @param string $column
     * @param array $bindParams
     *
     * @return $this;
     */
    public function addRawColumnPleaseUseCautiousIsMaybeUnsafe($column, $bindParams = [])
    {
        $this->sql['columns'] .= ($this->sql['columns'] ? ', ' : '') . $column;
        $bindParams && $this->bindParams = array_merge($this->bindParams, $bindParams);
        return $this;
    }

    /**
     * 解析字符串的字段  id, name,ctime 为数组
     *
     * @param mixed $column
     *
     * @return array
     */
    protected function parseStringColumn($column)
    {
        is_array($column) || $column = [$column];

        $column = array_map('trim', $column);
        foreach ($column as &$col) {
            $col = $this->formatColumnKey($this->addColumnPrefix($col));
        }
        return $column;
    }

    /**
     * 是否包含mysql函数
     *
     *  ->columns("DATE_FORMAT(from_unixtime(createtime),'%%H') as hour,SUM(value) AS nums,COUNT(DISTINCT identity) AS num")
     *
     * @param string $column
     *
     * @return bool
     */
    protected function haveMysqlFunction($column)
    {
        $return = stripos($column, '(') !== false && stripos($column, ')') !== false;
        $return || $return = preg_match('#(\s+)(CASE|WHEN|END|FROM)(\s+)#ims', $column);
        if ($return && preg_match('#(SELECT|UPDATE|DELETE/CREATE/INSERT|UNION|OUTFILE|INFILE)(\s+)#ims', $column, $match)) {
            throw new InvalidArgumentException('MySql Function Not Allow Use ' . $match[1]);
        }
        return $return;
    }

    /**
     * LIMIT
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     *
     * @return static
     */
    public function limit($offset = 0, $limit = 10)
    {
        $offset = intval($offset);
        $limit = intval($limit);
        $offset < 0 && $offset = 0;
        $limit < 1 && $limit = 100;
        $this->sql['limit'] = "LIMIT {$offset}, {$limit}";
        return $this;
    }

    /**
     * 强制使用索引
     *
     * @param string $table 要强制索引的表名(不带前缀)
     * @param string $index 要强制使用的索引
     * @param string $tablePrefix 表前缀 不传则获取配置中配置的前缀
     *
     * @return static
     */
    public function forceIndex($table, $index, $tablePrefix = null)
    {
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $this->forceIndex[$tablePrefix . $table] = $index;
        return $this;
    }


    /**
     * 排序
     *
     * @param string $column 要排序的字段
     * @param string $order 方向,默认为正序
     *
     * @return static
     */
    public function orderBy($column, $order = 'ASC')
    {
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            throw new InvalidArgumentException('orderBy order MUST BE ASC/DESC');
        }
        $column = $this->formatColumnKey($this->addColumnPrefix($column));
        if ($this->sql['orderBy'] == '') {
            $this->sql['orderBy'] = "ORDER BY {$column} {$order} ";
        } else {
            $this->sql['orderBy'] .= ", {$column} {$order} ";
        }
        return $this;
    }

    /**
     * 分组
     *
     * @param string $column 要设置分组的字段名
     *
     * @return static
     */
    public function groupBy($column)
    {
        $column = implode(', ', $this->parseStringColumn($column));

        if ($this->sql['groupBy'] == '') {
            $this->sql['groupBy'] = "GROUP BY {$column} ";
        } else {
            $this->sql['groupBy'] .= ",{$column} ";
        }
        return $this;
    }

    /**
     * having语句
     *
     * @param string $column 字段名
     * @param string $operator 操作符
     * @param string|array $value 值
     * @param string $logic 逻辑AND OR
     *
     * @return static
     */
    public function having($column, $operator = '=', $value = null, $logic = 'AND')
    {
        $column = $this->formatColumnKey($this->addColumnPrefix($column));
        $having = $this->sql['having'] == '' ? 'HAVING' : " {$logic} ";
        $this->sql['having'] .= "{$having} {$column} {$operator} ";
        if ($value || $value === 0) {
            if (is_array($value)) {//手动传%s
                $this->bindParams = array_merge($this->bindParams, $value);
            } else {
                $this->sql['having'] .= ' %s ';
                $this->bindParams[] = $value;
            }
        }
        return $this;
    }

    /**
     * union联结
     *
     * @param string|array $sql 要union的sql
     * @param bool $all 是否为union all
     *
     * @return static
     */
    public function union($sql, $all = false)
    {
        if (is_array($sql)) {
            foreach ($sql as $s) {
                $this->union .= $all ? ' UNION ALL ' : ' UNION ';
                $this->union .= $this->filterUnionSql($s);
            }
        } else {
            $this->union .= $all ? ' UNION ALL ' : ' UNION ';
            $this->union .= $this->filterUnionSql($sql) . ' ';
        }
        return $this;
    }

    /**
     * 执行
     * @param callable $query
     *
     * @return bool
     *
     * @throws Exception
     */
    public function transaction(callable $query)
    {
        try {
            $this->startTransAction();
            $query();
            return $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * orm参数是否自动重置, 默认在执行语句后会重置orm参数,包含查询的表、字段信息、条件等信息
     *
     * @param bool $autoReset 是否自动重置 查询的表、字段信息、条件等信息
     * @param bool $alwaysClearTable 用来控制在$paramsAutoReset = false 的时候是否清除查询的table信息.避免快捷方法重复调用table();
     * @param bool $alwaysClearColumns 用来控制在$paramsAutoReset = false 的时候是否清除查询的字段信息.主要用于按批获取数据不用多次调用columns();
     *
     * @return static
     */
    public function paramsAutoReset($autoReset = true, $alwaysClearTable = false, $alwaysClearColumns = true)
    {
        $this->paramsAutoReset = $autoReset;
        $this->alwaysClearTable = $alwaysClearTable;
        $this->alwaysClearColumns = $alwaysClearColumns;
        return $this;
    }

    /**
     * 标记本次查询不使用缓存
     *
     * @return static
     */
    public function noCache()
    {
        $this->currentQueryUseCache = false;
        return $this;
    }

    /**
     * 获取数据列值列表
     *
     * @param string $column 列名
     * @param null $key 返回数组中为列值指定自定义键（该自定义键必须是该表的其它字段列名）
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return array
     */
    public function pluck($column, $key = null, $limit = null, $useMaster = false)
    {
        $this->sql['columns'] == '' && $this->columns(is_null($key) ? $column : [$key, $column]);
        $result = $this->select(0, $limit, $useMaster);
        $return = [];
        foreach ($result as $row) {
            is_null($key) ? $return[] = $row[$column] : $return[$row[$key]] = $row[$column];
        }
        return $return;
    }

    /**
     * @deprecated
     * 请使用pluck方法
     */
    public function plunk()
    {
        return $this->pluck(...func_get_args());
    }

    /**
     * 组块结果集-此方法前调用paramsAutoReset无效
     *
     * @param int $num 每次获取的条数
     * @param callable $func 结果集处理函数。本回调函数内调用paramsAutoReset无效
     */
    public function chunk($num = 100, callable $func = null)
    {
        $this->paramsAutoReset();
        $start = 0;
        $backComdition = $this->sql;//sql组装
        $backTable = $this->table;//操作的表
        $backJoin = $this->join;//是否内联
        $backleftJoin = $this->leftJoin;//是否左联结
        $backrightJoin = $this->rightJoin;//是否右联
        $backBindParams = $this->bindParams;

        while ($result = $this->select($start, $num)) {
            if ($func($result) === false) {
                break;
            }
            $start += count($result);

            $this->sql = $backComdition;//sql组装
            $this->table = $backTable;//操作的表
            $this->join = $backJoin;//是否内联
            $this->leftJoin = $backleftJoin;//是否左联结
            $this->rightJoin = $backrightJoin;//是否右联
            $this->bindParams = $backBindParams;
        }
        $this->paramsAutoReset();
        $this->reset();
        $this->clearBindParams();
    }

    /**
     * 获取一列
     *
     * @param string $column 列名
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return bool|mixed
     */
    public function getOneValue($column, $useMaster = false)
    {
        $this->sql['columns'] == '' && $this->columns($column);
        $data = $this->getOne($useMaster);
        return isset($data[$column]) ? $data[$column] : false;
    }

    /**
     * 数据是否存在
     *
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function exists($useMaster = false)
    {
        return $this->count('*', false, $useMaster) > 0;
    }

    /**
     * 数据是否不存在
     *
     * @param bool|string $useMaster 是否使用主库 默认读取从库
     *
     * @return mixed
     */
    public function doesntExist($useMaster = false)
    {
        return $this->count('*', false, $useMaster) == 0;
    }

    /**
     * 开启事务
     *
     * @return bool
     */
    public function startTransAction()
    {
        Cml::$debug && Debug::addSqlInfo('beginTransaction');
        return $this->wlink->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        Cml::$debug && Debug::addSqlInfo('commit');
        return $this->wlink->commit();
    }

    /**
     * 设置一个事务保存点
     *
     * @param string $pointName
     *
     * @return bool
     */
    public function savePoint($pointName)
    {
        Cml::$debug && Debug::addSqlInfo("SAVEPOINT {$pointName}");
        return $this->wlink->exec("SAVEPOINT {$pointName}");
    }

    /**
     * 回滚事务
     *
     * @param bool $rollBackTo 是否为还原到某个保存点
     *
     * @return bool
     */
    public function rollBack($rollBackTo = false)
    {
        if ($rollBackTo === false) {
            Cml::$debug && Debug::addSqlInfo('ROLLBACK');
            return $this->wlink->rollBack();
        } else {
            Cml::$debug && Debug::addSqlInfo("ROLLBACK TO {$rollBackTo}");
            return $this->wlink->exec("ROLLBACK TO {$rollBackTo}");
        }
    }
}
