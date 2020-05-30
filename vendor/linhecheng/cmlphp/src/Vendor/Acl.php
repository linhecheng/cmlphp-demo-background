<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-11 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 权限控制类
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;
use Cml\Config;
use Cml\Encry;
use Cml\Http\Cookie;
use Cml\Model;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

/**
 * 权限控制类
 *
 * 对方法注释 @noacl 则不检查该方法的权限
 * 对方法注释 @acljump web/User/add 则将当前方法的权限检查跳转为检查 web/User/add方法的权限
 * 加到normal.php配置中
 * //权限控制配置
 * 'administratorid'=>'1', //超管理员id
 *
 * 建库语句
 *
 * CREATE TABLE `pr_admin_app` (
 * `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
 * `name` varchar(255) NOT NULL DEFAULT '' COMMENT '应用名',
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE `pr_admin_access` (
 * `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
 * `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属用户权限ID',
 * `groupid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属群组权限ID',
 * `menuid` int(11) NOT NULL DEFAULT '0' COMMENT '权限模块ID',
 * PRIMARY KEY (`id`),
 * KEY `idx_userid` (`userid`),
 * KEY `idx_groupid` (`groupid`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='用户或者用户组权限记录';
 *
 * CREATE TABLE `pr_admin_groups` (
 * `id` smallint(3) unsigned NOT NULL AUTO_INCREMENT,
 * `name` varchar(150) NOT NULL DEFAULT '' COMMENT '用户组名',
 * `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1正常，0删除',
 * `remark` text NOT NULL COMMENT '备注',
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE `pr_admin_menus` (
 * `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
 * `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父模块ID编号 0则为顶级模块',
 * `title` varchar(64) NOT NULL DEFAULT '' COMMENT '标题',
 * `url` varchar(64) NOT NULL DEFAULT '' COMMENT 'url路径',
 * `params` varchar(64) NOT NULL DEFAULT '' COMMENT 'url参数',
 * `isshow` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
 * `sort` smallint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序倒序',
 *  `app` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '菜单所属app，对应app表中的主键',
 * PRIMARY KEY (`id`),
 * KEY `idex_pid` (`pid`),
 * KEY `idex_order` (`sort`),
 * KEY `idx_action` (`url`),
 * KEY `idx_app` (`app`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='权限模块信息表';
 *
 * CREATE TABLE `pr_admin_users` (
 * `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
 * `groupid` varchar(255) NOT NULL DEFAULT '0' COMMENT '用户组id',
 * `username` varchar(40) NOT NULL DEFAULT '' COMMENT '用户名',
 * `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
 * `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
 * `lastlogin` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
 * `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
 * `stime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
 * `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1正常，0删除',
 * `remark` text NOT NULL,
 * `from_type` tinyint(3) unsigned DEFAULT '1' COMMENT '用户类型。1为系统用户。',
 * PRIMARY KEY (`id`),
 * UNIQUE KEY `username` (`username`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
 *
 * @package Cml\Vendor
 */
class Acl
{
    /**
     * 加密用的混淆key
     *
     * @var string
     */
    private static $encryptKey = 'pnnle-oienngls-llentne-lnegxe';

    /**
     * 定义表名
     *
     * @var array
     */
    private static $tables = [
        'access' => 'access',
        'groups' => 'groups',
        'menus' => 'menus',
        'users' => 'users',
    ];

    /**
     * 有权限的时候保存权限的显示名称用于记录log
     *
     * @var array
     */
    public static $aclNames = [];

    /**
     * 当前登录的用户信息
     *
     * @var null
     */
    public static $authUser = null;

    /**
     * 单点登录标识
     *
     * @var string
     */
    private static $ssoSign = '';

    /**
     * 单个用户归属多个用户组时多个id在mysql中的分隔符
     *
     * @var string
     */
    private static $multiGroupDeper = '|';

