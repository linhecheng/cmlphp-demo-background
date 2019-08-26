<?php

namespace adminbase\Controller\System;

use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Grid\Column\DateTime;
use adminbase\Service\Builder\Grid\Column\Text;
use adminbase\Service\Builder\Grid\SearchItem;
use adminbase\Service\Builder\GridBuildService;
use Cml\Config;
use adminbase\Controller\CommonController;
use adminbase\Model\System\SystemLogModel;
use adminbase\Service\SearchService;

class SystemLogController extends CommonController
{
    public function index()
    {
        GridBuildService::create('adminbase/System/SystemLog/ajaxPage', '系统操作日志')
            ->addSearchItem((new SearchItem())->setName('userid')->setPlaceholder('请输入用户id'))
            ->addSearchItem((new SearchItem())->setName('url')->setPlaceholder('请输入URL'))
            ->addSearchItem((new SearchItem())->setName('startTime')->setPlaceholder('开始时间')->setType(InputType::DateTime))
            ->addSearchItem((new SearchItem())->setName('endTime')->setPlaceholder('结束时间')->setType(InputType::DateTime))

            ->addColumn((new Text())->setText('id')->setDataColumnFieldName('id'))
            ->addColumn((new Text())->setText('userid')->setDataColumnFieldName('userid'))
            ->addColumn((new Text())->setText('用户名')->setDataColumnFieldName('username'))
            ->addColumn((new Text())->setText('URL')->setDataColumnFieldName('url'))
            ->addColumn((new Text())->setText('操作的菜单名')->setDataColumnFieldName('action'))
            ->addColumn((new Text())->setText('GET参数')->setDataColumnFieldName('get')->setOtherInfo('style="width:250px;word-wrap: break-word;"'))
            ->addColumn((new Text())->setText('POST参数')->setDataColumnFieldName('post')->setOtherInfo('style="width:250px;word-wrap: break-word;"'))
            ->addColumn((new Text())->setText('Ip')->setDataColumnFieldName('ip'))
            ->addColumn((new DateTime())->setText('创建时间')->setDataColumnFieldName('ctime'))

            ->display();
    }

    /**
     * ajax请求分页
     *
     * @acljump adminbase/System/SystemLog/index
     */
    public function ajaxPage()
    {
        SearchService::$likeOpenLeft = false;
        SearchService::$likeOpenRight = false;
        $systemLogModel = new SystemLogModel();
        SearchService::processSearch([
            'userid' => '',
            'url' => 'like',
            'startTime' => '>',
            'endTime' => '<'
        ], $systemLogModel, true);
        $totalCount = $systemLogModel->paramsAutoReset(false, true)->getTotalNums();

        $list = $systemLogModel->getListByPaginate(Config::get('page_num'));

        $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
    }
}
