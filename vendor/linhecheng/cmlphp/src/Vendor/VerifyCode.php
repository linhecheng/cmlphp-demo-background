<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 13-9-4 下午4:35
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 验证码扩展类
 * *********************************************************** */
namespace Cml\Vendor;

use Cml\Http\Cookie;
use Cml\Model;

/**
 * 验证码扩展类 用于生成验证码
 *
 * @package Cml\Vendor
 */
class VerifyCode
{
    /**
     * 生成图像数字验证码
     *
     * @param int $length 位数
     * @param string $type 图像格式
     * @param int $width 宽度
     * @param int $height 高度
     * @param string $verifyName Cookie中保存的名称
     * @param string $font 字体名
     *
     * @return void
     */
    public static function numVerify($length = 4, $type = 'png', $width = 150, $height = 35, $verifyName = 'verifyCode', $font = 'tahoma.ttf')
    {
        $randNum = substr(str_shuffle(str_repeat('0123456789', 5)), 0, $length);
        $authKey = md5(mt_rand() . microtime());
        Cookie::set($verifyName, $authKey);
        Model::getInstance()->cache()->set($authKey, $randNum, 1800);
        $width = ($length * 33 + 20) > $width ? $length * 33 + 20 : $width;
        $height = $length < 35 ? 35 : $height;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor($width, $height);
        } else {
            $im = imagecreate($width, $height);
        }
        $r = Array(225, 255, 255, 223);
        $g = Array(225, 236, 237, 255);
        $b = Array(225, 236, 166, 125);
        $key = mt_rand(0, 3);

