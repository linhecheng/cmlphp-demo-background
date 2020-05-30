<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 13-8-9 下午2:22
 * *********************************************************** */
namespace Cml;

use Cml\Interfaces\Cache;
use Cml\Interfaces\Db;

/**
 * Session保存位置处理类。封装了Session入库/Cache的逻辑处理
 * 采用Mysql存储时需要建数据表,语句如下:
 * CREATE TABLE `cml_session` (
 * `id` char(32) NOT NULL,
 * `value` varchar(5000) NOT NULL,
 * `ctime` int(11) unsigned NOT NULL,
 * PRIMARY KEY(`id`)
 * )ENGINE=MEMORY DEFAULT CHARSET=utf8;
 *
 * @package Cml
 */
class Session
{
    /**
     * session超时时间
     *
     * @var int $lifeTime
     */
    private $lifeTime;

    /**
     * @var Db |Cache $handler
     */
    private $handler;

    /**
     * 初始化
     *
     */
    public static function init()
    {
        $cmlSession = new Session();
        $cmlSession->lifeTime = ini_get('session.gc_maxlifetime');
        if (Config::get('session_user_loc') == 'db') {
            $cmlSession->handler = Model::getInstance()->db();
        } else {
            $cmlSession->handler = Model::getInstance()->cache();
        }
        ini_set('session.save_handler', 'user');
        session_module_name('user');
        session_set_save_handler(
            [$cmlSession, 'open'], //运行session_start()时执行
            [$cmlSession, 'close'], //在脚本执行结束或调用session_write_close(),或session_destroy()时被执行，即在所有session操作完后被执行
            [$cmlSession, 'read'], //在执行session_start()时执行，因为在session_start时会去read当前session数据
            [$cmlSession, 'write'], //此方法在脚本结束和session_write_close强制提交SESSION数据时执行
            [$cmlSession, 'destroy'], //在执行session_destroy()时执行
            [$cmlSession, 'gc'] //执行概率由session.gc_probability和session.gc_divisor的值决定，时机是在open,read之后，session_start会相继执行open,read和gc
        );
        ini_get('session.auto_start') || session_start(); //自动开启session,必须在session_set_save_handler后面执行
    }

    /**
     * session open
     *
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * session close
     *
     * @return bool
     */
    public function close()
    {
        if (Config::get('session_user_loc') == 'db') {
            $this->handler->wlink = null;
        }
        //$GLOBALS['debug'] && \Cml\Debug::stopAndShowDebugInfo(); 开启ob_start()的时候 php此时已经不能使用压缩，所以这边输出的数据是没压缩的，而之前已经告诉浏览器数据是压缩的，所以会导致火狐、ie不能正常解压
        //$this->gc($this->lifeTime);
        return true;
    }

    /**
     * session读取
     *
     * @param string $sessionId
     *
     * @return array|null
     */
    public function read($sessionId)
    {

        if (Config::get('session_user_loc') == 'db') {
            $result = $this->handler->get(Config::get('session_user_loc_table') . '-id-' . $sessionId, true, true, Config::get('session_user_loc_tableprefix'));
            return $result ? $result[0]['value'] : null;
        } else {
            $result = $this->handler->get(Config::get('session_user_loc_tableprefix') . Config::get('session_user_loc_table') . '-id-' . $sessionId);
            return $result ? $result : null;
        }
    }

    /**
     * session 写入
     *
     * @param string $sessionId
     * @param string $value
     *
     * @return bool
     */
    public function write($sessionId, $value)
    {
        if (Config::get('session_user_loc') == 'db') {
            $this->handler->set(Config::get('session_user_loc_table'), [
                'id' => $sessionId,
                'value' => $value,
                'ctime' => Cml::$nowTime
            ], Config::get('session_user_loc_tableprefix'));
        } else {
            $this->handler->set(Config::get('session_user_loc_tableprefix') . Config::get('session_user_loc_table') . '-id-' . $sessionId, $value, $this->lifeTime);
        }
        return true;
    }

    /**
     * session 销毁
     *
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        if (Config::get('session_user_loc') == 'db') {
            $this->handler->delete(Config::get('session_user_loc_table') . '-id-' . $sessionId, true, Config::get('session_user_loc_tableprefix'));
        } else {
            $this->handler->delete(Config::get('session_user_loc_tableprefix') . Config::get('session_user_loc_table') . '-id-' . $sessionId);
        }
        return true;
    }

    /**
     * session gc回收
     *
     * @param int $lifeTime
     *
     * @return bool
     */
    public function gc($lifeTime = 0)
    {
        if (Config::get('session_user_loc') == 'db') {
            $lifeTime || $lifeTime = $this->lifeTime;
            $this->handler->whereLt('ctime', Cml::$nowTime - $lifeTime)
                ->delete(Config::get('session_user_loc_table'), true, Config::get('session_user_loc_tableprefix'));
        }
        //else //cache 本身会回收
        return true;
    }
}
