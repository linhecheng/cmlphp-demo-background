<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 文件上传扩展类
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;

/**
 * 文件上传扩展类
 *
 * @package Cml\Vendor
 */
class UploadFile
{
    private $config = [
        'maxSize' => -1, //上传文件的最大值
        'allowExts' => [], //允许上传的文件后缀，留空则不做限制，不带点
        'allowTypes' => [], //允许上传的文件类型，留空不作检查
        'thumb' => false, //对上传的图片进行缩略图处理
        'thumbMaxWidth' => '100',//缩略图的最大宽度
        'thumbMaxHeight' => '100', //缩略图的最大高度
        'thumbPrefix' => 'mini_',//缩略图前缀
        'thumbPath'         =>  '',// 缩略图保存路径
        'thumbFile'         =>  '',// 缩略图文件名 带后缀
        'subDir' => false,//启用子目录保存文件
        'subDirType' => 'hash', //子目录创建方式，hash\date两种
        'dateFormat' => 'Y/m/d', //按日期保存的格式
        'hashLevel' => 1, //hash的目录层次
        'savePath' => '', //上传文件的保存路径
        'replace' => false, //替换同名文件
        'rename' => true,//是否生成唯一文件名
    ];

    //上传失败的信息
    private $errorInfo = '';

    // 上传成功的文件信息
    private $successInfo ;

    /**
     * 魔术方法快速获取配置
     *
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    /**
     * 魔术方法，快速配置参数
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**魔术方法查询是否存在配置项
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 构造方法
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        is_array($config) && $this->config = array_merge($this->config, $config);
    }

    /**
     * 上传所有文件
     *
     * @param null|string $savePath 上传文件的保存路径
     *
     * @return bool
     */
    public function upload($savePath = null)
    {
        is_null($savePath) && $savePath = $this->config['savePath'];
        $savePath = $savePath.'/';
        $fileInfo = [];
        $isUpload = false;

        //获取上传的文件信息
        $files = $this->workingFiles($_FILES);
        foreach ($files as $key => $file) {
            //过滤无效的选框
            if (!empty($file['name'])) {
                //登记上传文件的扩展信息
                !isset($file['key']) && $file['key'] = $key;
                $pathinfo = pathinfo($file['name']);
                $file['extension'] = $pathinfo['extension'];
                $file['savepath'] = $savePath;
                $saveName = $this->getSaveName($savePath, $file);
                $file['savename'] = $saveName;//取得文件名

                //创建目录
                if (is_dir($file['savepath'])) {
                    if (!is_writable($file['savepath'])) {
                        $this->errorInfo = "上传目录{$savePath}不可写";
                        return false;
                    }
                } else {
                    if (!mkdir($file['savepath'], 0700, true)) {
                        $this->errorInfo = "上传目录{$savePath}不可写";
                        return false;
                    }
                }
                //自动查检附件
                if (!$this->secureCheck($file)) return false;
                //保存上传文件
                if (!$this->save($file)) return false;
                unset($file['tmp_name'], $file['error']);
                $fileInfo[] = $file;
                $isUpload = true;
            }
        }
        if ($isUpload) {
            $this->successInfo = $fileInfo;
            return true;
        } else {
            $this->errorInfo = '没有选择上传文件';
            return false;
        }
    }

    /**
     * 根据上传文件命名规则取得保存文件名
     *
     * @param string $savepath
     * @param string $filename
     *
     * @return string
     */
    private function getSaveName($savepath, $filename)
    {
        //重命名
        $saveName = $this->config['rename'] ? \Cml\createUnique().'.'.$filename['extension'] : $filename['name'];
        if ($this->config['subDir']) {
            //使用子目录保存文件
            switch ($this->config['subDirType']) {
                case 'date':
                    $dir    =   date($this->config['dateFormat'], Cml::$nowTime).'/';
                    break;
                case 'hash':
                default:
                    $name   =   md5($saveName);
                    $dir    =   '';
                    for ($i = 0; $i < $this->config['hashLevel']; $i++) {
                        $dir   .=  $name{$i}.'/';
                    }
                    break;
            }
            if (!is_dir($savepath.$dir)) {
                mkdir($savepath.$dir,0700, true);
            }
            $saveName = $dir.$saveName;
        }
        return $saveName;
    }

