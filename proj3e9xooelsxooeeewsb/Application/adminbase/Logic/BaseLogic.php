<?php
/* * *********************************************************
* 运行控制器前置逻辑
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/1/13 14:28
* *********************************************************** */

namespace adminbase\Logic;

use adminbase\Model\Acl\AppModel;
use adminbase\Model\Acl\MenusModel;
use adminbase\Model\System\SystemLogModel;
use adminbase\Service\ResponseService;
use Cml\Cml;
use Cml\Config;
use Cml\Http\Input;
use Cml\Http\Request;
use Cml\Http\Response;
use Cml\Model;
use Cml\View;
use Cml\Secure;
use Cml\Vendor\Acl;

class BaseLogic
{
    /**
     * 保存用户操作日志并赋值用户信息、菜单信息、面包屑到模板
     */
    public static function saveLogAndAssignDataToTpl()
    {
        $url = ltrim(str_replace('\\', '/',
            Cml::getContainer()->make('cml_route')->getFullPathNotContainSubDir()
        ), '/');

        //记录操作日志
        $currentMenu = MenusModel::getInstance()->getByColumn($url, 'url');
        $tmp = $_POST;
        $post = Secure::htmlspecialchars($_POST);
        $_POST = $tmp;

        $fields = Config::get('log_unset_field', []);

        foreach ($fields as $val) {
            isset($post[$val]) && $post[$val] = '';
        }

        SystemLogModel::getInstance()->set([
            'action' => $currentMenu ? $currentMenu['title'] : $url,
            'url' => $url,
            'userid' => Acl::$authUser['id'],
            'username' => Acl::$authUser['username'],
            'get' => json_encode(Secure::htmlspecialchars($_GET), JSON_UNESCAPED_UNICODE),
            'post' => json_encode($post, JSON_UNESCAPED_UNICODE),
            'ip' => Request::ip(),
            'ctime' => Cml::$nowTime
        ]);

        //获取我有权限的app列表
        $getMyApp = function () {
            $app = AppModel::getInstance()->getList(0, 5000, 'ASC');
            //只显示有权限的app
            $menus = Acl::getMenus(false, 'm.app');
            $hadAclApp = [];
            array_walk($menus, function ($menu) use (&$hadAclApp) {
                array_push($hadAclApp, $menu['app']);
                $hadAclApp = array_unique($hadAclApp);
            });

            //取出默认载入的第一个app
            $defaultApp = Config::get('default_app');
            $defaultApp = in_array($defaultApp, $hadAclApp) ? $defaultApp : $hadAclApp[0];

            foreach ($app as $key => $val) {
                if (!in_array($val['id'], $hadAclApp)) {
                    unset($app[$key]);
                }
                $app[$key]['default'] = $val['id'] == $defaultApp;
            }

            return $app;
        };

        if (!Request::isAjax()) {
            if ($url == 'adminbase/System/Index/index') {
                $app = $getMyApp();
                View::getEngine('Html')
                    ->assignByRef('app', $app)
                    ->assign('user', Acl::getLoginInfo());
            }
        } else if ($url == 'adminbase/System/Index/index' && isset($_GET['app'])) {
            Model::getInstance()->whereIn('m.app', [0, Input::getInt('app', Config::get('default_app', 1))]);
            $menus = Acl::getMenus();
            if (empty($menus)) {
                //默认载入的app当前用户没有权限。载入用户有权限的第一个app
                $app = $getMyApp();
                $app = array_shift($app);
                if ($app) {
                    Model::getInstance()->whereIn('m.app', [0, $app['id']]);
                    $menus = Acl::getMenus();
                } else {
                    ResponseService::renderJson(403, '您没有任何操作权限!');
                }
            }

            $bread = '';
            $menus = array_values($menus);
            $icon = Config::get('menu_icon');
            $icon = $icon[Config::get('html_theme')];
            $iconCount = 0;
            $urlDeper = Config::get('url_pathinfo_depr');
            foreach ($menus as $key => &$val) {
                $val['icon'] = $icon[$iconCount++ % count($icon)];
                if ($val['url'] == $url) {
                    $val['current'] = true;
                    $bread[$url] = $val['title'];
                } else {
                    $val['current'] = false;
                }

                if (empty($val['sonNode']) && empty($val['url'])) {
                    unset($menus[$key]);
                } else {
                    $val['sonNode'] = array_values($val['sonNode']);

                    foreach ($val['sonNode'] as &$v) {
                        if ($v['url'] == $url) {
                            $bread[$val['url']] = $val['title'];
                            $bread[$v['url']] = $v['title'];
                            $v['current'] = true;
                            $val['current'] = true;
                        } else {
                            $v['current'] = false;
                            $val['current'] = false;
                        }
                        $isGetParams = '';
                        if ($v['params'] && substr($v['params'], 0, 1) == '?') {
                            $isGetParams = $v['params'];
                        }
                        $v['url'] = Response::url(trim($v['url'], $urlDeper) . ($isGetParams ? '' : ($v['params'] ? $urlDeper : '') . trim($v['params'], $urlDeper)), false) . $isGetParams;
                        $v['icon'] = $icon[$iconCount++ % count($icon)];
                        $v['sonNode'] = array_values($v['sonNode']);
                        foreach ($v['sonNode'] as &$last) {
                            $isGetParams = '';
                            if ($last['params'] && substr($last['params'], 0, 1) == '?') {
                                $isGetParams = $last['params'];
                            }
                            $last['url'] = Response::url(trim($last['url'], $urlDeper) . ($isGetParams ? '' : ($last['params'] ? $urlDeper : '') . trim($last['params'], $urlDeper)), false) . $isGetParams;
                        }
                    }
                }
                $isGetParams = '';
                if ($val['params'] && substr($val['params'], 0, 1) == '?') {
                    $isGetParams = $val['params'];
                }
                $val['url'] = Response::url(trim($val['url'], $urlDeper) . ($isGetParams ? '' : ($val['params'] ? $urlDeper : '') . trim($val['params'], $urlDeper)), false) . $isGetParams;
            }
            ResponseService::renderJson(0, $menus);
        }

        View::getEngine('Html')->assign('title', $currentMenu ? $currentMenu['title'] : $url);
    }
}
