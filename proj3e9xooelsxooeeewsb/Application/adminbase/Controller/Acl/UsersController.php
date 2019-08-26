<?php
/**
 * 用户管理
 *
 * @authon linhecheng
 */

namespace adminbase\Controller\Acl;

use adminbase\Model\Acl\AccessModel;
use adminbase\Service\AclService;
use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Comm\Option;
use adminbase\Service\Builder\Form\Input\CheckBox;
use adminbase\Service\Builder\Form\Input\Hidden;
use adminbase\Service\Builder\Form\Input\Password;
use adminbase\Service\Builder\Form\Input\Textarea;
use adminbase\Service\Builder\FormBuildService;
use adminbase\Service\Builder\Grid\Button\Add;
use adminbase\Service\Builder\Grid\Button\Del;
use adminbase\Service\Builder\Grid\Button\Disable;
use adminbase\Service\Builder\Grid\Button\Edit;
use adminbase\Service\Builder\Grid\Column\DateTime;
use adminbase\Service\Builder\Grid\Column\Status;
use adminbase\Service\Builder\Grid\Column\Text;
use adminbase\Service\Builder\Grid\SearchItem;
use adminbase\Service\Builder\GridBuildService;
use adminbase\Service\System\LogService;
use Cml\Plugin;
use Cml\Vendor\Acl;
use Cml\Cml;
use Cml\Config;
use Cml\Http\Input;
use adminbase\Controller\CommonController;
use adminbase\Model\Acl\GroupsModel;
use adminbase\Model\Acl\UsersModel;
use Cml\Vendor\Validate;
use adminbase\Service\SearchService;

class UsersController extends CommonController
{
    //用户列表
    public function index()
    {
        GridBuildService::create('adminbase/Acl/Users/ajaxPage', '用户列表')
            ->addSearchItem(function () {
                $options = [];
                $options[] = Option::getInstance()->setValue(0)->setText('请选择用户组');

                $groupList = GroupsModel::getInstance()->getGroupsList(10000);
                foreach ($groupList as $group) {
                    $options[] = Option::getInstance()->setValue($group['id'])->setText($group['name']);
                }

                $groupIdSearch = new SearchItem();
                $groupIdSearch->setName('groupid')
                    ->setType(InputType::Select)
                    ->setValue($options);
                return $groupIdSearch;
            })
            ->addSearchItem(SearchItem::getInstance()->setName('username')->setPlaceholder('请输入用户名'))
            ->addSearchItem(SearchItem::getInstance()->setName('nickname')->setPlaceholder('请输入用户昵称'))
            ->addTopButton(function () {
                $addUserBtn = new Add();
                $addUserBtn->setText('新增用户')
                    ->setNewPageTitle('新增用户')
                    ->setNewPageUrl('adminbase/Acl/Users/add')
                    ->setSaveDataUrl('adminbase/Acl/Users/save');
                return $addUserBtn;
            })
            ->addColumn(Text::getInstance()->setText('id')->setDataColumnFieldName('id'))
            ->addColumn(Text::getInstance()->setText('用户名')->setDataColumnFieldName('username'))
            ->addColumn(Text::getInstance()->setText('昵称')->setDataColumnFieldName('nickname'))
            ->addColumn(Text::getInstance()->setText('用户组')->setDataColumnFieldName('groupname'))
            ->addColumn(function () {
                $statusButton = new Status();
                return $statusButton->setText('状态')
                    ->setDataColumnFieldName('status');
            })
            ->addColumn(DateTime::getInstance()->setText('最后登录时间')->setDataColumnFieldName('lastlogin'))
            ->addColumn(DateTime::getInstance()->setText('创建时间')->setDataColumnFieldName('ctime'))
            ->addColumn(DateTime::getInstance()->setText('修改时间')->setDataColumnFieldName('stime'))
            ->addColumn(function () {
                $buttonEdit = new Edit();
                $buttonEdit->setText('编辑')
                    ->setNewPageTitle('编辑用户')
                    ->setNewPageUrl('adminbase/Acl/Users/edit')
                    ->setSaveDataUrl('adminbase/Acl/Users/save');

                $buttonDel = new Del();
                $buttonDel->setText('删除')
                    ->setUrl('adminbase/Acl/Users/del')
                    ->setClass('layui-btn-danger');

                $buttonStatus = new Disable();
                $buttonStatus->setText("{{item.status == 1 ? '禁用' : '启用'}}")
                    ->setUrl('adminbase/Acl/Users/disable')
                    ->setStatusField('status')
                    ->setOtherInfo(":class=\"item.status==1 ? 'layui-btn-warm' : 'layui-btn-normal'\"");

                $buttonAcl = new Add();
                $buttonAcl->setText('授权')
                    ->setNewPageTitle('用户授权')
                    ->setNewPageUrl('adminbase/Acl/Acl/user')
                    ->setSaveDataUrl('adminbase/Acl/Acl/save/type/1')
                    ->setClass('layui-btn-primary');

                $buttonColumn = new \adminbase\Service\Builder\Grid\Column\Button();
                $buttonColumn->setText('操作')
                    ->setDataColumnFieldName('id')
                    ->addButtons([
                        $buttonEdit,
                        $buttonDel,
                        $buttonStatus,
                        $buttonAcl
                    ]);
                return $buttonColumn;
            })
            ->display();
    }

