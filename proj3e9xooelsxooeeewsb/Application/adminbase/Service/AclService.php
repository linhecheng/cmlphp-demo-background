<?php namespace adminbase\Service;

use adminbase\Model\Acl\UsersModel;
use Cml\Config;
use Cml\Plugin;
use Cml\Service;
use Cml\Vendor\Acl;
use Cml\View;

class AclService extends Service
{
    /**
     * 显示无权限页
     */
    public static function noPermission()
    {
        Plugin::hook('admin_not_acl');
        Config::set('html_theme', 'kit_admin');
        View::getEngine()
            ->assign('code', '403')
            ->assign('title', '无权限')
            ->assign('msg', '很抱歉你没有当前模块的操作权限！')
            ->display('Public/error', 'adminbase');
    }

    /**
     * 判断当前登录的用户是否有操作某用户的权限
     *
     * @param int $opUserId
     *
     * @return bool
     */
    public static function currentLoginUserIsHadPermisionToOpUser($opUserId = 0)
    {
        if (Acl::isSuperUser()) {
            return true;
        }

        if (Config::get('administratorid') === intval($opUserId)) {
            return false;
        }

        $currentLoginUser = Acl::getLoginInfo();

        $opUserInfo = UsersModel::getInstance()->getByColumn($opUserId, 'id');

        if (!$opUserInfo['groupid']) {//被操作的用户不属于任何用户组
            return true;
        }
        $opUserGroupIds = array_filter(explode(Acl::getMultiGroupDeper(), trim($opUserInfo['groupid'])), function ($item) {
            return !empty($item);
        });

        sort($currentLoginUser['groupid']);
        sort($opUserGroupIds);

        if ($currentLoginUser['groupid'][0] <= $opUserGroupIds[0]) {
            return true; //只要当前登录用户的最小的用户组比被操作的用户的用户组小即他拥有更大的权限
        }
        /* foreach($currentLoginUser['groupid'] as $cgid) {
             foreach($opUserGroupIds as $ogid) {
                 if ($cgid <= $ogid) { //只要当前登录用户的管理组有一个比该用户的用户组小即他拥有更大的权限
                     return true;
                 }
             }
         }*/
        return false;
    }

    /**
     * 判断当前登录的用户是否有操作某用户组的权限
     *
     * @param int $opGroupId
     *
     * @return bool
     */
    public static function currentLoginUserIsHadPermisionToOpGroup($opGroupId = 0)
    {
        if (Acl::isSuperUser()) {
            return true;
        }
        $currentLoginUser = Acl::getLoginInfo();

        foreach ($currentLoginUser['groupid'] as $cgid) {
            if ($cgid <= $opGroupId) { //只要当前登录用户的管理组有一个比该用户组小即他拥有更大的权限
                return true;
            }
        }
        return false;
    }

}