    /**
     * 设置权限除了检查url之外的参数。如当前请求的url为web/Index/index  这边传参?id=1则检查权限的时候是检查有无url为web/Index/index?id=1的菜单
     *
     * @var string
     */
    private static $otherAclParams = '';

    /**
     * 设置单个用户归属多个用户组时多个id在mysql中的分隔符
     *
     * @param string $deper 分隔符
     */
    public static function setMultiGroupDeper($deper = '|')
    {
        self::$multiGroupDeper = $deper;
    }

    /**
     * 获取单个用户归属多个用户组时多个id在mysql中的分隔符
     *
     * @return string
     */
    public static function getMultiGroupDeper()
    {
        return self::$multiGroupDeper;
    }

    /**
     * 设置权限除了检查url之外的params参数。如当前请求的url为web/Index/index  这边传参?id=1则检查权限的时候是检查url为web/Index/index并且params字段为?id=1的菜单
     *
     * @param string $otherAclParams
     */
    public static function setOtherAclParams($otherAclParams = '')
    {
        self::$otherAclParams = $otherAclParams;
    }

    /**
     * 设置加密用的混淆key Cookie::set本身有一重加密 这里再加一重
     *
     * @param string $key
     */
    public static function setEncryptKey($key)
    {
        self::$encryptKey = $key;
    }

    /**
     * 自定义表名
     *
     * @param string|array $type
     * @param string $tableName
     */
    public static function setTableName($type = 'access', $tableName = 'access')
    {
        if (is_array($type)) {
            self::$tables = array_merge(self::$tables, $type);
        } else {
            self::$tables[$type] = $tableName;
        }
    }

    /**
     * 获取表名
     * @param string $type
     *
     * @return mixed
     */
    public static function getTableName($type = 'access')
    {
        if (isset(self::$tables[$type])) {
            return self::$tables[$type];
        } else {
            throw new InvalidArgumentException($type);
        }
    }

    /**
     * 保存当前登录用户的信息
     *
     * @param int $uid 用户id
     * @param bool $sso 是否为单点登录，即踢除其它登录用户
     * @param int $cookieExpire 登录的过期时间，为0则默认保持到浏览器关闭，> 0的值为登录有效期的秒数。默认为0
     * @param int $notOperationAutoLogin 当$cookieExpire设置为0时，这个值为用户多久不操作则自动退出。默认为1个小时
     * @param string $cookiePath path
     * @param string $cookieDomain domain
     */
    public static function setLoginStatus($uid, $sso = true, $cookieExpire = 0, $notOperationAutoLogin = 3600, $cookiePath = '', $cookieDomain = '')
    {
        $cookieExpire > 0 && $notOperationAutoLogin = 0;
        $user = [
            'uid' => $uid,
            'expire' => $notOperationAutoLogin > 0 ? Cml::$nowTime + $notOperationAutoLogin : 0,
            'ssosign' => $sso ? (string)Cml::$nowMicroTime : self::$ssoSign
        ];
        $notOperationAutoLogin > 0 && $user['not_op'] = $notOperationAutoLogin;

        //Cookie::set本身有一重加密 这里再加一重
        if ($sso) {
            Model::getInstance()->cache()->set("SSOSingleSignOn{$uid}", $user['ssosign'], 86400 + $cookieExpire);
        } else {
            //如果是刚刚从要单点切换成不要单点。这边要把ssosign置为cache中的
            empty($user['ssosign']) && $user['ssosign'] = Model::getInstance()->cache()->get("SSOSingleSignOn{$uid}");
        }
        Cookie::set(Config::get('userauthid'), Encry::encrypt(json_encode($user, JSON_UNESCAPED_UNICODE), self::$encryptKey), $cookieExpire, $cookiePath, $cookieDomain);
    }

