<?php
/* * *********************************************************
* 数据列表构建
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/1/17 16:43
* *********************************************************** */

namespace adminbase\Service\Builder;

use adminbase\Service\Builder\Comm\BuilderBase;
use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Comm\ButtonType;
use adminbase\Service\Builder\Form\Input\Base;
use adminbase\Service\Builder\Grid\Column\Button as ButtonColumn;
use adminbase\Service\Builder\Grid\Column\Checkbox;
use adminbase\Service\Builder\Grid\Column\ColumnType;
use adminbase\Service\Builder\Grid\SearchItem;
use Cml\Cml;
use Cml\Http\Response;
use Cml\View;
use adminbase\Service\Builder\Grid\Column\Base as ColumnBase;

class GridBuildService extends BuilderBase
{
    /**
     * 本类只构建列表页面中的搜索表单及数据列表表格的表头。数据是通过这边配置的地址异步加载
     *
     * @var string
     */
    private $ajaxGetDataUrl = '';

    /**
     * 要执行的js
     *
     * @var string
     */
    private $toPageJs = <<<js
layui.use('form', function(){
  var form = layui.form;
});
js;

    /**
     * 页面顶部的标题
     *
     * @var string
     */
    private $title = '数据列表';

    /**
     * 列表顶部搜索表单项
     *
     * @var array
     */
    private $searchField = [];

    /**
     * 列表顶部搜索表单旁边的按钮。如:新增
     *
     * @var array
     */
    private $Button = [];

    /**
     * 数据列表的表格信息
     *
     * @var array
     */
    private $table = [];

    /**
     * 是否有批量处理
     *
     * @var bool
     */
    private $checkAll = false;

    /**
     * 本类只构建列表页面中的搜索表单及数据列表表格的表头。数据是通过这边配置的地址异步加载
     *
     * @param string $ajaxGetDataUrl //输出的时候会自动使用 url模板标签输出
     * @param string $title 页面的标题
     */
    public function __construct($ajaxGetDataUrl, $title = '')
    {
        if (empty($ajaxGetDataUrl)) {
            throw new \InvalidArgumentException('数据加载地址必须填写');
        }
        $this->ajaxGetDataUrl = $ajaxGetDataUrl;
        $title && $this->title = $title;
    }

    /**
     * 添加一个列表顶部搜索表单项
     *
     * @param Grid\SearchItem | callable $name 传入 Builder\Grid\SearchItem 对象--或者使用闭包函数返回
     * @param string $placeholder 兼容旧版本
     * @param string $type 兼容旧版本
     * @param mixed $val value值 兼容旧版本
     * @param string $other 兼容旧版本
     * @param string $class 兼容旧版本
     *
     * 例子: ->addSearchItem('name', '请输入用户组名称')
     *
     * @return $this
     */
    public function addSearchItem($name, $placeholder = '', $type = InputType::Text, $val = '', $other = '', $class = '')
    {
        if (is_callable($name)) {
            $name = $name();
        }

        if ($name instanceof SearchItem) {
            $placeholder = $name->getPlaceholder();
            $type = $name->getType();
            $val = $name->getValue();
            $other = $name->getOtherInfo();
            $class = $name->getClass();
            $name = $name->getName();
        } elseif ($name instanceof Base) {
            $placeholder = $name->getPlaceholder();
            $type = $name->getType();
            $val = $name->getOptions();
            $other = $name->getOtherInfo();
            $class = $name->getClass();
            $name = $name->getName();
        }

        if (is_array($val)) {
            $option = '';
            foreach ($val as $item) {
                $option .= "<option value='{$item[0]}' {$item[2]} >{$item[1]}</option>";
            }
            $val = $option;
        }

        if ($type == InputType::Editor) {
            $this->toPageJs .= <<<str
layui.use('layedit', function(){
  var layedit = layui.layedit;
  layedit.build('editor_{$name}');
});
str;
        } else if ($type == InputType::Date) {
            $other .= ' readonly';
            $type = 'text';//用date类型laydate有bug
            $class .= ' laydate_select_' . $name;
            $this->toPageJs .= <<<str
layui.use('laydate', function(){
    layui.laydate.render({elem: '.laydate_select_{$name}', type: 'date', format: 'yyyy-MM-dd'})
});
str;
        } else if ($type == InputType::DateTime) {
            $other .= ' readonly';
            $class .= ' laydate_select_' . $name;
            $this->toPageJs .= <<<str
layui.use('laydate', function(){
    layui.laydate.render({elem: '.laydate_select_{$name}', type: 'datetime', format: 'yyyy-MM-dd HH:mm:ss'})
});
str;
        }

        $this->searchField[] = [
            'name' => $name,
            'placeholder' => $placeholder,
            'type' => $type,
            'val' => $val,
            'other' => $other,
            'class' => $class
        ];
        return $this;
    }

