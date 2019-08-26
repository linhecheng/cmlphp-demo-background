<?php

namespace adminbase\Controller;

use adminbase\Logic\BaseLogic;
use adminbase\Service\ResponseService;
use adminbase\Service\ValidateOrRenderJson;
use Cml\Config;
use Cml\Controller;
use Cml\Http\Input;
use Cml\Http\Request;
use Cml\Http\Response;
use Cml\Model;
use Cml\Plugin;
use Cml\Vendor\Acl;
use adminbase\Service\AclService;
use Cml\Vendor\Validate;
use Cml\View;

class CommonController extends Controller
{
    use ValidateOrRenderJson;

    /**
     * @var View\Html
     */
    protected $view = null;


    /**
     * 权限验证
     *
     */
    public function init()
    {
        //不查从库-防止延迟
        $config = Config::get('default_db');
        $config['slaves'] = [];
        Config::set('default_db', $config);

        Acl::setTableName([
            'access' => 'admin_access',
            'groups' => 'admin_groups',
            'menus' => 'admin_menus',
            'users' => 'admin_users',
        ]);
        Acl::setMultiGroupDeper(',');

        $this->view = View::getEngine(Request::isAjax() ? 'Json' : 'Html');

        $user = Acl::getLoginInfo();

        if (!$user) {//未登录
            Plugin::hook('admin_not_login');

            if (Request::isAjax()) {
                strpos(Request::getService('HTTP_ACCEPT'), 'json')
                    ? $this->renderJson(-1000000, Response::url('adminbase/Public/login',false))
                    : ResponseService::jsJump('adminbase/Public/login');
            } else {
                Response::redirect('adminbase/Public/login');
            }
        }

        if (!Acl::checkAcl($this)) {//无权限
            AclService::noPermission();
        }

        $this->validate = new Validate($_REQUEST);

        BaseLogic::saveLogAndAssignDataToTpl();
    }


    /**
     * 公用的删除方法
     *
     * @param Model $model 数据模型
     * @param int|array $delStatus 删除的字段值。默认为0 传数组则为 字段名=>删除的值
     * @param string $pk 主键的字段名，默认为id
     * @param null|callable $successCall 删除成功的回调
     */
    protected function delBy($model, $delStatus = 0, $pk = 'id', $successCall = null)
    {
        $this->validate->rule('gt', 'id', 0);
        $this->runValidate();

        $id = Input::requestInt($pk);
        is_array($id) || $id = [$id];
        $data = $model->mapDbAndTable()
            ->whereNot(is_array($delStatus) ? key($delStatus) : 'status', is_array($delStatus) ? current($delStatus) : $delStatus)
            ->whereIn($pk, $id)
            ->count('1');
        $data || $this->renderJson(1, '数据不存在或已被删除');

        $model->mapDbAndTable()->whereIn($pk, $id)->update([
            (is_array($delStatus) ? key($delStatus) : 'status') => (is_array($delStatus) ? current($delStatus) : $delStatus)
        ]);

        if (is_callable($successCall)) {
            $successCall($id);
        }

        $this->renderJson(0);
    }
}
