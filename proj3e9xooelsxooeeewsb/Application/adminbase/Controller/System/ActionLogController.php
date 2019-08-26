<?php
namespace adminbase\Controller\System;

use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Grid\Column\DateTime;
use adminbase\Service\Builder\Grid\Column\Text;
use adminbase\Service\Builder\Grid\SearchItem;
use adminbase\Service\Builder\GridBuildService;
use Cml\Config;
use adminbase\Controller\CommonController;
use adminbase\Model\System\ActionLogModel;
use adminbase\Service\SearchService;

class ActionLogController extends CommonController
{
    public function index()
    {
        GridBuildService::create('adminbase/System/ActionLog/ajaxPage', '重要操作日志')
            ->addSearchItem((new SearchItem())->setName('userid')->setPlaceholder('请输入用户id'))
            ->addSearchItem((new SearchItem())->setName('startTime')->setPlaceholder('开始时间')->setType(InputType::DateTime))
            ->addSearchItem((new SearchItem())->setName('endTime')->setPlaceholder('结束时间')->setType(InputType::DateTime))

            ->addColumn((new Text())->setText('id')->setDataColumnFieldName('id'))
            ->addColumn((new Text())->setText('userid')->setDataColumnFieldName('userid'))
            ->addColumn((new Text())->setText('用户名')->setDataColumnFieldName('username'))
            ->addColumn((new Text())->setText('操作名')->setDataColumnFieldName('action'))
            ->addColumn((new DateTime())->setText('操作时间')->setDataColumnFieldName('ctime'))
            ->display();
    }


    /**
     * ajax请求分页
     *
     * @acljump adminbase/System/ActionLog/index
     */
    public function ajaxPage()
    {
        $actionLogModel = new ActionLogModel();
        SearchService::processSearch([
            'userid' => '',
            'startTime' => '>',
            'endTime' => '<'
        ], $actionLogModel, true);

        $totalCount = $actionLogModel->paramsAutoReset(false, true)->getTotalNums();

        $list = $actionLogModel->getListByPaginate(Config::get('page_num'));

        $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
    }
}