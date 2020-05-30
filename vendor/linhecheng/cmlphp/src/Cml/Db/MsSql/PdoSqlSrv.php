<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MsSql数据库 Pdo驱动类
 * *********************************************************** */

namespace Cml\Db\MsSql;

use Cml\Db\MySql\Pdo;
use Cml\Exception\PdoConnectException;
use PDOException;

/**
 * Orm MsSql数据库Pdo实现类
 *
 * @package Cml\Db\MsSql
 */
class PdoSqlSrv extends Pdo
{
    /**
     * Db连接
     *
     * @param string $host 数据库host
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $dbName 数据库名
     * @param string $charset 字符集
     * @param string $engine 引擎
     * @param bool $pConnect 是否为长连接
     *
     * @return mixed
     */
    public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false)
    {
        $link = '';
        $host = explode(':', $host);
        $dsn = "sqlsrv:server={$host[0] }" . (isset($host[1]) ? ",{$host[1]}" : '') . "; Database={$dbName}";

        $doConnect = function () use ($dsn, $pConnect, $charset, $username, $password) {
            return new \PDO($dsn, $username, $password, [
                \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        };

        try {
            $link = $doConnect();
        } catch (PDOException $e) {
            throw new PdoConnectException(
                'Pdo Connect Error! ｛' .
                $host[0] . (isset($host[1]) ? ':' . $host[1] : '') . ', ' . $dbName .
                '} Code:' . $e->getCode() . ', ErrorInfo!:' . $e->getMessage(),
                0,
                $e
            );
        }
        return $link;
    }

    /**
     * 构建sql
     *
     * @param null $offset 偏移量
     * @param null $limit 返回的条数
     * @param bool $isSelect 是否为select调用， 是则不重置查询参数并返回cacheKey/否则直接返回sql并重置查询参数
     *
     * @return string|array
     */
    public function buildSql($offset = null, $limit = null, $isSelect = false)
    {
        is_null($offset) || $this->limit($offset, $limit);

        $this->sql['columns'] == '' && ($this->sql['columns'] = '*');

        $columns = $this->sql['columns'];

        $tableAndCacheKey = $this->tableFactory();

        empty($this->sql['limit']) && ($this->sql['limit'] = "BETWEEN 1  AND 101");
$this->sql['orderBy'] || $this->sql['orderBy'] = 'ORDER BY rand()';
        $sql = <<<sql
SELECT
	RES.* 
FROM
	(SELECT
				cmlphp.*,
				ROW_NUMBER () OVER ( {$this->sql['orderBy']} ) AS ROW_NUMBER 
			 FROM	( SELECT {$columns} FROM {$tableAndCacheKey[0]} {$this->sql['where']} {$this->sql['groupBy']} {$this->sql['having']}) AS cmlphp 
	) AS RES WHERE ( RES.ROW_NUMBER {$this->sql['limit']} )
	
sql;
        if ($isSelect) {
            return [$sql, $tableAndCacheKey[1]];
        } else {
            $this->currentSql = $sql;
            $sql = $this->buildDebugSql();
            $this->reset();
            $this->clearBindParams();
            $this->currentSql = '';
            return " ({$sql}) ";
        }
    }

    /**
     * LIMIT
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     *
     * @return $this
     */
    public function limit($offset = 0, $limit = 10)
    {
        $offset = intval($offset);
        $limit = intval($limit);
        $offset < 0 && $offset = 0;
        $limit < 1 && $limit = 100;
        $offset++;
        $limit++;
        $this->sql['limit'] = "BETWEEN {$offset} AND {$limit}";
        return $this;
    }

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    public function getTables()
    {
        $this->currentQueryIsMaster = false;
        $stmt = $this->prepare("select name from sys.objects where type='U'", $this->rlink);
        $this->execute($stmt);

        $tables = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row['name'];
        }
        return $tables;
    }

    /**
     * 获取当前数据库中所有表的信息
     *
     * @return array
     */
    public function getAllTableStatus()
    {
        $this->currentQueryIsMaster = false;
        $stmt = $this->prepare('SELECT * FROM [INFORMATION_SCHEMA].[TABLES]', $this->rlink);
        $this->execute($stmt);
        $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $return = [];
        foreach ($res as $val) {
            $return[$val['TABLE_NAME']] = $val;
        }
        return $return;
    }

    protected function formatColumnKey($column)
    {
        $column = implode('.', array_map(function ($field) {
            $field = trim($field, '][');
            return "[{$field}]";
        }, explode('.', $column)));
        return $column;
    }
}
