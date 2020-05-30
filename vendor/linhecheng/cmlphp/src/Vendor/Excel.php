<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Excel生成类
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * Excel生成类
 *
 * @package Cml\Vendor
 */
class Excel
{
    private $header = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="[url=http://www.w3.org/TR/REC-html40]http://www.w3.org/TR/REC-html40[/url]"><head><meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT"><meta http-equiv=Content-Type content="text/html; charset=%s"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>%s</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';

    private $coding;
    private $tWorksheetTitle;
    private $filename;
    private $titleRow = [];

    /**
     * Excel基础配置
     *
     * @param string $enCoding 编码
     * @param bool|string $boolean 转换类型
     * @param string $title 表标题
     * @param string $filename Excel文件名
     *
     * @return void
     */
    public function config($enCoding, $boolean, $title, $filename = '')
    {
        if (func_num_args() == 3) {
            $filename = $title;
            $title = $boolean;
        }
        //编码
        $this->coding = $enCoding;
        //表标题
        $title = preg_replace('/[\\\|:|\/|\?|\*|\[|\]]/', '', $title);
        $title = substr($title, 0, 30);
        $this->tWorksheetTitle = $title;
        //文件名
        //$filename = preg_replace('/[^aA-zZ0-9\_\-]/', '', $filename);
        $this->filename = $filename;
    }

    /**
     * 获取文件名
     *
     * @param bool $withFix 是否带文件后缀
     *
     * @return mixed
     */
    public function getFileName($withFix = true)
    {
        return $this->filename . ($withFix ? ".xls" : '');
    }

    /**
     * 添加标题行
     *
     * @param array $titleArr
     */
    public function setTitleRow($titleArr)
    {
        $this->titleRow = $titleArr;
    }

    /**
     * 循环生成Excel行
     *
     * @param array $data
     *
     * @return string
     */
    private function addRow($data)
    {
        $cells = '';
        foreach ($data as $val) {
            //字符转换为 HTML 实体
            //$val = htmlentities($val, ENT_COMPAT, $this->coding);
            $cells .= "<td align=\"left\">{$val}</td>";
        }
        return $cells;
    }

    /**
     * 获取输出的内容
     *
     * @param $data
     *
     * @return string
     */
    public function fetch($data)
    {
        ob_start();
        echo sprintf($this->header, $this->coding, $this->tWorksheetTitle);
        echo '<body link=blue vlink=purple ><table width="100%" border="0" cellspacing="0" cellpadding="0">';

        if (is_array($this->titleRow)) {
            echo "<thead><tr>\n" . $this->addRow($this->titleRow) . "</tr></thead>\n";
        }
        echo '<tbody>';
        foreach ($data as $val) {
            $rows = $this->addRow($val);
            echo "<tr>\n" . $rows . "</tr>\n";
        }
        echo "</tbody></table></body></html>";
        return ob_get_clean();
    }

    /**
     * 生成Excel文件
     *
     * @param array $data
     *
     * @return void
     */
    public function excelXls($data)
    {
        header("Content-Type: application/vnd.ms-excel; charset=" . $this->coding);
        header('Content-Disposition: attachment; filename="' . rawurlencode($this->getFileName(true)) . '"');

        exit($this->fetch($data));
    }
}