    /**
     * ajax请求分页
     *
     * @acljump adminbase/Acl/Users/index
     */
    public function ajaxPage()
    {
        $usersModel = new UsersModel();

        Acl::isSuperUser() || $usersModel->whereNot('id', Config::get('administratorid'));
        SearchService::processSearch([
            'username' => 'like',
            'nickname' => 'like'
        ], $usersModel, true);
        $groupId = Input::getInt('groupid', 0);
        $totalCount = $usersModel->when($groupId, function (UsersModel $model) use ($groupId) {
            $model->whereRaw("find_in_set($groupId, `groupid`)", []);
        })->paramsAutoReset(false, true)->getTotalNums();

        $list = $usersModel->paramsAutoReset(true, true)->getListByPaginate(Config::get('page_num'));
        array_walk($list, function (&$item) {
            $item['groupname'] = $item['groupid'] ? GroupsModel::getInstanceAndRunMapDbAndTable()->whereIn('id', explode(',', $item['groupid']))->plunk('name') : [];
            $item['groupname'] = implode(', ', $item['groupname']);
        });
        $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
    }


    /**
     * 新增用户
     *
     * @param array $user 编辑的时候传入用户信息
     */
    public function add($user = [])
    {
        $showField = Plugin::hook('before_add_or_edit_user', $user ? [$user['from_type']] : []);

        $inst = FormBuildService::create($user ? '修改用户信息' : '新增用户')
            ->addFormItem(function () use ($showField) {
                $remarkInput = new \adminbase\Service\Builder\Form\Input\Text();
                $remarkInput->setLabel('用户名')->setName('username')
                    ->setPlaceholder((isset($showField['username']) ? $showField['username'] : '请输入用户名'))
                    ->setOtherInfo(' lay-verify="length" lay-min="3" lay-max="10" ');
                return $remarkInput;
            });

        if (!$showField || $showField['nickname']) {
            $inst->addFormItem(function () {
                $nicknameInput = new \adminbase\Service\Builder\Form\Input\Text();
                $nicknameInput->setLabel('昵 称')->setName('nickname')
                    ->setPlaceholder('请输入昵称')
                    ->setOtherInfo(' lay-verify="length" lay-min="3" lay-max="20" ');
                return $nicknameInput;
            });
        }

        if (!$showField || $showField['password']) {
            $inst->addFormItem(function () {
                $nicknameInput = new Password();
                $nicknameInput->setLabel('密码')->setName('password')
                    ->setPlaceholder('请输入新密码')->setClass('use_password')
                    ->setOtherInfo(' lay-verify="length" lay-min="3" lay-max="20" ');
                return $nicknameInput;
            })->addFormItem(function () {
                $nicknameInput = new Password();
                $nicknameInput->setLabel('重复密码')->setName('checkpassword')
                    ->setPlaceholder('请重复输入密码')
                    ->setOtherInfo(' lay-verify="repeat" lay-repeat=".use_password" lay-repeat-text="值必须和密码一样" ');
                return $nicknameInput;
            });
        }

        $groupOptions = [];
        $group = GroupsModel::getInstance()->getAllGroups();

        foreach ($group as $item) {
            //$groupOptions[] = [$item['id'], $item['name'], ($user && $user['groupid'] == $item['id']) ? 'selected' : ''];
            $inst->addFormItem(function () use ($item) {
                $nicknameInput = new CheckBox();
                $nicknameInput->setLabel('用户组')->setName('groupid[]')
                    ->setPlaceholder($item['name'])
                    ->setValue($item['id']);
                return $nicknameInput;
            });
        }
        //$inst->addFormItem('用户组', 'groupid', '', '', '', InputType::Select, $groupOptions);

        $inst->addFormItem(function () use ($item) {
            $nicknameInput = new Textarea();
            $nicknameInput->setLabel('备注')->setName('remark')
                ->setPlaceholder('备注信息');
            return $nicknameInput;
        })->addFormItem(function () {
            $idInput = new Hidden();
            return $idInput->setName('id');
        });

        $user && $user['groupid[]'] = explode(Acl::getMultiGroupDeper(), trim($user['groupid'], Acl::getMultiGroupDeper()));

        $inst->withData($user)->display();
    }

