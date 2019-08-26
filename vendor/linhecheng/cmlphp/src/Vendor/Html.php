<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Html 扩展类 静态页面生成
 * *********************************************************** */
namespace Cml\Vendor;
use Cml\Cml;


/**
 * Html 扩展类 静态页面生成
 *
 * @package Cml\Vendor
 */
class Html
{

    private $htmlPath = 'data/html/'; //静态页面目录
    private $key; //生成的HTML静态页面对应的KEY值
    private $ismd5 = false; //生成的文件名是否MD5加密

    /**
     * 静态页面-开启ob_start(),打开缓冲区
     *
     * @return bool
     */
    public function start()
    {
        return ob_start();
    }

    /**
     * 静态页面-生成静态页面，$key值是生成页面的唯一标识符
     *
     * @param  string  $key  静态页面标识符，可以用id代替
     *
     * @return bool
     */
    public function end($key)
    {
        $this->key = $key;
        $this->html(); //生成HTML文件
        return ob_end_clean(); //清空缓冲
    }

    /**
     * 静态页面-获取静态页面
     *
     * @param  string  $key  静态页面标识符，可以用id代替
     *
     * @return  bool
     */
    public function get($key)
    {
        $filename = $this->getFilename($key);
        if (!$filename || !is_file($filename)) return false;
        Cml::requireFile($filename);
        return true;
    }

    /**
     * 静态页面-生成静态页面
     *
     * @return bool
     */
    private function html()
    {
        $filename = $this->getFilename($this->key);
        if (!$filename) return false;
        return @file_put_contents($filename, ob_get_contents(), LOCK_EX);
    }

    /**
     * 静态页面-静态页面文件
     *
     * @param  string  $key  静态页面标识符，可以用id代替
     * 
     * @return string
     */
    private function getFilename($key)
    {
        $filename = ($this->ismd5 == true) ? md5($key) : $key;
        if (!is_dir($this->htmlPath)) return false;
        return $this->htmlPath . '/' . $filename . '.htm';
    }
}