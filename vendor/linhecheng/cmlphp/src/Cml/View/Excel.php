<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 视图 Excel渲染引擎
 * *********************************************************** */

namespace Cml\View;

use \Cml\Vendor\Excel as ExcelLib;

/**
 * 视图 Excel渲染引擎
 *
 * @package Cml\View
 */
class Excel extends Base
{
    /**
     * 获取excel内容
     *
     * @param string $filename 文件名
     * @param array $titleRaw 标题行
     * @param string $encoding 编码
     *
     * @return string
     */
    public function fetch($filename = '', array $titleRaw = [], $encoding = 'utf-8')
    {
        return $this->config($filename, $titleRaw, $encoding)->fetch($this->args);
    }

    /**
     * 生成Excel文件
     *
     * @param string $filename 文件名
     * @param array $titleRaw 标题行
     * @param string $encoding 编码
     *
     * @return void
     */
    public function display($filename = '', array $titleRaw = [], $encoding = 'utf-8')
    {
        $this->config($filename, $titleRaw, $encoding)->excelXls($this->args);
    }

    /**
     * 生成Excel文件
     *
     * @param string $filename 文件名
     * @param array $titleRaw 标题行
     * @param string $encoding 编码
     *
     * @return ExcelLib
     */
    private function config($filename = '', array $titleRaw = [], $encoding = 'utf-8')
    {
        $filename == '' && $filename = 'excel';

        $excel = new ExcelLib();
        $excel->config($encoding, false, 'default', $filename);
        $titleRaw && $excel->setTitleRow($titleRaw);

        $this->setHeader('Content-Type', "application/vnd.ms-excel; charset={$encoding}");
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $excel->getFileName() . '"');
        return $excel;
    }
}
