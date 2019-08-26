<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 数据验证类
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;
use Cml\Config;
use Cml\Exception\FileCanNotReadableException;
use Cml\Lang;

/**
 * 数据验证类,封装了常用的数据验证接口
 *
 * @package Cml\Vendor
 */
class Validate
{

    /**
     * 要验证的数组
     * @var array
     */
    private $data = [];

    /**
     * 自定义的规则
     *
     * @var array
     */
    private static $rules = [];

    /**
     * 数据绑定的验证规则
     *
     * @var array
     */
    private $dateBindRule = [];

    /**
     * 错误提示语
     *
     * @var array
     */
    private static $errorTip = [];

    /**
     * 验证后的错误信息
     *
     * @var array
     */
    private $errorMsg = [];

    /**
     * 字段别名
     *
     * @var array
     */
    private $label = [];

    /**
     * 初始化要检验的参数
     *
     * @param array $data 包含要验证数据的数组
     * @param string|null $langDir 语言包所在的路径
     *
     */
    public function __construct(array $data = [], $langDir = null)
    {
        if (is_null($langDir)) {
            $langDir = __DIR__ . '/Validate/Lang/' . Config::get('lang') . '.php';
        }

        if (!is_file($langDir)) {
            throw new FileCanNotReadableException(Lang::get('_NOT_FOUND_', 'lang dir [' . $langDir . ']'));
        }

        $errorTip = Cml::requireFile($langDir);
        foreach ($errorTip as $key => $val) {
            $key = strtolower($key);
            isset(self::$errorTip[$key]) || self::$errorTip[$key] = $val;
        }

        $this->data = $data;
    }

    /**
     * 动态覆盖语言包
     *
     * @param array $errorTip
     */
    public static function setLang($errorTip = [])
    {
        self::$errorTip = array_merge(self::$errorTip, $errorTip);
    }

