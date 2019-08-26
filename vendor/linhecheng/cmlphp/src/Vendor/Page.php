<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 分页类
 * *********************************************************** */
namespace Cml\Vendor;

use Cml\Cml;
use Cml\Config;
use Cml\Http\Input;
use Cml\Http\Response;

/**
 * 分页类,对外系统现在一般使用js分页很少用到php分页了
 *
 * @package Cml\Vendor
 */
class Page
{
    /**
     * 分页栏每页显示的页数
     *
     * @var int
     */
    public $barShowPage = 5;

    /**
     * 页数跳转时要带的参数
     *
     * @var array
     */
    public $param;

    /**
     * 分页的基础url地址默认获取当前操作
     *
     * @var string
     */
    public $url = '';

    /**
     * 列表每页显示条数
     *
     * @var int
     */
    public $numPerPage;

    /**
     * 起始行数
     *
     * @var int
     */
    public $firstRow;

    /**
     * 分页总页数
     *
     * @var int
     */
    protected $totalPages;

    /**
     * 总行数
     *
     * @var int
     */
    protected $totalRows;

    /**
     * @var int 当前页数
     */
    protected $nowPage;

    /**
     * @var int 分页栏的总页数
     */
    protected $coolPages;

    /**
     * @var mixed|string 分页变量名
     */
    protected $pageShowVarName;

    /**
     * @var array 分页定制显示
     */
    protected $config = [
        'header' => '条记录',
        'prev' => '上一页',
        'next' => '下一页',
        'first' => '第一页',
        'last' => '最后一页',
        'theme' => '<li><a>%totalRow% %header% %nowPage%/%totalPage%页</a></li>%upPage% %downPage% %first%  %prePage% %linkPage%  %nextPage%  %end%'
    ];

    /**
     * 构造函数
     *
     * @param int $totalRows 总行数
     * @param int $numPerPage 每页显示条数
     * @param array $param 分页跳转时带的参数 如：['name' => '张三']
     */
    public function __construct($totalRows, $numPerPage = 20, $param = [])
    {
        $this->totalRows = $totalRows;
        $this->numPerPage = $numPerPage ? intval($numPerPage) : 10;
        $this->pageShowVarName = Config::get('var_page') ? Config::get('var_page') : 'p';
        $this->param = $param;
        $this->totalPages = ceil($this->totalRows/$this->numPerPage);
        $this->coolPages = ceil($this->totalPages/$this->barShowPage);
        $this->nowPage = Input::getInt($this->pageShowVarName, 1);
        if ($this->nowPage < 1) {
            $this->nowPage = 1;
        } elseif (!empty($this->totalRows) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->numPerPage*($this->nowPage - 1);
    }

    /**
     * 配置参数
     *
     * @param string $name 配置项
     * @param string $value 配置的值
     *
     * @return void
     */
    public function setConfig($name, $value)
    {
        isset($this->config[$name]) && ($this->config[$name] = $value);
    }

    /**
     *输出分页
     */
    public function show()
    {
        if ($this->totalRows == 0)  return '';
        $nowCoolPage = ceil($this->nowPage/$this->barShowPage);
        $delimiter = Config::get('url_pathinfo_depr');
        $params = array_merge($this->param, [$this->pageShowVarName => '__PAGE__']);
        $paramsString = '';
        foreach($params as $key => $val) {
            $paramsString == '' || $paramsString .= '/';
            $paramsString .= $key . '/' . $val;
        }

        if ($this->url) {
            $url = rtrim(Response::url($this->url . '/' . $paramsString, false), $delimiter);
        } else {
            $url = rtrim(Response::url(Cml::getContainer()->make('cml_route')->getFullPathNotContainSubDir()  . '/' .  $paramsString, false), $delimiter);
        }
        $upRow = $this->nowPage - 1;
        $downRow = $this->nowPage + 1;
        $upPage = $upRow > 0 ? '<li><a href = "'.str_replace('__PAGE__', $upRow, $url).'">'.$this->config['prev'].'</a></li>' : '';
        $downPage  = $downRow <= $this->totalPages ? '<li><a href="'.str_replace('__PAGE__', $downRow, $url).'">'.$this->config['next'].'</a></li>' : '';

        // << < > >>
        if ($nowCoolPage == 1) {
            $theFirst = $prePage = '';
        } else {
            $preRow = $this->nowPage - $this->barShowPage;
            $prePage = '<li><a href="'.str_replace('__PAGE__', $preRow, $url).'">上'.$this->barShowPage.'页</a></li>';
            $theFirst = '<li><a href="'.str_replace('__PAGE__', 1, $url).'">'.$this->config['first'].'</a></li>';
        }

        if ($nowCoolPage == $this->coolPages) {
            $nextPage = $theEnd = '';
        } else {
            $nextRow = $this->nowPage + $this->barShowPage;
            $theEndRow = $this->totalPages;
            $nextPage = '<li><a href="'.str_replace('__PAGE__', $nextRow, $url).'">下'.$this->barShowPage.'页</a></li>';
            $theEnd = '<li><a href="'.str_replace('__PAGE__', $theEndRow, $url).'">'.$this->config['last'].'</a></li>';
        }

        //1 2 3 4 5
        $linkPage = '';
        for ($i = 1; $i <= $this->barShowPage; $i++) {
            $page = ($nowCoolPage -1) * $this->barShowPage + $i;
            if ($page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $linkPage .= '&nbsp;<li><a href="'.str_replace('__PAGE__', $page, $url).'">&nbsp;'.$page.'&nbsp;</a></li>';
                } else {
                    break;
                }
            } else {
                if ($this->totalPages != 1) {
                    $linkPage .= '&nbsp;<li class="active"><a>'.$page.'</a></li>';
                }
            }
        }
        $pageStr = str_replace(
            ['%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'],
            [$this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd],
            $this->config['theme']
        );
        return '<ul>'.$pageStr.'</ul>';
    }
}