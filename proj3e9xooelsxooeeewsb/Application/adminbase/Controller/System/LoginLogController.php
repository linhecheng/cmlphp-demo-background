<?php
namespace adminbase\Controller\System;

use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Grid\Column\DateTime;
use adminbase\Service\Builder\Grid\Column\Text;
use adminbase\Service\Builder\Grid\SearchItem;
use adminbase\Service\Builder\GridBuildService;
use Cml\Config;
use adminbase\Controller\CommonController;
use adminbase\Model\System\LoginLogModel;
use adminbase\Service\SearchService;

class LoginLogController extends CommonController
{
    public function index()
    {
        GridBuildService::create('adminbase/System/LoginLog/ajaxPage', '登录日志')
            ->addSearchItem((new SearchItem())->setName('userid')->setPlaceholder('请输入用户id'))
            ->addSearchItem((new SearchItem())->setName('startTime')->setPlaceholder('开始时间')->setType(InputType::DateTime))
            ->addSearchItem((new SearchItem())->setName('endTime')->setPlaceholder('结束时间')->setType(InputType::DateTime))

            ->addColumn((new Text())->setText('id')->setDataColumnFieldName('id'))
            ->addColumn((new Text())->setText('userid')->setDataColumnFieldName('userid'))
            ->addColumn((new Text())->setText('用户名')->setDataColumnFieldName('username'))
            ->addColumn((new Text())->setText('昵称')->setDataColumnFieldName('nickname'))
            ->addColumn((new Text())->setText('Ip')->setDataColumnFieldName('ip'))
            ->addColumn((new DateTime())->setText('登录时间')->setDataColumnFieldName('ctime'))

            ->display();
    }

    /**
     * ajax请求分页
     *
     * @acljump adminbase/System/LoginLog/index
     */
    public function ajaxPage()
    {
        $loginLogModel = new LoginLogModel();
        SearchService::processSearch([
            'userid' => '',
            'startTime' => '>',
            'endTime' => '<'
        ], $loginLogModel, true);

        $totalCount = $loginLogModel->paramsAutoReset(false, true)->getTotalNums();
        $list = $loginLogModel->getListByPaginate(Config::get('page_num'));

        $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
    }
}