    /**
     * 添加一个列表顶部搜索旁边的按钮。
     *
     * @param Grid\Button\Base | callable $text 传入 Builder\Grid\Button 对象--或者使用闭包函数返回
     * @param string $type 兼容旧版本
     * @param string $newPageUrl 兼容旧版本
     * @param string $newPageTitle 兼容旧版本
     * @param string $saveDateUrl 兼容旧版本
     * @param string $class 兼容旧版本
     * @param string $other 兼容旧版本
     * @param mixed $layerWidth 兼容旧版本
     *
     * 例子： 如:新增 ->addTopButton('新增', ButtonType::ADD, 'adminbase/Acl/Groups/add', '新增用户组', 'adminbase/Acl/Groups/save')
     *
     * @return $this
     */
    public function addTopButton($text, $type = ButtonType::ADD, $newPageUrl = '', $newPageTitle = '', $saveDataUrl = '', $class = '', $other = '', $layerWidth = false)
    {
        if (is_callable($text)) {
            $text = $text();
        }

        if ($text instanceof \adminbase\Service\Builder\Grid\Button\Base) {
            $layerWidth = $text->getLayerWidth();
            $text = $text->getFormatArray();

            $type = $text['type'];
            $newPageUrl = $text['url'];
            $newPageTitle = $text['title'];
            $saveDataUrl = $text['saveUrl'];
            $class = $text['class'];
            $other = $text['other'];
            $text = $text['text'];
        }

        if ($type == ButtonType::EXPORT) {
            $class .= ' top_export_button ';
            $this->toPageJs .= <<<str
                layui.use(['cml'], function(){
                    var cml = layui.cml;
                    $('.top_export_button').click(function() {
                        cml.form.location($(this).data('url')+$('.search_form').serialize());
                    });
                });
str;
        }
        if ($type == ButtonType::IMPORT) {
            $newPageUrl = Response::url($newPageUrl, false);
            $class .= ' top_button_up ';
            $this->toPageJs .= <<<str
  layui.use(['upload', 'cml'], function(){
        var cml = layui.cml;
        var elem = $('.top_button_up');
        layui.upload.render({
               elem:'.top_button_up',
              url: '{$newPageUrl}'
              ,before: function(input){
                   cml.showLoading();
              }
              ,accept:'file'
              ,done: function(res){
                    cml.closeLoading();
                    cml.showTip(res.msg);        
                    cml.loadAjaxPage(false);          
              }
        });        
    });
str;
        }
        $this->Button[] = [
            'text' => $text,
            'type' => $type,
            'url' => $newPageUrl,
            'title' => $newPageTitle,
            'saveUrl' => $saveDataUrl,
            'class' => $class,
            'other' => $other,
            'width' => $layerWidth
        ];
        return $this;
    }

    /**
     * 添加数据列
     *
     * @param Grid\Column\Base | callable $text 传入 Grid\Column 下的相关对象--或者使用闭包函数返回
     * @param string $dataColumnFieldName 对应数据列的字段 兼容旧版本
     * @param string $other 兼容旧版本
     * @param int $type 兼容旧版本 类型1为文本 | 2为打勾打x。当值为>0时打勾。<=0时打x | 3为按钮(直接调用addButtonColumn)  |  5为checkbox(直接调用addCheckBoxColumn) | 6为图片  | 7为a标签 | 8为html
     *
     * 例子：->addColumn('用户组名称', 'name')
     *
     * @return $this
     */
    public function addColumn($text, $dataColumnFieldName = '', $other = '', $type = 1)
    {
        if (is_callable($text)) {
            $text = $text();
        }

        if ($text instanceof Checkbox) {
            return $this->oldAddCheckBoxColumn($text);
        }

        if ($text instanceof ButtonColumn) {
            return $this->oldAddButtonColumn($text);
        }

        if ($text instanceof ColumnBase) {
            $dataColumnFieldName = $text->getDataColumnFieldName();
            $other = $text->getOtherInfo();
            $type = $text->getColumnType();
            $text = $text->getText();
        }

        $this->table[] = [
            'text' => $text,
            'name' => $dataColumnFieldName,
            'other' => $other,
            'type' => $type
        ];
        return $this;
    }

