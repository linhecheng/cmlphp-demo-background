<?php
namespace adminbase\Controller\System;

use Cml\Http\Input;
use Cml\Model;
use Cml\View;
use adminbase\Controller\CommonController;

class IndexController extends CommonController
{
    /**
     * 首页
     *
     */
    public function index()
    {
        $isSub = Input::getInt('type', 0);
        $view = View::getEngine('Html');
        $isSub ? $view->displayWithLayout('System/Index/sub', 'regional') : $view->display('System/Index/index');
    }

    public function phpinfo()
    {
        var_dump(Model::staticCache()->getInstance('d')->config('get', 'timeout'));
        var_dump(ini_get('default_socket_timeout'));
        var_dump(\Redis::OPT_READ_TIMEOUT);

        phpinfo();
    }
}