<?php
/**
 * 权限管理
 *
 */

namespace adminbase\Controller\Acl;

use adminbase\Model\Acl\AccessModel;
use adminbase\Model\Acl\AppModel;
use adminbase\Service\AclService;
use adminbase\Service\System\LogService;
use Cml\Http\Input;
use Cml\Vendor\Acl;
use adminbase\Controller\CommonController;
use adminbase\Model\Acl\GroupsModel;
use adminbase\Model\Acl\MenusModel;
use adminbase\Model\Acl\UsersModel;
use Cml\View;

class AclController extends CommonController
{
    /**
     * 用户授权
     *
     */
    public function user()
    {
        $this->showAclPage(1);
    }

    /**
     * 用户组授权
     *
     */
    public function group()
    {
        $this->showAclPage(2);
    }

    /**
     * 显示授权页面
     *
     * @param int $type 1 用户授权 2用户组授权
     */
    private function showAclPage($type = 1)
    {
        $id = Input::getInt('id');
        $id < 1 && exit('非法请求');

        $dataModel = $type === 1 ? new UsersModel() : new GroupsModel();

        $isHadPermission = $type == 1 ?
            AclService::currentLoginUserIsHadPermisionToOpUser($id)
            :
            AclService::currentLoginUserIsHadPermisionToOpGroup($id);
        $isHadPermission || exit('您所有的用户组没有操作该用户[组]的权限!');

        $menusModel = new MenusModel();

        //获取所有菜单
        $menus = $menusModel->orderBy('sort', 'desc')->getList(0, 5000, 'asc');
        //获取已有权限
        $accessModel = new AccessModel();
        $hadAccessMenusId = $accessModel->getAccessArrByField($id, $type === 1 ? 'userid' : 'groupid');
        $hadAccessMenus = [];
        foreach ($hadAccessMenusId as $val) {
            $hadAccessMenus[] = $val['menuid'];
        }

        //授权的时候该管理员只能看到自己有的权限列表
        $currentLoginUsersHadAccessList = Acl::isSuperUser() ? [] : $this->getCurrentLoginUsersAcl();

        $app = AppModel::getInstance()->getList(0, 5000, 'ASC');
        array_unshift($app, ['id' => 0, 'name' => '公用(公用下的菜单会显示在所有app下)']);

        foreach ($menus as $key => &$val) {
            if (false === Acl::isSuperUser() && !in_array($val['id'], $currentLoginUsersHadAccessList)) {
                unset($menus[$key]);
                continue;
            }
            unset($val['url']);
            $val['open'] = $val['checked'] = in_array($val['id'], $hadAccessMenus);
            foreach ($app as $item) {
                if ($val['pid'] == 0 && $val['app'] == $item['id']) {
                    $val['pid'] = $item['id'] + 100000000;
                }
            }
        }
        array_walk($app, function ($item) use (&$menus) {
            $menus[] = [
                'id' => $item['id'] + 100000000,
                'title' => $item['name'],
                'pid' => 0,
                'open' => true,
                'nocheck' => true
            ];
        });

        View::getEngine('Html')
            ->assign('type', $type)
            ->assignByRef('item', $dataModel->getByColumn($id))
            ->assignByRef('menus', array_values($menus))
            ->display('Acl/Acl/acl');

    }

    /**
     * 保存授权信息
     *
     * @acljump adminbase/Acl/Acl/group|adminbase/Acl/Acl/user
     */
    public function save()
    {
        $menuIds = trim(Input::postString('ids'), ',');
        empty($menuIds) || $menuIds = explode(',', $menuIds);
        $id = Input::postInt('id');
        $type = Input::getInt('type', 0);

        $isHadPermission = $type == 1 ?
            AclService::currentLoginUserIsHadPermisionToOpUser($id)
            :
            AclService::currentLoginUserIsHadPermisionToOpGroup($id);
        $isHadPermission || $this->renderJson(10100);//您所有的用户组没有操作该用户[组]的权限!

        if (!in_array($type, [1, 2]) || $id < 1) {
            $this->renderJson(3);//没修改
        }
        $field = $type === 1 ? 'userid' : 'groupid';

        LogService::addActionLog("修改了[{$field}:{$id}]的权限信息!");

        //授权的时候该管理员只能授权自己有的权限列表
        $currentLoginUsersHadAccessList = Acl::isSuperUser() ? [] : $this->getCurrentLoginUsersAcl();

        $aclModel = new AccessModel();
        $aclModel->delByColumn($id, $field);

        foreach ($menuIds as $i) {
            if (false === Acl::isSuperUser() && !in_array($i, $currentLoginUsersHadAccessList)) {
                continue;
            }
            $aclModel->set([
                $field => $id,
                'menuid' => $i
            ]);
        }
        $this->renderJson(0);
    }

    /**
     * 获取当前登录用户所有的权限id
     *
     */
    private function getCurrentLoginUsersAcl()
    {
        $user = Acl::getLoginInfo();
        //获取已有权限
        $accessModel = new AccessModel();
        $hadAccessMenusId = array_merge($accessModel->getAccessArrByField($user['id'], 'userid'), $accessModel->getAccessArrByField($user['groupid'], 'groupid'));

        $return = [];
        foreach ($hadAccessMenusId as $val) {
            $return[] = $val['menuid'];
        }
        return array_unique($return);
    }

}