<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 文件操作类
 * *********************************************************** */
namespace Cml\Vendor;

/**
 * 文件操作类
 *
 * @package Cml\Vendor
 */
class File
{

    /**
     * 创建空文件
     *
     * @param string $filename  需要创建的文件
     *
     * @return mixed
     */
    public static function createFile($filename)
    {
        if (is_file($filename)) return false;
        self::createDir(dirname($filename)); //创建目录
        return file_put_contents($filename,'');
    }

    /**
     * 写文件
     *
     * @param  string $filename  文件名称
     * @param  string $content   写入文件的内容
     * @param  int $type   类型，1=清空文件内容，写入新内容，2=再内容后街上新内容
     *
     * @return bool
     */
    public static function writeFile($filename, $content, $type = 1)
    {
        if ($type == 1) {
            is_file($filename) && self::delFile($filename); //删除文件
            self::createFile($filename);
            self::writeFile($filename, $content, 2);
            return true;
        } else {
            if (!is_writable($filename)) return false;
            $handle = fopen($filename, 'a');
            if (!$handle) return false;
            $result = fwrite($handle, $content);
            if (!$result) return false;
            fclose($handle);
            return true;
        }
    }

    /**
     * 拷贝一个新文件
     *
     * @param  string $filename    文件名称
     * @param  string $newfilename 新文件名称
     *
     * @return bool
     */
    public static function copyFile($filename, $newfilename)
    {
        if (!is_file($filename) || !is_writable($filename)) return false;
            self::createDir(dirname($newfilename)); //创建目录
        return copy($filename, $newfilename);
    }

    /**
     * 移动文件
     *
     * @param  string $filename 文件名称
     * @param  string $newfilename 新文件名称
     *
     * @return bool
     */
    public static function moveFile($filename, $newfilename)
    {
        if (!is_file($filename) || !is_writable($filename)) return false;
            self::createDir(dirname($newfilename)); //创建目录
        return rename($filename, $newfilename);
    }

    /**
     * 删除文件
     *
     * @param string $filename  文件名称
     *
     * @return bool
     */
    public static function delFile($filename)
    {
        if (!is_file($filename) || !is_writable($filename)) return true;
        return unlink($filename);
    }

    /**
     * 获取文件信息
     *
     * @param string $filename  文件名称
     *
     * @return bool | array  ['上次访问时间','inode 修改时间','取得文件修改时间','大小'，'类型']
     */
    public static function getFileInfo($filename)
    {
        if (!is_file($filename)) {
            return false;
        }
        return [
            'atime' => date("Y-m-d H:i:s", fileatime($filename)),
            'ctime' => date("Y-m-d H:i:s", filectime($filename)),
            'mtime' => date("Y-m-d H:i:s", filemtime($filename)),
            'size'  => filesize($filename),
            'type'  => filetype($filename)
        ];
    }

    /**
     * 创建目录
     *
     * @param string  $path   目录
     *
     * @return bool
     */
    public static function createDir($path)
    {
        if (is_dir($path)) return false;
            self::createDir(dirname($path));
        mkdir($path);
        chmod($path, 0777);
        return true;
    }

    /**
     * 删除目录
     *
     * @param string $path 目录
     *
     * @return bool
     */
    public static function delDir($path)
    {
        $succeed = true;
        if (is_dir($path)){
            $objDir = opendir($path);
            while (false !== ($fileName = readdir($objDir))){
                if (($fileName != '.') && ($fileName != '..')){
                    chmod("$path/$fileName", 0777);
                    if (!is_dir("$path/$fileName")){
                        if (!unlink("$path/$fileName")){
                            $succeed = false;
                            break;
                        }
                    }
                    else{
                        self::delDir("$path/$fileName");
                    }
                }
            }
            if (!readdir($objDir)){
                closedir($objDir);
                if (!rmdir($path)){
                    $succeed = false;
                }
            }
        }
        return $succeed;
    }
}