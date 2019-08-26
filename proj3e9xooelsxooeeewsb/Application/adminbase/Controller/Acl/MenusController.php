<?php
/**
 * 菜单管理
 *
 * @authon linhecheng
 */

namespace adminbase\Controller\Acl;

use adminbase\Model\Acl\AccessModel;
use adminbase\Model\Acl\AppModel;
use adminbase\Service\Builder\Comm\Option;
use adminbase\Service\Builder\Form\Input\Hidden;
use adminbase\Service\Builder\Form\Input\Radio;
use adminbase\Service\Builder\Form\Input\Select;
use adminbase\Service\Builder\Form\Input\Text;
use adminbase\Service\Builder\FormBuildService;
use adminbase\Service\ResponseService;
use adminbase\Service\System\LogService;
use Cml\Http\Input;
use Cml\Http\Request;
use Cml\View;
use adminbase\Controller\CommonController;
use adminbase\Model\Acl\MenusModel;
use Cml\Vendor\Tree;

class MenusController extends CommonController
{
    public function menusList()
    {
        $menus = MenusModel::getInstance()->orderBy('sort', 'desc')->getList(0, 5000, 'asc');
        $menus = Tree::getTreeNoFormat($menus);
        array_walk($menus, function (&$menu) {
            array_walk($menu['sonNode'], function (&$sonMenu) {
                $sonMenu['sonNode'] = array_values($sonMenu['sonNode']);
            });
            $menu['sonNode'] = array_values($menu['sonNode']);
        });

        if (Request::isAjax()) {
            ResponseService::renderJson(0, $menus);
        }

        $app = AppModel::getInstance()->getList(0, 5000, 'ASC');
        array_unshift($app, ['id' => 0, 'name' => '公用<span style="color:red;">(公用下的菜单会显示在所有app下)</span>']);
        View::getEngine()
            ->assignByRef('menus', $menus)
            ->assign('app', $app)
            ->displayWithLayout('Acl/Menus/menusList', 'regional');

    }

    //增加菜单
    public function add($menu = [])
    {
        $pid = $menu ? $menu['pid'] : Input::getInt('pid', 0);
        $app = Input::getInt('app', 0);
        $app = AppModel::getInstance()->getByColumn($menu ? $menu['app'] : $app);
        $parentTree = [
        ];
        $rootOption = new Option();
        $rootOption->setValue(0)->setText($app['name']);
        $parentTree[] = $rootOption;

        $currentAppMenus = MenusModel::getInstance()->where('app', $app['id'])->getListByPaginate(1000000);
        $currentAppMenus = Tree::getTreeNoFormat($currentAppMenus);
        $loopSonMenu = function ($currentAppMenus) use (&$parentTree, $pid, &$loopSonMenu) {
            static $i = 1;
            foreach ($currentAppMenus as $sonMenus) {
                $option = new Option();
                $option->setValue($sonMenus['id'])
                    ->setText(str_pad('', $i * 30, '&nbsp;', STR_PAD_LEFT) . ($sonMenus['sonNode'] ? '' : '|---') . $sonMenus['title'])
                    ->setOtherInfo($sonMenus['id'] == $pid ? ' selected ' : '');
                $parentTree[] = $option;

                if ($sonMenus['sonNode']) {
                    $i++;
                    $loopSonMenu($sonMenus['sonNode']);
                    $i--;
                }
            }
        };
        $loopSonMenu($currentAppMenus);

        $inst = FormBuildService::create($menu ? '修改菜单信息' : '新增菜单')
            ->addFormItem(function () use ($parentTree) {
                $nicknameInput = new Select();
                $nicknameInput->setLabel('父类')->setName('pid')
                    ->setOptions($parentTree);
                return $nicknameInput;
            })
            ->addFormItem(function () use ($parentTree) {
                $nicknameInput = new Text();
                $nicknameInput->setLabel('显示名称')->setName('title')
                    ->setPlaceholder('请输入显示名称')
                    ->setOtherInfo(' lay-verify="required" ');
                return $nicknameInput;
            })
            ->addFormItem(function () use ($parentTree) {
                $nicknameInput = new Text();
                $nicknameInput->setLabel('url')->setName('url')
                    ->setPlaceholder('请输入url')
                    ->setOtherInfo(' lay-verify="required" ');
                return $nicknameInput;
            })
            ->addFormItem(function () use ($parentTree) {
                $nicknameInput = new Text();
                $nicknameInput->setLabel('params')->setName('params')
                    ->setPlaceholder('请输入url参数如id/1');
                return $nicknameInput;
            })
            ->addFormItem(function () use ($parentTree) {
                $nicknameInput = new Radio();
                $nicknameInput->setLabel('是否显示到菜单')->setName('isshow')
                    ->setPlaceholder('是')
                    ->setOptions(1);
                return $nicknameInput;
            })
            ->addFormItem(function () use ($parentTree) {
                $nicknameInput = new Radio();
                $nicknameInput->setLabel('是否显示到菜单')->setName('isshow')
                    ->setPlaceholder('否')
                    ->setOptions(0);
                return $nicknameInput;
            })
            ->addFormItem('排序', 'sort')
            ->addFormItem(function () {
                $idInput = new Hidden();
                return $idInput->setName('app');
            })->addFormItem(function () {
                $idInput = new Hidden();
                return $idInput->setName('id');
            });


        empty($menu) && $menu = [
            'app' => $app['id'],
            'isshow' => 1
        ];

        $inst->withData($menu)
            ->display();
    }

    //编辑菜单
    public function edit()
    {
        $id = Input::getInt('id');
        $id > 0 || exit('非法操作');
        $this->add(MenusModel::getInstance()->getByColumn($id));
    }

    /**
     * 保存菜单
     *
     * @acljump adminbase/Acl/Menus/add|adminbase/Acl/Menus/edit
     *
     */
    public function save()
    {
        $data = [];
        $id = Input::postInt('id');
        $data['pid'] = Input::postInt('pid', 0);
        $data['title'] = Input::postString('title');
        $data['url'] = trim(Input::postString('url'), '/');
        $data['params'] = Input::postString('params', '');
        $data['isshow'] = Input::postInt('isshow');
        $data['sort'] = Input::postInt('sort', 0);
        $app = Input::postInt('app');
        is_null($app) || $data['app'] = $app;

        $menuModel = new MenusModel();
        if (!$id) {//新增
            $res = $menuModel->set($data);
        } else {
            LogService::addActionLog("修改了菜单[{$id}]的信息" . json_encode($data, JSON_UNESCAPED_UNICODE));

            $res = $menuModel->updateByColumn($id, $data);
        }
        $this->renderJson($res ? 0 : 1);
    }

    /**
     * 删除菜单
     *
     */
    public function del()
    {
        $id = Input::getInt('id');
        $id < 1 && $this->renderJson(1);
        $menuModel = new MenusModel();
        if ($menuModel->hasSonMenus($id)) {
            $this->renderJson(1, '该菜单下有子菜单不能删除！');
        } else {
            LogService::addActionLog("删除了菜单[{$id}]!");
            $res = 1;
            if ($menuModel->delByColumn($id)) {
                $res = 0;
                AccessModel::getInstance()->delByColumn($id, 'menuid');
            }
            $this->renderJson($res);
        }
    }
}