    /**
     * 添加一个自定义的验证规则
     *
     * @param  string $name
     * @param  mixed $callback
     * @param  string $message
     * @throws \InvalidArgumentException
     */
    public static function addRule($name, $callback, $message = 'error param')
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('param $callback must can callable');
        }
        self::$errorTip[strtolower($name)] = $message;
        static::$rules[$name] = $callback;
    }

    /**
     * 绑定校验规则到字段
     *
     * @param string $rule
     * @param array|string $field
     *
     * @return $this
     */
    public function rule($rule, $field)
    {
        $ruleMethod = 'is' . ucfirst($rule);
        if (!isset(static::$rules[$rule]) && !method_exists($this, $ruleMethod)) {
            throw new \InvalidArgumentException(Lang::get('_NOT_FOUND_', 'validate rule [' . $rule . ']'));
        }

        $params = array_slice(func_get_args(), 2);

        $this->dateBindRule[] = [
            'rule' => $rule,
            'field' => (array)$field,
            'params' => (array)$params
        ];
        return $this;
    }

    /**
     * 批量绑定校验规则到字段
     *
     * @param array $rules
     *
     * @return $this
     */
    public function rules($rules)
    {
        foreach ($rules as $rule => $field) {
            if (is_array($field) && is_array($field[0])) {
                foreach ($field as $params) {
                    array_unshift($params, $rule);
                    call_user_func_array([$this, 'rule'], $params);
                }
            } else {
                $this->rule($rule, $field);
            }
        }
        return $this;
    }

    /**
     * 自定义错误提示信息
     *
     * @param string $msg
     * @return $this
     */
    public function message($msg)
    {
        $this->dateBindRule[count($this->dateBindRule) - 1]['message'] = $msg;

        return $this;
    }

    /**
     * 执行校验并返回布尔值
     *
     * @return boolean
     */
    public function validate()
    {
        foreach ($this->dateBindRule as $bind) {
            foreach ($bind['field'] as $field) {
                if (strpos($field, '.')) {
                    $values = Cml::doteToArr($field, $this->data);
                } else {
                    $values = isset($this->data[$field]) ? $this->data[$field] : null;
                }

                if (isset(static::$rules[$bind['rule']])) {
                    $callback = static::$rules[$bind['rule']];
                } else {
                    $callback = [$this, 'is' . ucfirst($bind['rule'])];
                }

                $result = true;
                if ($bind['rule'] == 'arr') {
                    $result = call_user_func($callback, $values, $bind['params'], $field);
                } else {
                    is_array($values) || $values = [$values];// GET|POST的值为数组的时候每个值都进行校验
                    foreach ($values as $value) {
                        $result = $result && call_user_func($callback, $value, $bind['params'], $field);
                        if (!$result) {
                            break;
                        }
                    }
                }

                if (!$result) {
                    $this->error($field, $bind);
                }
            }
        }

        return count($this->getErrors()) === 0;
    }

    /**
     * 添加一条错误信息
     *
     * @param string $field
     * @param array $bind
     */
    private function error($field, &$bind)
    {
        $label = (isset($this->label[$field]) && !empty($this->label[$field])) ? $this->label[$field] : $field;
        $this->errorMsg[$field][] = vsprintf(str_replace('{field}', $label, (isset($bind['message']) ? $bind['message'] : self::$errorTip[strtolower($bind['rule'])])), $bind['params']);
    }

    /**
     * 设置字段显示别名
     *
     * @param string|array $label
     *
     * @return $this
     */
    public function label($label)
    {
        if (is_array($label)) {
            $this->label = array_merge($this->label, $label);
        } else {
            $this->label[$this->dateBindRule[count($this->dateBindRule) - 1]['field'][0]] = $label;
        }

        return $this;
    }

    /**
     * 获取所有错误信息
     *
     * @param int $format 返回的格式 0返回数组，1返回json,2返回字符串
     * @param string $delimiter format为2时分隔符
     * @return array|string
     */
    public function getErrors($format = 0, $delimiter = ', ')
    {
        switch ($format) {
            case 1:
                return json_encode($this->errorMsg, JSON_UNESCAPED_UNICODE);
            case 2:
                $return = '';
                foreach ($this->errorMsg as $val) {
                    $return .= ($return == '' ? '' : $delimiter) . implode($delimiter, $val);
                }
                return $return;
        }
        return $this->errorMsg;
    }

    /**
     * 数据基础验证-是否必须填写的参数
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isRequire($value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    /**
     * 数据基础验证-是否为字符串参数
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isString($value)
    {
        return is_string($value);
    }

    /**
     * 数据基础验证-是否大于
     *
     * @param int $value 要比较的值
     * @param int $max 要大于的长度
     *
     * @return bool
     */
    public static function isGt($value, $max)
    {
        is_array($max) && $max = $max[0];
        if (!is_numeric($value)) {
            return false;
        } elseif (function_exists('bccomp')) {
            return bccomp($value, $max, 14) == 1;
        } else {
            return $value > $max;
        }
    }

    /**
     * 数据基础验证-是否小于
     *
     * @param int $value 要比较的值
     * @param int $min 要小于的长度
     *
     * @return bool
     */
    public static function isLt($value, $min)
    {
        is_array($min) && $min = $min[0];
        if (!is_numeric($value)) {
            return false;
        } elseif (function_exists('bccomp')) {
            return bccomp($min, $value, 14) == 1;
        } else {
            return $value < $min;
        }
    }

    /**
     * 数据基础验证-是否大于等于
     *
     * @param int $value 要比较的值
     * @param int $max 要大于的长度
     *
     * @return bool
     */
    public static function isGte($value, $max)
    {
        is_array($max) && $max = $max[0];
        if (!is_numeric($value)) {
            return false;
        } else {
            return $value >= $max;
        }
    }

    /**
     * 数据基础验证-是否小于等于
     *
     * @param int $value 要比较的值
     * @param int $min 要小于的长度
     *
     * @return bool
     */
    public static function isLte($value, $min)
    {
        is_array($min) && $min = $min[0];
        if (!is_numeric($value)) {
            return false;
        } else {
            return $value <= $min;
        }
    }

    /**
     * 数据基础验证-数字的值是否在区间内
     *
     * @param string $value 字符串
     * @param int $start 起始数字
     * @param int $end 结束数字
     *
     * @return bool
     */
    public static function isBetween($value, $start, $end)
    {
        if (is_array($start)) {
            $end = $start[1];
            $start = $start[0];
        }

        if ($value > $end || $value < $start) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 数据基础验证-字符串长度是否大于
     *
     * @param string $value 字符串
     * @param int $max 要大于的长度
     *
     * @return bool
     */
    public static function isLengthGt($value, $max)
    {
        $value = trim($value);
        if (!is_string($value)) {
            return false;
        }
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        is_array($max) && $max = $max[0];

        if ($max != 0 && $length <= $max) return false;
        return true;
    }


    /**
     * 数据基础验证-字符串长度是否小于
     *
     * @param string $value 字符串
     * @param int $min 要小于的长度
     *
     * @return bool
     */
    public static function isLengthLt($value, $min)
    {
        $value = trim($value);
        if (!is_string($value)) {
            return false;
        }
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        is_array($min) && $min = $min[0];

        if ($min != 0 && $length >= $min) return false;
        return true;
    }

    /**
     * 长度是否在某区间内(包含边界)
     *
     * @param string $value 字符串
     * @param int $min 要小于等于的长度
     * @param int $max 要大于等于的长度
     *
     * @return bool
     */
    public static function isLengthBetween($value, $min, $max)
    {
        if (is_array($min)) {
            $max = $min[1];
            $min = $min[0];
        }

        if (self::isLengthGte($value, $min) && self::isLengthLte($value, $max)) {
            return true;
        }

        return false;
    }

    /**
     * 数据基础验证-字符串长度是否大于等于
     *
     * @param string $value 字符串
     * @param int $max 要大于的长度
     *
     * @return bool
     */
    public static function isLengthGte($value, $max)
    {
        is_array($max) && $max = $max[0];
        return self::isLength($value, $max);
    }


    /**
     * 数据基础验证-字符串长度是否小于等于
     *
     * @param string $value 字符串
     * @param int $min 要小于的长度
     *
     * @return bool
     */
    public static function isLengthLte($value, $min)
    {
        is_array($min) && $min = $min[0];
        return self::isLength($value, 0, $min);
    }

    /**
     * 数据基础验证-判断数据是否在数组中
     *
     * @param string $value 字符串
     * @param array $array 比较的数组
     *
     * @return bool
     */
    public static function isIn($value, $array)
    {
        is_array($array[0]) && $array = $array[0];
        return in_array($value, $array);
    }

    /**
     * 数据基础验证-判断数据是否在数组中
     *
     * @param string $value 字符串
     * @param array $array 比较的数组
     *
     * @return bool
     */
    public static function isNotIn($value, $array)
    {
        is_array($array[0]) && $array = $array[0];
        return !in_array($value, $array);
    }

    /**
     * 数据基础验证-检测字符串长度
     *
     * @param  string $value 需要验证的值
     * @param  int $min 字符串最小长度
     * @param  int $max 字符串最大长度
     *
     * @return bool
     */
    public static function isLength($value, $min = 0, $max = 0)
    {
        $value = trim($value);
        if (!is_string($value)) {
            return false;
        }
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

        if (is_array($min)) {
            $max = $min[1];
            $min = $min[0];
        }
        if ($min != 0 && $length < $min) return false;
        if ($max != 0 && $length > $max) return false;
        return true;
    }

    /**
     * 数据基础验证-是否是空字符串
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isEmpty($value)
    {
        if (empty($value)) return true;
        return false;
    }

    /**
     * 验证两个字段相等
     *
     * @param string $compareField
     * @param string $field
     *
     * @return bool
     */
    protected function isEquals($value, $compareField, $field)
    {
        is_array($compareField) && $compareField = $compareField[0];
        return isset($this->data[$field]) && isset($this->data[$compareField]) && $this->data[$field] == $this->data[$compareField];
    }

    /**
     * 验证两个字段不等
     *
     * @param string $compareField
     * @param string $field
     *
     * @return bool
     */
    protected function isDifferent($value, $compareField, $field)
    {
        is_array($compareField) && $compareField = $compareField[0];
        return isset($this->data[$field]) && isset($this->data[$compareField]) && $this->data[$field] != $this->data[$compareField];
    }

    /**
     * 数据基础验证-检测数组，数组为空时候也返回false
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isArr($value)
    {
        if (!is_array($value) || empty($value)) {
            return false;
        }
        return true;
    }

    /**
     * 数据基础验证-是否是Email 验证：xxx@qq.com
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isEmail($value)
    {
        return filter_var($value, \FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 数据基础验证-是否是IP
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isIp($value)
    {
        return filter_var($value, \FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 数据基础验证-是否是数字类型
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isNumber($value)
    {
        return is_numeric($value);
    }

    /**
     * 数据基础验证-是否是整型
     *
     * @param  int $value 需要验证的值
     *
     * @return bool
     */
    public static function isInt($value)
    {
        return filter_var($value, \FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 数据基础验证-是否是布尔类型
     *
     * @param  int $value 需要验证的值
     *
     * @return bool
     */
    public static function isBool($value)
    {
        return (is_bool($value)) ? true : false;
    }

    /**
     * 数据基础验证-是否是身份证
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isCard($value)
    {
        return preg_match("/^(\d{15}|\d{17}[\dx])$/i", $value);
    }

    /**
     * 数据基础验证-是否是移动电话 验证：1385810XXXX
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isMobile($value)
    {
        return preg_match('/^[+86]?1[3546789][0-9]{9}$/', trim($value));
    }

    /**
     * 数据基础验证-是否是电话 验证：0571-xxxxxxxx
     *
     * @param  string $value 需要验证的值
     * @return bool
     */
    public static function isPhone($value)
    {
        return preg_match('/^[0-9]{3,4}[\-]?[0-9]{7,8}$/', trim($value));
    }

    /**
     * 数据基础验证-是否是URL 验证：http://www.baidu.com
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isUrl($value)
    {
        return filter_var($value, \FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 数据基础验证-是否是邮政编码 验证：311100
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isZip($value)
    {
        return preg_match('/^[1-9]\d{5}$/', trim($value));
    }

    /**
     * 数据基础验证-是否是QQ
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isQq($value)
    {
        return preg_match('/^[1-9]\d{4,12}$/', trim($value));
    }

    /**
     * 数据基础验证-是否是英文字母
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isEnglish($value)
    {
        return preg_match('/^[A-Za-z]+$/', trim($value));
    }

    /**
     * 数据基础验证-是否是中文
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public static function isChinese($value)
    {
        return preg_match("/^([\xE4-\xE9][\x80-\xBF][\x80-\xBF])+$/", trim($value));
    }


    /**
     * 检查是否是安全的账号
     *
     * @param string $value
     *
     * @return bool
     */
    public static function isSafeAccount($value)
    {
        return preg_match("/^[a-zA-Z]{1}[a-zA-Z0-9_\.]{3,31}$/", $value);
    }

    /**
     * 检查是否是安全的昵称
     *
     * @param string $value
     *
     * @return bool
     */
    public static function isSafeNickname($value)
    {
        return preg_match("/^[-\x{4e00}-\x{9fa5}a-zA-Z0-9_\.]{2,10}$/u", $value);
    }

    /**
     * 检查是否是安全的密码
     *
     * @param string $str
     *
     * @return bool
     */
    public static function isSafePassword($str)
    {
        if (preg_match('/[\x80-\xff]./', $str) || preg_match('/\'|"|\"/', $str) || strlen($str) < 6 || strlen($str) > 20) {
            return false;
        }
        return true;
    }

    /**
     * 检查是否是正确的标识符
     *
     * @param string $value 以字母或下划线开始，后面跟着任何字母，数字或下划线。
     *
     * @return mixed
     */
    public static function isIdentifier($value)
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', trim($value));
    }
}
