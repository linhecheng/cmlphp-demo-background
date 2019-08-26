<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 图片处理扩展类
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * 图片处理扩展类
 *
 * @package Cml\Vendor
 */
class Image
{

    /**
     * 取得图像信息
     * @access public
     *
     * @param string $image 图像文件名
     *
     * @return array | false
     */
    public static function getImageInfo($image)
    {
        $imagesInfoArr = @getimagesize($image);
        if (!$imagesInfoArr) return false;
        list($imagesInfo['width'], $imagesInfo['height'], $imagesInfo['ext']) = $imagesInfoArr;
        $imagesInfo['ext'] = strtolower(ltrim(image_type_to_extension($imagesInfo['ext']), '.'));
        return $imagesInfo;
    }

    /**
     * 图片打水印
     *
     * @param string $sourceImage 源图片
     * @param string $waterMarkImage 水印
     * @param null|string $saveName 保存路径，默认为覆盖原图
     * @param int $alpha 水印透明度
     * @param null $positionW 水印位置 相对原图横坐标
     * @param null $positionH 水印位置 相对原图纵坐标
     * @param int $quality 生成的图片的质量 jpeg有效
     *
     * @return mixed
     */
    public static function addWaterMark($sourceImage, $waterMarkImage, $saveName = null, $alpha = 80, $positionW = null, $positionH = null, $quality = 100)
    {
        if (!is_file($sourceImage) || !is_file($waterMarkImage)) return false;
        //获取图片信息
        $sourceImageInfo = self::getImageInfo($sourceImage);
        $waterMarkImageInfo = self::getImageInfo($waterMarkImage);
        if ($sourceImageInfo['width'] < $waterMarkImageInfo['width'] || $sourceImageInfo['height'] < $waterMarkImageInfo['height'] || $sourceImageInfo['ext'] == 'bmp' || $waterMarkImageInfo['bmp']) return false;

        //创建图像
        $sourceImageCreateFunc = "imagecreatefrom{$sourceImageInfo['ext']}";
        $sourceCreateImage = $sourceImageCreateFunc($sourceImage);
        $waterMarkImageCreateFunc = "imagecreatefrom{$waterMarkImageInfo['ext']}";
        $waterMarkCreateImage = $waterMarkImageCreateFunc($waterMarkImage);

        //设置混色模式
        imagealphablending($waterMarkImage, true);

        $posX = is_null($positionW) ? $sourceImageInfo['width'] - $waterMarkImageInfo['width'] : $sourceImageInfo['width'] - $positionW;
        $posY = is_null($positionH) ? $sourceImageInfo['height'] - $waterMarkImageInfo['height'] : $sourceImageInfo['height'] - $positionH;

        //生成混合图像
        imagecopymerge($sourceCreateImage, $waterMarkCreateImage, $posX, $posY, 0, 0, $waterMarkImageInfo['width'], $waterMarkImageInfo['height'], $alpha);

        //生成处理后的图像
        if (is_null($saveName)) {
            $saveName = $sourceImage;
            @unlink($sourceImage);
        }
        self::output($sourceCreateImage, $sourceImageInfo['ext'], $saveName, $quality);
        return true;
    }

    /**
     * 生成缩略图
     *
     * @param string $image 要缩略的图
     * @param string $thumbName 生成的缩略图的路径
     * @param null $type 要生成的图片类型 默认跟原图一样
     * @param int $width 缩略图的宽度
     * @param int $height 缩略图的高度
     * @param bool $isAutoFix 是否按比例缩放
     *
     * @return false|string
     */
    public static function makeThumb($image, $thumbName, $type = null, $width = 100, $height = 50, $isAutoFix = true)
    {
        is_dir(dirname($thumbName)) || mkdir(dirname($thumbName), 0700, true);
        $imageInfo = self::getImageInfo($image);
        if (!$imageInfo) return false;

        $type = is_null($type) ? strtolower($imageInfo['ext']) : strtolower(($type == 'jpg' ? 'jpeg' : $type));

        if ($isAutoFix) { //根据比例缩放
            $fixScale = min($width  / $imageInfo['width'], $height / $imageInfo['height']);//缩放的比例
            if ($fixScale > 1) {
                //缩略图超过原图大小
                $width = $imageInfo['width'];
                $height = $imageInfo['height'];
            } else {
                $width = $imageInfo['width'] * $fixScale;
                $height = $imageInfo['height'] * $fixScale;
            }
        } else {
            ($width > $imageInfo['width']) && $width = $imageInfo['width'];
            ($height > $imageInfo['height']) && $height = $imageInfo['height'];
        }

        if (!in_array($type, ['jpeg', 'gif', 'png'])) {
            return false;
        }
        $createImageFunc = "imagecreatefrom{$type}";
        $sourceCreateImage = $createImageFunc($image);//载入原图

        //生成缩略图
        if ($type == 'gif' || !function_exists('imagecreatetruecolor')) {
            $thumbImg = imagecreate($width, $height);
        } else {
            $thumbImg = imagecreatetruecolor($width, $height);
        }

        if ($type == 'png') {
            imagealphablending($thumbImg, false);//取消默认的混色模式（为解决阴影为绿色的问题）
            imagesavealpha($thumbImg,true);//设定保存完整的 alpha 通道信息（为解决阴影为绿色的问题）
        } elseif ($type == 'gif') {
            $transparentIndex = imagecolortransparent($sourceCreateImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($sourceCreateImage , $transparentIndex);
                $transparentIndex = imagecolorallocate($thumbImg, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                imagefill($thumbImg, 0, 0, $transparentIndex);
                imagecolortransparent($thumbImg, $transparentIndex);
            }
        }
        //复制
        function_exists('imagecopyresampled') ? imagecopyresampled($thumbImg, $sourceCreateImage, 0, 0, 0, 0, $width, $height, $imageInfo['width'], $imageInfo['height']) : imagecopyresized($thumbImg, $sourceCreateImage, 0, 0, 0, 0, $width, $height, $imageInfo['width'], $imageInfo['height']);
        self::output($thumbImg, $type, $thumbName);
        imagedestroy($sourceCreateImage);
        return $thumbName;
    }

    /**
     * 输出图片
     *
     * @param string $image 被载入的图片
     * @param string $type 输出的类型
     * @param string $filename 保存的文件名
     * @param int $quality jpeg保存的质量
     *
     * @return mixed
     */
    public static function output(&$image, $type='png', $filename = null, $quality = 100)
    {
        $type == 'jpg' && $type = 'jpeg';
        $imageFun = "image{$type}";
        if (is_null($filename)) { //输出到浏览器
            header("Content-type: image/{$type}");
            ($type == 'jpeg') ? $imageFun($image, null, $quality) : $imageFun($image);
            imagedestroy($image);
            exit();
        } else { //保存到文件
            ($type == 'jpeg') ? $imageFun($image, $filename, $quality) : $imageFun($image, $filename);
            imagedestroy($image);
            return $filename;
        }
    }

}