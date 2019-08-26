<?php
/* * *********************************************************
* 应用管理
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/5/08 10:33
* *********************************************************** */

namespace adminbase\Controller\Acl;

use adminbase\Model\Acl\AppModel;
use adminbase\Model\Acl\MenusModel;
use adminbase\Service\Builder\Form\Input\Hidden;
use adminbase\Service\Builder\FormBuildService;
use adminbase\Service\Builder\Grid\Button\Add;
use adminbase\Service\Builder\Grid\Button\Del;
use adminbase\Service\Builder\Grid\Button\Edit;
use adminbase\Service\Builder\Grid\Column\Text;
use adminbase\Service\Builder\GridBuildService;
use adminbase\Service\System\LogService;
use Cml\Config;
use Cml\Http\Input;
use adminbase\Controller\CommonController;

class AppController extends CommonController
{
    //应用列表
    public function index()
    {
        GridBuildService::create('adminbase/Acl/App/ajaxPage', '应用列表')
            ->addTopButton(function () {
                $addBtn = new Add();
                $addBtn->setText('新增')
                    ->setNewPageTitle('新增应用')
                    ->setNewPageUrl('adminbase/Acl/App/add')
                    ->setSaveDataUrl('adminbase/Acl/App/save');
                return $addBtn;
            })
            ->addColumn(Text::getInstance()->setText('id')->setDataColumnFieldName('id'))
            ->addColumn(Text::getInstance()->setText('应用名称')->setDataColumnFieldName('name'))
            ->addColumn(function () {
                $buttonEdit = new Edit();
                $buttonEdit->setText('编辑')
                    ->setNewPageTitle('编辑应用')
                    ->setNewPageUrl('adminbase/Acl/App/edit')
                    ->setClass('layui-btn-normal')
                    ->setSaveDataUrl('adminbase/Acl/App/save');

                $buttonDel = new Del();
                $buttonDel->setText('删除')
                    ->setUrl('adminbase/Acl/App/del')
                    ->setClass('layui-btn-danger');

                $buttonColumn = new \adminbase\Service\Builder\Grid\Column\Button();
                $buttonColumn->setText('操作')
                    ->setDataColumnFieldName('id')
                    ->addButtons([
                        $buttonEdit,
                        $buttonDel
                    ]);
                return $buttonColumn;
            })
            ->display();
    }

    /**
     * ajax请求分页
     *
     * @acljump adminbase/Acl/App/index
     */
    public function ajaxPage()
    {
        $appModel = new AppModel();

        $totalCount = $appModel->paramsAutoReset(false, true)->getTotalNums();
        $list = $appModel->getListByPaginate(Config::get('page_num'));

        $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
    }


    /**
     * 新增应用
     *
     * @param array $app 编辑的时候传入应用信息
     */
    public function add($app = [])
    {
        $inst = FormBuildService::create($app ? '修改应用信息' : '新增应用')
            ->addFormItem(function () {
                $nameInput = new \adminbase\Service\Builder\Form\Input\Text();
                $nameInput->setLabel('应用名')->setName('name')
                    ->setPlaceholder('请输入应用名')
                    ->setOtherInfo(' lay-verify="required" ');
                return $nameInput;
            })
            ->addFormItem(function() {
                $idInput = new Hidden();
                return $idInput->setName('id');
            })
            ->withData($app);

        $inst->display();
    }

    /**
     * 编辑应用
     *
     */
    public function edit()
    {
        $id = Input::getInt('id');

        $this->add(AppModel::getInstance()->getByColumn($id));
    }

    /**
     * 保存应用
     *
     * @acljump adminbase/Acl/App/add|adminbase/Acl/App/edit
     *
     */
    public function save()
    {
        $data = [];
        $id = Input::postInt('id');

        $data['name'] = Input::postString('name', '');
        $data['name'] || $this->renderJson(2, '应用名称不能为空!');

        $appsModel = new AppModel();
        if (!$id) {//新增
            $res = $appsModel->set($data);
        } else {
            LogService::addActionLog("修改了应用[{$id}]的信息" . json_encode($data));
            $res = $appsModel->updateByColumn($id, $data);
        }
        $this->renderJson($res ? 0 : 1);
    }


    /**
     * 删除应用
     *
     */
    public function del()
    {
        $id = Input::getInt('id');
        $id < 1 && $this->renderJson(1);

        //判断该应用下有无菜单，有则提示不能删除
        MenusModel::getInstance()->getByColumn($id, 'app') && $this->renderJson(10101);
        $appsModel = new AppModel();
        if ($appsModel->delByColumn($id)) {
            LogService::addActionLog("删除了应用[{$id}]!");
            $this->renderJson(0);
        } else {
            $this->renderJson(1);
        }
    }
}