    /**
     * 编辑用户
     *
     */
    public function edit()
    {
        $id = Input::getInt('id');

        AclService::currentLoginUserIsHadPermisionToOpUser($id) || exit('您所有的用户组没有操作该用户[组]的权限!');

        $this->add(UsersModel::getInstance()->getByColumn($id));
    }

    /**
     * 保存用户
     *
     * @acljump adminbase/Acl/Users/add|adminbase/Acl/Users/edit
     *
     */
    public function save()
    {
        $data = [];
        $id = Input::postInt('id');
        $id > 0 && (AclService::currentLoginUserIsHadPermisionToOpUser($id) || $this->renderJson(10100));//'您所有的用户组没有操作该用户[组]的权限!'

        $username = Input::postString('username');
        $data['nickname'] = Input::postString('nickname');
        $data['password'] = Input::postString('password');
        $data['checkpassword'] = Input::postString('checkpassword');
        $data['groupid'] = Input::postInt('groupid');
        $data['remark'] = Input::postString('remark', '');

        if ($data['groupid']) {
            $newGroupInfo = GroupsModel::getInstance()->mapDbAndTable()->whereIn('id', $data['groupid'])->plunk('name', 'id');

            foreach ($newGroupInfo as $groupId => $groupName) {
                if (!AclService::currentLoginUserIsHadPermisionToOpGroup($groupId)) {
                    $this->renderJson(10100, "您所有的用户组没有权限将该用户的用户组设为【{$groupName}】!");
                }
            }
        }

        if (!is_null($data['nickname'])) {
            if (mb_strlen($data['nickname']) < 3 || mb_strlen($data['nickname']) > 20) {
                $this->renderJson(-1, '昵称长度必须大于3小于20！');
            }
        } else {
            unset($data['nickname']);
        }

        if (isset($data['password'])) {
            $data['password'] != $data['checkpassword'] && $this->renderJson(-1, '两次密码输入不正确，请重新输入！');
        } else {
            unset($data['password']);
        }

        unset($data['checkpassword']);

        if (is_null($data['password']) || empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = md5(md5($data['password']) . Config::get('password_salt'));
        }

        $usersModel = new UsersModel();

        $data['groupid'] = $data['groupid'] ? implode(Acl::getMultiGroupDeper(), $data['groupid']) : '';

        if (!$id) {//新增
            //第三方登录挂载点
            $otherUserInfo = Plugin::hook('before_add_user_save', [$username]);
            //判断是否已有同名用户
            $equalName = $usersModel->getByColumn($username, 'username');
            $equalName && $this->renderJson(1, '用户名已存在');

            is_array($otherUserInfo) && $data = array_merge($data, $otherUserInfo);
            $data['username'] = $username;
            $data['ctime'] = Cml::$nowTime;
            $usersModel->set($data);
            LogService::addActionLog("添加了用户" . json_encode($data, JSON_UNESCAPED_UNICODE));
        } else {
            $data['stime'] = Cml::$nowTime;

            LogService::addActionLog("修改了用户[{$id}]的信息" . json_encode($data, JSON_UNESCAPED_UNICODE));
            $usersModel->updateByColumn($id, $data);
        }
        $this->renderJson(0);
    }