    /**
     * 添加全选/反选批量操作列----兼容旧版本，新版直接用addColumn
     *
     * @param string $name 传入 Grid\Column\Checkbox 对象--或者使用闭包函数返回
     * @param array $buttons 兼容旧版本
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    private function oldAddCheckBoxColumn($name = '', $buttons = [])
    {
        if (is_callable($name)) {
            $name = $name();
        }

        if ($name instanceof Checkbox) {
            $buttons = $name->getButtons();
            $name = $name->getDataColumnFieldName();
        }

        $allowType = [ButtonType::DISABLE, ButtonType::DEL, ButtonType::EDIT];
        foreach ($buttons as $button) {
            if (!in_array($button['type'], $allowType)) {
                throw new \InvalidArgumentException('addCheckBoxColumn类型的按钮只能为' . json_encode($allowType));
            }
        }
        $this->table[] = [
            'text' => '<input type="checkbox"  v-model="cCheckAll" />',
            'name' => $name,
            'type' => ColumnType::Checkbox
        ];
        $this->checkAll = [
            'name' => $name,
            'buttons' => $buttons
        ];
        return $this;
    }

    /**
     * 添加按钮列----兼容旧版本，新版直接用addColumn
     *
     * @param string $text 传入 Grid\Column\Button 对象--或者使用闭包函数返回
     * @param string $id 兼容旧版
     * @param array $buttons 兼容旧版
     * @param string $other 其兼容旧版
     *
     * @return $this
     */
    private function oldAddButtonColumn($text = '', $id = 'id', $buttons = [], $other = '')
    {
        if (is_callable($text)) {
            $text = $text();
        }

        if ($text instanceof ButtonColumn) {
            $id = $text->getDataColumnFieldName();
            $other = $text->getOtherInfo();
            $buttons = $text->getButtons();
            $text = $text->getText();
        }

        foreach ($buttons as &$val) {
            isset($val['status']) && $val['saveUrl'] = $val['status'];
        }
        $this->table[] = [
            'id' => $id,
            'text' => $text,
            'buttons' => $buttons,
            'type' => ColumnType::Button,
            'other' => $other
        ];
        return $this;
    }

    /**
     * 可使用$inst->setLayerWidthHeight("['100%', '100%']");设置页面中按钮打开layer弹出层的宽高
     * 渲染模板并输出
     *
     * @param string $layout 布局
     * @param string $layoutInOtherApp 布局所在的应用名
     * @param string $tpl 使用的模板
     */
    public function display($layout = 'regional', $layoutInOtherApp = 'adminbase', $tpl = 'list')
    {
        $with = $this->getLayerWidthHeight();
        if (empty($with)) {
            $this->setLayerWidthHeight("'500px'");
        }

        parent::display($layout, $layoutInOtherApp);

        echo View::getEngine('html')
            ->assignByRef('search', $this->searchField)
            ->assignByRef('buttons', $this->Button)
            ->assignByRef('table', $this->table)
            ->assignByRef('ajaxUrl', $this->ajaxGetDataUrl)
            ->assignByRef('topTitle', $this->title)
            ->assignByRef('toPageJs', $this->toPageJs)
            ->assignByRef('checkAll', $this->checkAll)
            ->fetch($tpl, false, true, true);
        Cml::cmlStop();
    }

    /**
     * 本类只构建列表页面中的搜索表单及数据列表表格的表头。数据是通过这边配置的地址异步加载
     *
     * @param string $ajaxGetDataUrl //输出的时候会自动使用 url模板标签输出
     * @param string $title 页面的标题
     *
     * @return $this
     */
    public static function create($ajaxGetDataUrl = '', $title = '')
    {
        return new self($ajaxGetDataUrl, $title);
    }

    /**
     * 用来兼容旧版本
     *
     * @param string $method
     * @param mixed $arguments
     *
     * @return $this;
     */
    public function __call($method, $arguments)
    {
        switch ($method) {
            case 'addButtonColumn':
                return call_user_func_array([$this, 'oldAddButtonColumn'], $arguments);
            case 'addCheckBoxColumn';
                return call_user_func_array([$this, 'oldAddCheckBoxColumn'], $arguments);
        }
        throw new \BadMethodCallException("访问了不存在的方法［{$method}");
    }

    /**
     * 添加js到页面
     *
     * @param string $js
     *
     * @return $this
     */
    public function addJs($js)
    {
        $this->toPageJs .= <<<str
$js
str;
        return $this;
    }
}