        $backColor = imagecolorallocate($im, $r[$key], $g[$key], $b[$key]);//背景色（随机）
        $borderColor = imagecolorallocate($im, 100, 100, 100); //边框色
        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        // 干扰
        for ($i = 0; $i < 10; $i++) {
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $stringColor);
        }
        for ($i = 0; $i < 25; $i++) {
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $stringColor);
        }
        for ($i = 0; $i < $length; $i++) {
            $x = $i === 0 ? 15 : $i * 35;
            $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
            imagettftext($im, 28, mt_rand(0, 60), $x, 35, $stringColor, CML_EXTEND_PATH . DIRECTORY_SEPARATOR . $font, $randNum[$i]);
        }
        self::output($im, $type);
    }

    /**
     * 中文验证码
     *
     * @param int $length
     * @param string $type
     * @param int $width
     * @param int $height
     * @param string $font
     * @param string $verifyName
     *
     * @return void
     */
    public static function CnVerify($length = 4, $type = 'png', $width = 180, $height = 50, $font = 'tahoma.ttf', $verifyName = 'verifyCode')
    {
        $code = StringProcess::randString($length, 4);
        $width = ($length * 45) > $width ? $length * 45 : $width;
        $authKey = md5(mt_rand() . microtime());
        Cookie::set($verifyName, $authKey);
        Model::getInstance()->cache()->set($authKey, md5($code), 1800);
        $im = imagecreatetruecolor($width, $height);
        $borderColor = imagecolorallocate($im, 100, 100, 100);  //边框色
        $bkcolor = imagecolorallocate($im, 250, 250, 250);
        imagefill($im, 0, 0, $bkcolor);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        // 干扰
        for ($i = 0; $i < 15; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $fontcolor);
        }
        for ($i = 0; $i < 255; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $fontcolor);
        }
        if (!is_file($font)) {
            $font = CML_EXTEND_PATH . DIRECTORY_SEPARATOR . $font;
        }
        for ($i = 0; $i < $length; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120));
            $codex = StringProcess::substrCn($code, $i, 1);
            imagettftext($im, mt_rand(16, 20), mt_rand(-60, 60), 40 * $i + 20, mt_rand(30, 35), $fontcolor, $font, $codex);
        }
        self::output($im, $type);
    }

    /**
     * 生成数字计算题验证码
     *
     * @param string $type
     * @param int $width
     * @param int $height
     * @param string $font
     * @param string $verifyName
     *
     * @return void
     */
    public static function calocVerify($type = 'png', $width = 170, $height = 45, $font = 'tahoma.ttf', $verifyName = 'verifyCode')
    {
        $la = $ba = 0;
        $calcType = mt_rand(1, 3);
        $createNumber = function () use (&$la, &$ba, $calcType) {
            $la = mt_rand(1, 9);
            $ba = mt_rand(1, 9);
        };
        $createNumber();
        if ($calcType == 3) {
            while ($la == $ba) {
                $createNumber();
            }
            if ($la < $ba) {
                $tmp = $la;
                $la = $ba;
                $ba = $tmp;
            }
        }
        $calcTypeArr = [
            1 => $la + $ba,
            2 => $la * $ba,
            3 => $la - $ba
            // 4 => $la / $ba,
        ];
        $randStr = $calcTypeArr[$calcType];
        $randResult = [
            1 => $la . '+' . $ba . '=?',
            2 => $la . '*' . $ba . '=?',
            3 => $la . '-' . $ba . '=?'
            // 4 => $la .'/'. $ba.'='. $randarr[4],
        ];
        $calcResult = $randResult[$calcType];
        $authKey = md5(mt_rand() . microtime());
        Cookie::set($verifyName, $authKey);

        Model::getInstance()->cache()->set($authKey, $randStr, 1800);
        //$width = ($length * 10 + 10) > $width ? $length * 10 + 10 : $width;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor($width, $height);
        } else {
            $im = imagecreate($width, $height);
        }
        $r = Array(225, 255, 255, 223);
        $g = Array(225, 236, 237, 255);
        $b = Array(225, 236, 166, 125);
        $key = mt_rand(0, 3);

        $backColor = imagecolorallocate($im, $r[$key], $g[$key], $b[$key]);    //背景色（随机）
        $borderColor = imagecolorallocate($im, 100, 100, 100);                    //边框色
        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        // 干扰
        for ($i = 0; $i < 10; $i++) {
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $stringColor);
        }
        for ($i = 0; $i < 25; $i++) {
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $stringColor);
        }
        for ($i = 0; $i < 5; $i++) {
            //  imagestring($im, 5, $i * 10 + 5, mt_rand(1, 8), $calcResult{$i}, $stringColor);
            $x = $i === 0 ? 20 : $i * 50;
            $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
            if ($i == 1 || $i == 3 || $i == 4) {
                $fontSize = $calcType == 3 ? 50 : 28;
                if ($i == 1) {
                    imagettftext($im, $fontSize, 0, $x, 35, $stringColor, CML_EXTEND_PATH . DIRECTORY_SEPARATOR . $font, $calcResult[$i]);
                } else {
                    $decNum = $i == 3 ? 30 : 55;
                    imagettftext($im, 25, 0, $x - $decNum, 35, $stringColor, CML_EXTEND_PATH . DIRECTORY_SEPARATOR . $font, $calcResult[$i]);
                }
            } else {
                imagettftext($im, 28, mt_rand(0, 60), $x, 35, $stringColor, CML_EXTEND_PATH . DIRECTORY_SEPARATOR . $font, $calcResult[$i]);
            }
        }
        self::output($im, $type);
    }

    /**
     * 校验验证码
     *
     * @param string $input 用户输入
     * @param bool $isCn 是否为中文验证码
     * @param string $verifyName 生成验证码时的字段
     *
     * @return bool 正确返回true,错误返回false
     */
    public static function checkCode($input, $isCn = false, $verifyName = 'verifyCode')
    {
        $key = Cookie::get($verifyName);
        if (!$key) return false;
        $code = Model::getInstance()->cache()->get($key);
        Model::getInstance()->cache()->delete($key);
        $isCn && $input = md5(urldecode($input));
        if ($code === false || $code != $input) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 输出图片
     *
     * @param resource $image 被载入的图片
     * @param string $type 输出的类型
     * @param string $filename 保存的文件名
     * @param int $quality jpeg保存的质量
     *
     * @return void
     */
    public static function output(&$image, $type = 'png', $filename = null, $quality = 100)
    {
        $type == 'jpg' && $type = 'jpeg';
        $imageFun = "image{$type}";
        if (is_null($filename)) { //输出到浏览器
            header("Content-type: image/{$type}");
            ($type == 'jpeg') ? $imageFun($image, null, $quality) : $imageFun($image);
        } else { //保存到文件
            ($type == 'jpeg') ? $imageFun($image, $filename, $quality) : $imageFun($image, $filename);
        }
        imagedestroy($image);
        exit();
    }
}