    /**
     * 删除用户
     *
     */
    public function del()
    {
        $id = Input::getInt('id');
        $id < 1 && $this->renderJson(1);
        $users = new UsersModel();

        AclService::currentLoginUserIsHadPermisionToOpUser($id) || $this->renderJson(10100);

        if ($id === intval(Config::get('administratorid'))) {
            $this->renderJson(1, '不能删除超管');
        }

        if ($users->delByColumn($id)) {
            LogService::addActionLog("删除了用户[{$id}]");
            //删除对应的权限
            $accessModel = new AccessModel();
            $accessModel->delByColumn($id, 'userid');
            $this->renderJson(0);
        } else {
            $this->renderJson(1);
        }
    }

    /**
     * 禁用/解禁用户
     *
     */
    public function disable()
    {
        $data = [];
        $id = Input::getInt('id');

        AclService::currentLoginUserIsHadPermisionToOpUser($id) || $this->renderJson(10100);

        $status = Input::getInt('status');

        $data['status'] = $status ? 0 : 1;
        $id < 1 && $this->renderJson(1);
        $users = new UsersModel();
        if ($id === intval(Config::get('administratorid'))) {
            $this->renderJson(1, '不能操作超管');
        }

        if ($res = $users->updateByColumn($id, $data)) {
            LogService::addActionLog("禁用了用户[{$id}]");
            $this->renderJson(0);
        } else {
            $this->renderJson(1);
        }
    }

    /**
     * 修改个人资料
     * @noacl
     */
    public function editSelfInfo()
    {
        $user = Acl::getLoginInfo();
        $user = UsersModel::getInstance()->getByColumn($user['id']);
        $showField = Plugin::hook('before_add_or_edit_user', [$user['from_type']]);

        $inst = FormBuildService::create('个人资料')
            ->addFormItem('昵 称', 'nickname', '请输入昵称', '',
                'lay-verify="length" lay-min="3" lay-max="20" ' . isset($showField['nickname']) ? '' : ' disabled '
            );
        if (!$showField || $showField['password']) {
            $inst->addFormItem('密 码', 'oldpwd', '请输入旧密码', '', '', InputType::Password)
                ->addFormItem('新密码', 'pwd', '请输入新密码', 'use_password', '', InputType::Password)
                ->addFormItem('重复新密码', 'repwd', '重复新密码', '', ' lay-verify="repeat" lay-repeat=".use_password" lay-repeat-text="值必须和新密码一样" ', InputType::Password);
        }
        $inst->withData(['nickname' => $user['nickname']])
            ->display();
    }

    /**
     * 修改个人资料 - 保存
     *
     * @noacl
     *
     */
    public function saveSelfInfo()
    {
        $user = Acl::getLoginInfo();
        $userModel = new UsersModel();
        $user = $userModel->getByColumn($user['id']);

        $data = [];
        $data['nickname'] = Input::postString('nickname');
        $data['stime'] = Cml::$nowTime;

        if (isset($_POST['oldpwd']) && Validate::isLength($_POST['oldpwd'], 6, 20)) {
            if ($user['password'] != md5(md5($_POST['oldpwd']) . Config::get('password_salt'))) {
                $this->renderJson(10201);
            }

            if ($_POST['pwd'] != $_POST['repwd']) {
                $this->renderJson(10202);
            }

            if (!Validate::isLength($_POST['pwd'], 6, 20)) {
                $this->renderJson(2, '新密码长度必须为6-20个字符！');
            }
            $data['password'] = md5(md5($_POST['pwd']) . Config::get('password_salt'));
            Acl::logout();
        }

        if ($userModel->updateByColumn($user['id'], $data)) {
            $this->renderJson(0);
        }
    }
}