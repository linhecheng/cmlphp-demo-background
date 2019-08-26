<?php
/**
 * 用户组管理
 *
 * @authon linhecheng
 */

namespace adminbase\Controller\Acl;

use adminbase\Service\AclService;
use adminbase\Service\Builder\Form\Input\Hidden;
use adminbase\Service\Builder\Form\Input\Textarea;
use adminbase\Service\Builder\FormBuildService;
use adminbase\Service\Builder\Grid\Button\Add;
use adminbase\Service\Builder\Grid\Button\Del;
use adminbase\Service\Builder\Grid\Button\Edit;
use adminbase\Service\Builder\Grid\Column\Text;
use adminbase\Service\Builder\Grid\SearchItem;
use adminbase\Service\Builder\GridBuildService;
use adminbase\Service\System\LogService;
use Cml\Config;
use Cml\Http\Input;
use adminbase\Controller\CommonController;
use adminbase\Model\Acl\AccessModel;
use adminbase\Model\Acl\GroupsModel;
use adminbase\Service\SearchService;
use Cml\Plugin;

class GroupsController extends CommonController
{
    //用户组列表
    public function index()
    {
        GridBuildService::create('adminbase/Acl/Groups/ajaxPage', '用户组列表')
            ->addSearchItem(SearchItem::getInstance()->setName('name')->setPlaceholder('请输入用户组名称'))
            ->addTopButton(function () {
                $addBtn = new Add();
                $addBtn->setText('新增')
                    ->setNewPageUrl('adminbase/Acl/Groups/add')
                    ->setNewPageTitle('新增用户组')
                    ->setSaveDataUrl('adminbase/Acl/Groups/save');
                return $addBtn;
            })
            ->addColumn(Text::getInstance()->setText('id')->setDataColumnFieldName('id'))
            ->addColumn(Text::getInstance()->setText('用户组名称')->setDataColumnFieldName('name'))
            ->addColumn(Text::getInstance()->setText('备注')->setDataColumnFieldName('remark'))
            ->addColumn(function () {
                $buttonEdit = new Edit();
                $buttonEdit->setText('编辑')
                    ->setNewPageTitle('编辑用户组')
                    ->setNewPageUrl('adminbase/Acl/Groups/edit')
                    ->setClass('layui-btn-normal')
                    ->setSaveDataUrl('adminbase/Acl/Groups/save');

                $buttonDel = new Del();
                $buttonDel->setText('删除')
                    ->setUrl('adminbase/Acl/Groups/del')
                    ->setClass('layui-btn-danger');

                $buttonAcl = new Add();
                $buttonAcl->setText('授权')
                     ->setNewPageTitle('用户组授权')
                    ->setNewPageUrl('adminbase/Acl/Acl/group')
                    ->setSaveDataUrl('adminbase/Acl/Acl/save/type/2');

                $buttonColumn = new \adminbase\Service\Builder\Grid\Column\Button();
                $buttonColumn->setText('操作')
                    ->setDataColumnFieldName('id')
                    ->addButtons([
                        $buttonEdit,
                        $buttonDel,
                        $buttonAcl
                    ]);
                return $buttonColumn;
            })
            ->display();
    }

    /**
     * ajax请求分页
     *
     * @acljump adminbase/Acl/Groups/index
     */
    public function ajaxPage()
    {
        $groupsModel = new GroupsModel();

        SearchService::processSearch([
            'name' => 'like'
        ], $groupsModel, true);
        $totalCount = $groupsModel->paramsAutoReset(false, true)->getTotalNums();
        $list = $groupsModel->getGroupsList(Config::get('page_num'));

        $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
    }


    /**
     * 新增用户组
     *
     * @param array $group 编辑的时候传入用户组信息
     */
    public function add($group = [])
    {
        $inst = FormBuildService::create($group ? '修改用户组信息' : '新增用户组')
            ->addFormItem(function () {
                $nameInput = new \adminbase\Service\Builder\Form\Input\Text();
                $nameInput->setLabel('用户组名')->setName('name')
                    ->setPlaceholder('请输入用户组名')->setOtherInfo(' lay-verify="required" ');
                return $nameInput;
            })
            ->addFormItem(function () {
                $remarkInput = new Textarea();
                $remarkInput->setLabel('备注')->setName('remark')
                    ->setPlaceholder('请输入备注信息')
                    ->setOptions('adminbase/Public/upload');
                return $remarkInput;
            })
            ->addFormItem(function () {
                $idInput = new Hidden();
                return $idInput->setName('id');
            })
            ->withData($group);

        Plugin::hook('before_add_or_edit_group', $inst, $group);

        $inst->display();
    }

    /**
     * 编辑用户组
     *
     */
    public function edit()
    {
        $id = Input::getInt('id');
        AclService::currentLoginUserIsHadPermisionToOpGroup($id) || $this->renderJson(10100);//您所有的用户组没有操作该用户[组]的权限!

        $this->add(GroupsModel::getInstance()->getByColumn($id));
    }

    /**
     * 保存用户组
     *
     * @acljump adminbase/Acl/Groups/add|adminbase/Acl/Groups/edit
     *
     */
    public function save()
    {
        $data = [];
        $id = Input::postInt('id');

        $data['name'] = Input::postString('name', '');
        $data['remark'] = Input::postString('remark', '');
        $data['name'] || $this->renderJson(2, '用户组名称不能为空!');

        $other = Plugin::hook('before_add_group_save');
        $other && $data = array_merge($data, $other);

        $groupsModel = new GroupsModel();
        if (!$id) {//新增
            $groupsModel->set($data);
        } else {
            AclService::currentLoginUserIsHadPermisionToOpGroup($id) || $this->renderJson(10100);//您所有的用户组没有操作该用户[组]的权限!
            LogService::addActionLog("修改了用户组[{$id}]的信息" . json_encode($data));
            $groupsModel->updateByColumn($id, $data);
        }
        Plugin::hook('after_add_group_save', $id);
        $this->renderJson(0);
    }


    /**
     * 删除用户组
     *
     */
    public function del()
    {
        $id = Input::getInt('id');
        $id < 1 && $this->renderJson(1);

        AclService::currentLoginUserIsHadPermisionToOpGroup($id) || $this->renderJson(10100);//您所有的用户组没有操作该用户[组]的权限!

        $groupsModel = new GroupsModel();
        $id === intval(Config::get('administratorid')) && $this->renderJson(1, '不能删除超管');
        if ($groupsModel->delByColumn($id)) {
            LogService::addActionLog("删除了用户组[{$id}]!");
            //删除对应的权限
            $accessModel = new AccessModel();
            $accessModel->delByColumn($id, 'groupid');
            $this->renderJson(0);
        } else {
            $this->renderJson(1);
        }
    }
}