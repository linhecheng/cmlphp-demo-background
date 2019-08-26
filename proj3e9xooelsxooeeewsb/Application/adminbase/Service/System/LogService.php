<?php namespace adminbase\Service\System;

use Cml\Http\Request;
use Cml\Vendor\Acl;
use Cml\Cml;
use Cml\Service;
use adminbase\Model\System\ActionLogModel;

/**
 * Class LoginlogService log服务类
 *
 * @package Service\System
 */
class LogService extends Service
{

    /**
     * 新增一条操作日志
     *
     * @param $action
     */
    public static function addActionLog($action)
    {
        $user = Acl::getLoginInfo();
        ActionLogModel::getInstance()->set([
            'action' => $action,
            'userid' => $user['id'],
            'username' => $user['username'],
            'ctime' => Cml::$nowTime
        ]);
    }
}