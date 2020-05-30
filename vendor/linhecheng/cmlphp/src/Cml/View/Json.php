<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图 Json渲染引擎
 * *********************************************************** */

namespace Cml\View;

use Cml\Cml;
use Cml\Config;
use Cml\Debug;
use function Cml\dump;
use function Cml\dumpUsePHPConsole;

/**
 * 视图 Json渲染引擎
 *
 * @package Cml\View
 */
class Json extends Base
{
    /**
     * 获取json输出
     *
     * @return string
     */
    public function fetch()
    {
        $this->setHeader('Content-Type', 'application/json;charset=' . Config::get('default_charset'));

        if (Cml::$debug) {
            $sql = Debug::getSqls();
            if (Config::get('dump_use_php_console')) {
                dumpUsePHPConsole([
                    'sql' => $sql,
                    'tipInfo' => Debug::getTipInfo()
                ], strip_tags($_SERVER['REQUEST_URI']));
            }
            $this->args['sql'] = $sql;
        } else {
            $deBugLogData = dump('', 1);
            if (!empty($deBugLogData)) {
                Config::get('dump_use_php_console') ? dumpUsePHPConsole($deBugLogData, 'debug') : $this->args['cml_debug_info'] = $deBugLogData;
            }
        }
        return json_encode($this->args, JSON_UNESCAPED_UNICODE) ?: json_last_error_msg();
    }

    /**
     * 输出数据
     *
     */
    public function display()
    {
        $json = $this->fetch();
        $this->sendHeader();
        exit($json);
    }
}