    /**
     * 获取当前登录用户的信息
     *
     * @return array
     */
    public static function getLoginInfo()
    {
        if (is_null(self::$authUser)) {
            //Cookie::get本身有一重解密 这里解第二重
            self::$authUser = Encry::decrypt(Cookie::get(Config::get('userauthid')), self::$encryptKey);
            empty(self::$authUser) || self::$authUser = json_decode(self::$authUser, true);

            if (
                empty(self::$authUser)
                || (self::$authUser['expire'] > 0 && self::$authUser['expire'] < Cml::$nowTime)
                || self::$authUser['ssosign'] != Model::getInstance()->cache()
                    ->get("SSOSingleSignOn" . self::$authUser['uid'])
            ) {
                self::$authUser = false;
                self::$ssoSign = '';
            } else {
                self::$ssoSign = self::$authUser['ssosign'];

                $user = Model::getInstance(self::$tables['users'])->where('status', 1)->getByColumn(self::$authUser['uid']);
                if (empty($user)) {
                    self::$authUser = false;
                } else {
                    $authUser = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'nickname' => $user['nickname'],
                        'groupid' => array_values(array_filter(explode(self::$multiGroupDeper, trim($user['groupid'], self::$multiGroupDeper)), function ($v) {
                            return !empty($v);
                        })),
                        'from_type' => $user['from_type']
                    ];

                    $authUser['groupname'] = Model::getInstance(self::$tables['groups'])
                        ->whereIn('id', $authUser['groupid'])
                        ->where('status', 1)
                        ->pluck('name');
                    $authUser['groupname'] = implode(',', $authUser['groupname']);
                    //有操作登录超时时间重新设置为expire时间
                    if (self::$authUser['expire'] > 0 && (
                            (self::$authUser['expire'] - Cml::$nowTime) < (self::$authUser['not_op'] / 2)
                        )
                    ) {
                        self::setLoginStatus($user['id'], false, 0, self::$authUser['not_op']);
                    }

                    unset($user, $group);
                    self::$authUser = $authUser;
                }
            }
        }
        return self::$authUser;
    }


    /**
     * 检查对应的权限
     *
     * @param object|string $controller 传入控制器实例对象，用来判断当前访问的方法是不是要跳过权限检查。
     * 如当前访问的方法为web/User/list则传入new \web\Controller\User()获得的实例。最常用的是在基础控制器的init方法或构造方法里传入$this。
     * 传入字符串如web/User/list时会自动 new \web\Controller\User()获取实例用于判断
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function checkAcl($controller)
    {
        $authInfo = self::getLoginInfo();
        if (!$authInfo) return false; //登录超时

        //当前登录用户是否为超级管理员
        if (self::isSuperUser()) {
            return true;
        }

        $checkUrl = Cml::getContainer()->make('cml_route')->getFullPathNotContainSubDir();
        $checkAction = Cml::getContainer()->make('cml_route')->getActionName();

        if (is_string($controller)) {
            $checkUrl = trim($controller, '/\\');
            $controller = str_replace('/', '\\', $checkUrl);
            $actionPosition = strrpos($controller, '\\');
            $checkAction = substr($controller, $actionPosition + 1);
            $offset = $appPosition = 0;
            for ($i = 0; $i < Config::get('route_app_hierarchy', 1); $i++) {
                $appPosition = strpos($controller, '\\', $offset);
                $offset = $appPosition + 1;
            }
            $appPosition = $offset - 1;

            $subString = substr($controller, 0, $appPosition) . '\\' . Cml::getApplicationDir('app_controller_path_name') . substr($controller, $appPosition, $actionPosition - $appPosition);
            $controller = "\\{$subString}" . Config::get('controller_suffix');

            if (class_exists($controller)) {
                $controller = new $controller;
            } else {
                return false;
            }
        }

        $checkUrl = ltrim(str_replace('\\', '/', $checkUrl), '/');
        $origUrl = $checkUrl;

        if (is_object($controller)) {
            //判断是否有标识 @noacl 不检查权限
            $reflection = new ReflectionClass($controller);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($method->name == $checkAction) {
                    $annotation = $method->getDocComment();
                    if (strpos($annotation, '@noacl') !== false) {
                        return true;
                    }

                    $checkUrlArray = [];

                    if (preg_match('/@acljump([^\n]+)/i', $annotation, $aclJump)) {
                        if (isset($aclJump[1]) && $aclJump[1]) {
                            $aclJump[1] = explode('|', $aclJump[1]);
                            foreach ($aclJump[1] as $val) {
                                $val = trim($val);
                                substr($val, 0, 3) == '../' && $val = '../' . $val;
                                if ($times = preg_match_all('#\./#i', $val)) {
                                    $origUrlArray = explode('/', $origUrl);
                                    $val = explode('./', $val);

                                    for ($i = 0; $i < $times; $i++) {
                                        array_pop($origUrlArray);
                                        array_shift($val);
                                    }
                                    $val = implode('/', array_merge($origUrlArray, $val));
                                }
                                $val && $checkUrlArray[] = ltrim(str_replace('\\', '/', trim($val)), '/') . self::$otherAclParams;
                            }
                        }
                        empty($checkUrlArray) || $checkUrl = $checkUrlArray;
                    }
                }
            }
        }

        $acl = Model::getInstance()->table([self::$tables['access'] => 'a'])
            ->join([self::$tables['menus'] => 'm'], 'a.menuid=m.id')
            ->_and(function ($model) use ($authInfo) {
                /**
                 * @var Model $model
                 */
                $model->whereIn('a.groupid', $authInfo['groupid'])
                    ->_or()
                    ->where('a.userid', $authInfo['id']);
            })->when(self::$otherAclParams, function ($model) {
                /**
                 * @var Model $model
                 */
                $model->where('m.params', self::$otherAclParams);
            })->when(is_array($checkUrl), function ($model) use ($checkUrl) {
                /**
                 * @var Model $model
                 */
                $model->whereIn('m.url', $checkUrl);
            }, function ($model) use ($checkUrl) {
                /**
                 * @var Model $model
                 */
                $model->where('m.url', $checkUrl);
            })->count('1');
        return $acl > 0;
    }

    /**
     * 获取有权限的菜单列表
     *
     * @param bool $format 是否格式化返回
     * @param string $columns 要额外获取的字段
     *
     * @return array
     */
    public static function getMenus($format = true, $columns = '')
    {
        $res = [];
        $authInfo = self::getLoginInfo();
        if (!$authInfo) { //登录超时
            return $res;
        }

        $result = Model::getInstance()->table([self::$tables['menus'] => 'm'])
            ->columns(['distinct m.id, m.sort', 'm.pid', 'm.title', 'm.url', 'm.params' . ($columns ? " ,{$columns}" : '')])
            ->when(!self::isSuperUser(), function ($model) use ($authInfo) {//当前登录用户是否为超级管理员
                /**
                 * @var Model $model
                 */
                $model->join([self::$tables['access'] => 'a'], 'a.menuid=m.id')
                    ->_and(function ($model) use ($authInfo) {
                        /**
                         * @var Model $model
                         */
                        $model->whereIn('a.groupid', $authInfo['groupid'])
                            ->_or()
                            ->where('a.userid', $authInfo['id']);
                    });
            })->where('m.isshow', 1)
            ->orderBy('m.sort', 'DESC')
            ->orderBy('m.id', 'ASC')
            ->select(0, 5000);

        $res = $format ? Tree::getTreeNoFormat($result, 0) : $result;
        return $res;
    }

    /**
     * 登出
     *
     */
    public static function logout()
    {
        $user = Acl::getLoginInfo();
        $user && Model::getInstance()->cache()->delete("SSOSingleSignOn" . $user['id']);
        Cookie::delete(Config::get('userauthid'));
    }

    /**
     * 判断当前登录用户是否为超级管理员
     *
     * @return bool
     */
    public static function isSuperUser()
    {
        $authInfo = self::getLoginInfo();
        if (!$authInfo) {//登录超时
            return false;
        }
        $admin = Config::get('administratorid');
        return is_array($admin) ? in_array($authInfo['id'], $admin) : ($authInfo['id'] === $admin);
    }
}
