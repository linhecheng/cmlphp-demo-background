<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 字符串处理类
 * *********************************************************** */
namespace Cml\Vendor;

/**
 * 字符串处理类,包含字符串截取、获取随机字符串等
 *
 * @package Cml\Vendor
 */
class StringProcess
{

    /**
     * 返回两个字符串的相似度
     *
     * @param string  $string1
     * @param string $string2
     * @return int
     */
    public static function strSimilar($string1, $string2)
    {
        similar_text($string1, $string2, $percent);
        return round($percent / 100, 2);
    }

    /**
     * 计算两个字符串间的levenshteinDistance
     * @param string $string1
     * @param string $string2
     * @param int $costReplace 定义替换次数
     * @param string $encoding
     * @return mixed
     */
    public static function levenshteinDistance($string1, $string2,  $costReplace = 1, $encoding = 'UTF-8')
    {
        $mbStringToArrayFunc = function ($string) use ($encoding)
        {
            $arrayResult = [];
            while ($iLen = mb_strlen($string, $encoding)) {
                array_push($arrayResult, mb_substr($string, 0, 1, $encoding));
                $string = mb_substr($string, 1, $iLen, $encoding);
            }
            return $arrayResult;
        };

        $countSameLetter = 0;
        $d = [];
        $mbLen1 = mb_strlen($string1, $encoding);
        $mbLen2 = mb_strlen($string2, $encoding);

        $mbStr1 = $mbStringToArrayFunc($string1, $encoding);
        $mbStr2 = $mbStringToArrayFunc($string2, $encoding);

        $maxCount = count($mbStr1) > count($mbStr2) ? count($mbStr1) : count($mbStr2);

        for ($i1 = 0; $i1 <= $mbLen1; $i1++) {
            $d[$i1] = [];
            $d[$i1][0] = $i1;
        }

        for ($i2 = 0; $i2 <= $mbLen2; $i2++) {
            $d[0][$i2] = $i2;
        }

        for ($i1 = 1; $i1 <= $mbLen1; $i1++) {
            for ($i2 = 1; $i2 <= $mbLen2; $i2++) {
                // $cost = ($str1[$i1 - 1] == $str2[$i2 - 1]) ? 0 : 1;
                if ($mbStr1[$i1 - 1] === $mbStr2[$i2 - 1]) {
                    $cost = 0;
                    $countSameLetter++;
                } else {
                    $cost = $costReplace; //替换
                }
                $d[$i1][$i2] = min($d[$i1 - 1][$i2] + 1, //插入
                    $d[$i1][$i2 - 1] + 1, //删除
                    $d[$i1 - 1][$i2 - 1] + $cost);
            }
        }

        $percent  = round(($maxCount - $d[$mbLen1][$mbLen2]) / $maxCount, 2);

        //return $d[$mbLen1][$mbLen2];
        return ['distance' => $d[$mbLen1][$mbLen2], 'count_same_letter' => $countSameLetter, 'percent' => $percent];
    }

    /**
     * 检查字符串是否是UTF8编码
     *
     * @param string $string 字符串
     *
     * @return Boolean
     */
    public static function isUtf8($string)
    {
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++){
            $c = ord($string[$i]);
            if ($c > 128){
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }
                if (($i + $bits) > $len) return false;
                while ($bits > 1){
                    $i++;
                    $b = ord($string[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }

    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $string 需要转换的字符串
     * @param int $start 开始位置
     * @param int $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断字符串后缀
     *
     * @return string
     */
    public static function substrCn($string, $start = 0, $length, $charset = "utf-8", $suffix = '')
    {
        if (function_exists("mb_substr")){
            return mb_substr($string, $start, $length, $charset).$suffix;
        } elseif (function_exists('iconv_substr')) {
            return iconv_substr($string, $start, $length, $charset).$suffix;
        }
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $string, $match);
        $slice = join("",array_slice($match[0], $start, $length));
        return $slice.$suffix;
    }

    /**
     * 产生随机字串 //中文 需要php_mbstring扩展支持
     *
     * 默认长度6位 字母和数字混合 支持中文
     * @param int $len 长度
     * @param int $type 字串类型 0 字母 1 数字 其它 混合
     * @param string $addChars 自定义一部分字符
     *
     * @return string
     */
    public static function randString($len = 6, $type = 0, $addChars = '')
    {
        $string = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789',3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
                break;
            default :
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
                break;
        }
        if ($len > 10 ) {//位数过长重复字符串一定次数
            $chars = $type == 1 ? str_repeat($chars,$len) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $string = substr($chars, 0, $len);
        } else {
            // 中文 需要php_mbstring扩展支持
            for ($i = 0; $i < $len; $i++){
                $string .= self::substrCn($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8', false);
            }
        }
        return $string;
    }
}