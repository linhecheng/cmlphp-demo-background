<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 文件缓存驱动
 * *********************************************************** */

namespace Cml\Cache;

use Cml\Cml;
use Cml\Config;

/**
 * 文件缓存驱动
 *
 * @package Cml\Cache
 */
class File extends namespace\Base
{
    /**
     * @var bool | resource
     */
    private $lock = false;//是否对文件锁操作 值为bool或打开的文件指针

    /**
     * 使用的缓存配置 默认为使用default_cache配置的参数
     *
     * @param bool ｜array $conf
     */
    public function __construct($conf = false)
    {
        $this->conf = $conf ? $conf : Config::get('default_cache');
        $this->conf['CACHE_PATH'] = isset($this->conf['CACHE_PATH']) ? $this->conf['CACHE_PATH'] : Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . 'FileCache' . DIRECTORY_SEPARATOR;
        is_dir($this->conf['CACHE_PATH']) || mkdir($this->conf['CACHE_PATH'], 0700, true);
    }

    /**
     * 获取缓存
     *
     * @param string $key 要获取的缓存key
     *
     * @return mixed
     */
    public function get($key)
    {
        $fileName = $this->getFileName($key);
        if (!is_file($fileName)) {
            if ($this->lock) {
                $this->lock = false;
                $this->set($key, 0);
                return 0;
            }
            return false;
        }
        $fp = fopen($fileName, 'r+');
        if ($this->lock) {//自增自减  上锁
            $this->lock = $fp;
            if (flock($fp, LOCK_EX) === false) return false;
        }
        $data = fread($fp, filesize($fileName));
        $this->lock || fclose($fp);//非自增自减操作时关闭文件
        if ($data === false) {
            return false;
        }
        //缓存过期
        $fileTime = substr($data, 13, 10);
        $pos = strpos($data, ')');
        $cacheTime = substr($data, 24, $pos - 24);
        $data = substr($data, $pos + 1);
        if ($cacheTime == 0) return unserialize($data);

        if (Cml::$nowTime > (intval($fileTime) + intval($cacheTime))) {
            unlink($fileName);
            return false;//缓存过期
        }
        return unserialize($data);
    }

    /**
     * 写入缓存
     *
     * @param string $key key 要缓存的数据的key
     * @param mixed $value 要缓存的数据 要缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        $value = '<?php exit;?>' . time() . "($expire)" . serialize($value);

        if ($this->lock) {//自增自减
            fseek($this->lock, 0);
            $return = fwrite($this->lock, $value);
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
            $this->lock = false;
        } else {
            $fileName = $this->getFileName($key);
            $return = file_put_contents($fileName, $value, LOCK_EX);
        }
        $return && clearstatcache();
        return $return;
    }

    /**
     * 更新缓存  可以直接用set但是为了一致性操作所以做此兼容
     *
     * @param string $key 要更新的数据的key
     * @param mixed $value 要更新缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool
     */
    public function update($key, $value, $expire = 0)
    {
        return $this->set($key, $value, $expire);
    }

    /**
     * 删除缓存
     *
     * @param string $key 要删除的数据的key
     *
     * @return bool
     */
    public function delete($key)
    {
        $fileName = $this->getFileName($key);
        return (is_file($fileName) && unlink($fileName));
    }

    /**
     * 清空缓存
     *
     * @return bool
     */
    public function truncate()
    {
        set_time_limit(60);
        if (!is_dir($this->conf['CACHE_PATH'])) return true;
        $this->cleanDir('all');
        return true;
    }

    /**
     * 清空文件夹
     *
     * @param string $dir
     *
     * @return bool
     */
    public function cleanDir($dir)
    {
        if (empty($dir)) return false;

        $dir === 'all' && $dir = '';//删除所有
        $fullDir = $this->conf['CACHE_PATH'] . $dir;
        if (!is_dir($fullDir)) {
            return false;
        }

        $files = scandir($fullDir);
        foreach ($files as $file) {
            if ('.' === $file || '..' === $file) continue;
            $tmp = $fullDir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($tmp)) {
                $this->cleanDir($dir . DIRECTORY_SEPARATOR . $file);
            } else {
                unlink($tmp);
            }
        }
        rmdir($fullDir);
        return true;
    }

    /**
     * 自增
     *
     * @param string $key 要自增的缓存的数据的key
     * @param int $val 自增的进步值,默认为1
     *
     * @return bool
     */
    public function increment($key, $val = 1)
    {
        $this->lock = true;
        $v = $this->get($key);
        if (is_int($v)) {
            return $this->update($key, $v + abs(intval($val)));
        } else {
            $this->set($key, 1);
            return 1;
        }
    }

    /**
     * 自减
     *
     * @param string $key 要自减的缓存的数据的key
     * @param int $val 自减的进步值,默认为1
     *
     * @return bool
     */
    public function decrement($key, $val = 1)
    {
        $this->lock = true;
        $v = $this->get($key);
        if (is_int($v)) {
            return $this->update($key, $v - abs(intval($val)));
        } else {
            $this->set($key, 0);
            return 0;
        }
    }

    /**
     * 获取缓存文件名
     *
     * @param  string $key 缓存名
     *
     * @return string
     */
    private function getFileName($key)
    {
        $md5Key = md5($this->conf['prefix'] . $key);

        $dir = $this->conf['CACHE_PATH'] . substr($key, 0, strrpos($key, '/')) . DIRECTORY_SEPARATOR;
        $dir .= substr($md5Key, 0, 2) . DIRECTORY_SEPARATOR . substr($md5Key, 2, 2);
        is_dir($dir) || mkdir($dir, 0700, true);
        return $dir . DIRECTORY_SEPARATOR . $md5Key . '.php';
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @param string $key
     *
     * @return void
     */
    public function getInstance($key = '')
    {
    }
}
