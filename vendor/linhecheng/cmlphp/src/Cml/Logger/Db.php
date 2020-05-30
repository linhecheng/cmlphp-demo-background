<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 18-11-30 下午1:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Log Db驱动实现
 * *********************************************************** */

/**
 * 数据表--表名/前缀可自行更改
 * CREATE TABLE `pr_cmlphp_log` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
 * `level` enum('debug','info','notice','warning','error','critical','alert','emergency') NOT NULL DEFAULT 'debug' COMMENT '日志等级',
 * `message` text,
 * `context` longtext COMMENT '上下文',
 * `ctime` int(11) unsigned DEFAULT '0' COMMENT '写入日期',
 * `ip` char(15) NOT NULL DEFAULT '',
 * PRIMARY KEY (`id`),
 * KEY `skey` (`level`,`ctime`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='cmlphp db日志驱动数据表';
 *
 * 相关配置:
 * db_log_use_db 配置数据表所在的db标识 默认default_db
 * db_log_use_table 配置数据表除前缀的表名 默认 cmlphp_log
 * db_log_use_tableprefix 配置数据表前缀 默认取db中配置的前缀
 */

namespace Cml\Logger;

use Cml\Config;
use Cml\Http\Request;
use Cml\Model;

/**
 *  Log Db驱动实现
 *
 * @package Cml\Logger
 */
class Db extends Base
{
    /**
     * 任意等级的日志记录
     *
     * @param mixed $level 日志等级
     * @param string $message 要记录到log的信息
     * @param array $context 上下文信息
     *
     * @return bool
     */
    public function log($level, $message, array $context = [])
    {
        $db = Config::get('db_log_use_db', 'default_db');
        $table = Config::get('db_log_use_table', 'cmlphp_log');
        $tablePrefix = Config::get('db_log_use_tableprefix', null);

        $context['cmlphp_log_src'] = Request::isCli() ? 'cli' : 'web';

        if ($level === self::EMERGENCY) {//致命错误记文件一份，防止db挂掉什么信息都没有
            $file = new File();
            $file->log($level, $message, $context);
        }
        return Model::getInstance($table, $tablePrefix, $db)->setCacheExpire(false)->set([
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'ctime' => time()
        ]);
    }
}