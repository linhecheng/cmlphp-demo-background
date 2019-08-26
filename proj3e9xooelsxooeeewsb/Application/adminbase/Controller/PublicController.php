<?php

namespace adminbase\Controller;

use adminbase\Service\ResponseService;
use Cml\Config;
use Cml\Model;
use Cml\Plugin;
use Cml\Tools\StaticResource;
use Cml\Cml;
use Cml\Controller;
use Cml\Http\Input;
use Cml\Http\Request;
use Cml\Http\Response;
use Cml\Vendor\Acl;
use Cml\Vendor\UploadFile;
use Cml\View;
use adminbase\Model\Acl\UsersModel;
use adminbase\Model\System\LoginLogModel;
use Cml\Vendor\Validate;
use Cml\Vendor\VerifyCode;

class PublicController extends Controller
{
    public function init()
    {
        Acl::setTableName([
            'access' => 'admin_access',
            'groups' => 'admin_groups',
            'menus' => 'admin_menus',
            'users' => 'admin_users',
        ]);
    }

    //
    public function login()
    {
        $user = Acl::getLoginInfo();

        $user && ResponseService::jsJump('adminbase/System/Index/index');

        $showField = Plugin::hook('before_add_or_edit_user');

        View::getEngine()
            ->assign('showField', $showField ? $showField : [])
            ->display('Public/login');
    }

    /**
     * 校验登录
     *
     */
    public function checkLogin()
    {
        $username = trim(base64_decode(Input::postString('username')));
        $password = base64_decode(Input::postString('password'));
        $_POST['username'] = $username;
        $_POST['password'] = $password;

        $validate = new Validate($_POST);
        $validate
            ->rule('require', 'code', 'username', 'pwd')
            ->rule('length', 'username', 3, 50)
            ->rule('length', 'password', 6, 50)
            ->label([
                'code' => '验证码',
                'username' => '用户名',
                'password' => '密码'
            ]);

        if (!$validate->validate()) {
            ResponseService::renderJson(10002, $validate->getErrors(2, '|'));
        }

        $code = Input::postString('code');
        if (!VerifyCode::checkCode($code)) {
            ResponseService::renderJson(10001);
        }

        $usersModel = new UsersModel();

        $user = $usersModel->where('status', 1)->getByColumn($username, 'username');

        if (!$user || $user['status'] == 0) {
            ResponseService::renderJson(10003);
        }

        if ($user['from_type'] == 1) {
            md5(md5($password) . Config::get('password_salt')) != $user['password'] && ResponseService::renderJson(10005);
        } else {
            //第三方登录挂载点
            if (!Plugin::hook('admin_login_plugin', [$username, $password])) {
                ResponseService::renderJson(10005);
            }
        }

        Acl::setLoginStatus($user['id'], Config::get('oss_login', true));

        $loginLogModel = new LoginLogModel();
        $loginLogModel->set([
            'userid' => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'],
            'ip' => Request::ip(),
            'ctime' => Cml::$nowTime
        ]);

        $usersModel->updateByColumn($user['id'], [
            'lastlogin' => Cml::$nowTime
        ]);

        ResponseService::renderJson(0, '登录成功！');
    }

    //登出
    public function logout()
    {
        Acl::logout();
        Response::redirect('adminbase/public/login');
    }

    //创建静态文件软链接
    public function createSymLink()
    {
        StaticResource::createSymbolicLink();
    }

    /**
     * ueditor上传配置
     *
     */
    public function uploadConfig()
    {
        $baseDir = Cml::getApplicationDir('apps_path') . "/adminbase/Resource/kit_admin/plugins/ueditor/abcnimeinimei/";
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($baseDir . 'config.json')), true);
        $action = $_GET['action'];

        switch ($action) {
            case 'config':
                $result = json_encode($CONFIG);
                break;

            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = include($baseDir . "action_upload.php");
                break;

            /* 列出图片 */
            case 'listimage':
                $result = include($baseDir . "action_list.php");
                break;
            /* 列出文件 */
            case 'listfile':
                $result = include($baseDir . "action_list.php");
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = include($baseDir . "action_crawler.php");
                break;

            default:
                $result = json_encode(array(
                    'state' => '请求地址出错'
                ));
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state' => 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
            exit;
        }
    }

    public function upload()
    {
        $accept = Input::getString('accept');

        $accept && $accept = Encry::decrypt($accept);
        $config = Config::get('upload_config', [
            'maxSize' => 1024 * 1034 * 10
        ]);
        $accept && $config['allowExts'] = $accept;

        $upload = new UploadFile($config);
        if ($upload->upload(Config::get('upload_path'))) {
            $success = $upload->getSuccessInfo();
            ResponseService::renderJson(0, '上传成功', [
                'src' => Config::get('upload_into_db_prefix') . "{$success[0]['savename']}"
            ]);
        } else {
            ResponseService::renderJson(1, $upload->getErrorInfo());
        }
    }
}