    /**
     * 把同一个选框名有多个文件的上传信息转换成跟 单个文件一样的数组
     *
     * @param $files ($_FILES)
     *
     * @return array
     */
    private function workingFiles($files)
    {
        $fileArray = [];
        $n = 0;
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) { //一个表单name有多个文件
                $keys = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    $fileArray[$n]['key'] = $key; //这边的key为表单中的file选框的name 比如有两个上传框一个叫attach 一个叫img 这两个都可为数组(多个)
                    foreach ($keys as $_key) {
                        $fileArray[$n][$_key] = $file[$_key][$i];
                    }
                    $n++;
                }
            } else {
                $fileArray[$key] = $file;
            }
        }
        return $fileArray;
    }

    /**
     * 保存
     *
     * @param array $file
     *
     * @return bool
     */
    private function save($file)
    {
        $filename = $file['savepath'].$file['savename'];
        if (!$this->config['replace'] && is_file($filename)) { //不覆盖同名文件
            $this->errorInfo = "文件已经存在{$filename}";
            return false;
        }

        //如果是图片，检查格式
        if ( in_array(strtolower($file['extension']), ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])
            && false === getimagesize($file['tmp_name']) ) {
            $this->errorInfo = '非法图像文件';
            return false;
        }
        if (!move_uploaded_file($file['tmp_name'], $filename)) {
            $this->errorInfo = '文件上传错误!';
            return false;
        }

        if ($this->config['thumb'] && in_array(strtolower($file['extension']), ['gif', 'jpg', 'jpeg', 'bmp', 'png'])) {
            if ($image = getimagesize($filename)) {
                //生成缩略图
                $thumbPath = $this->config['thumbPath'] ? $this->config['thumbPath'] : dirname($filename);
                $thunbName = $this->config['thumbFile'] ? $this->config['thumbFile'] : $this->config['thumbPrefix'].basename($filename);
                Image::makeThumb($filename, $thumbPath.'/'.$thunbName, null, $this->config['thumbMaxWidth'], $this->config['thumbMaxHeight']);
            }
        }
        return true;
    }

    /**
     * 检查上传的文件有没上传成功是否合法
     *
     * @param array $file 上传的单个文件
     *
     * @return bool
     */
    private function secureCheck($file)
    {
        //文件上传失败，检查错误码
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    $this->errorInfo = '上传的文件大小超过了 php.ini 中 upload_max_filesize 选项限制的值';
                    break;
                case 2:
                    $this->errorInfo = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                    break;
                case 3:
                    $this->errorInfo = '文件只有部分被上传';
                    break;
                case 4:
                    $this->errorInfo = '没有文件被上传';
                    break;
                case 6:
                    $this->errorInfo = '找不到临时文件夹';
                    break;
                case 7:
                    $this->errorInfo = '文件写入失败';
                    break;
                default:
                    $this->errorInfo = '未知上传错误！';
            }
            return false;
        }

        //文件上传成功，进行自定义检查
        if ( (-1 != $this->config['maxSize']) &&  ($file['size'] > $this->config['maxSize']) ) {
            $this->errorInfo = '上传文件大小不符';
            return false;
        }

        //检查文件Mime类型
        if (!$this->checkType($file['type'])) {
            $this->errorInfo = '上传文件mime类型允许';
            return false;
        }

        //检查文件类型
        if (!$this->checkExt($file['extension'])) {
            $this->errorInfo ='上传文件类型不允许';
            return false;
        }

        //检查是否合法上传
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->errorInfo = '非法的上传文件！';
            return false;
        }
        return true;
    }

    /**
     * 查检文件的mime类型是否合法
     *
     * @param string $type
     *
     * @return bool
     */
    private function checkType($type)
    {
        if (!empty($this->allowTypes)) {
            return in_array(strtolower($type), $this->allowTypes);
        }
        return true;
    }

    /**
     * 检查上传的文件后缀是否合法
     *
     * @param string $ext
     *
     * @return bool
     */
    private function checkExt($ext)
    {
        if (!empty($this->allowExts)) {
            return in_array(strtolower($ext), $this->allowExts, true);
        }
        return true;
    }

    /**
     * 取得最后一次错误信息
     *
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->errorInfo;
    }

    /**
     * 取得上传文件的信息
     *
     * @return array
     */
    public function getSuccessInfo()
    {
        return $this->successInfo;
    }

}