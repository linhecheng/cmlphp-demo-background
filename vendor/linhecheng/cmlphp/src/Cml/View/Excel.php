<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图 Excel渲染引擎
 * *********************************************************** */

namespace Cml\View;

/**
 * 视图 Excel渲染引擎
 *
 * @package Cml\View
 */
class Excel extends Base
{
    /**
     * 生成Excel文件
     *
     * @param string $filename 文件名
     * @param array $titleRaw 标题行
     *
     * @return void
     */
    public function display($filename = '', array $titleRaw = [])
    {
        $filename == '' && $filename = 'excel';

        $excel = new \Cml\Vendor\Excel();
        $excel->config('utf-8', false, 'default', $filename);
        $titleRaw && $excel->setTitleRow($titleRaw);
        $excel->excelXls($this->args);
    }
}
