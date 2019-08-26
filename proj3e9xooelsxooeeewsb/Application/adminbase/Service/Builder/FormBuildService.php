<?php
/* * *********************************************************
* 表单构建
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/1/17 16:43
* *********************************************************** */

namespace adminbase\Service\Builder;

use adminbase\Service\Builder\Comm\BuilderBase;
use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\Comm\ButtonType;
use Cml\Cml;
use Cml\Config;
use Cml\Http\Response;
use Cml\Tools\StaticResource;
use Cml\View;
use \adminbase\Service\Builder\Form\Input\Base as InputBase;

class FormBuildService extends BuilderBase
{
    /**
     * 表单元素类型--兼容旧版本 新版请使用 InputType::xxx
     */
    const INPUT_TEXT = InputType::Text;
    const INPUT_HIDDEN = InputType::Hidden;
    const INPUT_PASSWORD = InputType::Password;
    const INPUT_CHECKBOX = InputType::Checkbox;
    const INPUT_RADIO = InputType::Radio;
    const INPUT_DATE = InputType::Date;
    const INPUT_DATETIME = InputType::DateTime;
    const INPUT_SELECT = InputType::Select;
    const INPUT_TEXTAREA = InputType::Textarea;
    const INPUT_EDITOR = InputType::Editor;
    const INPUT_UEDITOR = InputType::UEditor;
    const INPUT_FILE = InputType::File;
    const INPUT_IMAGE = InputType::Image;

    /**
     * 打开弹窗按钮类型--兼容旧版本 新版请使用 ButtonType::xxx
     */
    const BUTTON_ADD = ButtonType::ADD;
    const BUTTON_EDIT = ButtonType::EDIT;
    const BUTTON_DEL = ButtonType::DEL;
    const BUTTON_DISABLE = ButtonType::DISABLE;
    const BUTTON_TAB = ButtonType::TAB;
    const BUTTON_VIEW = ButtonType::VIEW;
    const BUTTON_EXPORT = ButtonType::EXPORT;

    /**
     * 页面顶部的标题
     *
     * @var string
     */
    private $title = '';

    /**
     * 表单信息
     *
     * @var array
     */
    private $table = [];

    /**
     * 表单元素的值
     *
     * @var array
     */
    private $data = [];


    /**
     * 要执行的js
     *
     * @var string
     */
    private $toPageJs = <<<js
        layui.use(['form'], function(){
            var form = layui.form;
            form.render();
        });
js;

    /**
     * 要引入的js
     * @var array
     */
    private $needIncludeJs = [

    ];

    /**
     * 引入js文件后执行
     *
     * @var string
     */
    private $toPageJsAfterInclude = <<<js
   
js;

    /**
     * FormBuildService constructor.
     *
     * @param string $title 页面的标题
     */
    public function __construct($title = '')
    {
        $this->title = $title;
    }

    /**
     * 添加表单元素
     *
     * @param Grid\Column\Base | callable $label 传入 Grid\Column 下的相关对象--或者使用闭包函数返回
     * @param string $name name属性 兼容旧版本
     * @param string $placeholder 兼容旧版本
     * @param string $class 样式 兼容旧版本
     * @param string $otherInfo 其它信息。直接输出如disable。 兼容旧版本
     * @param string $type 表单的type 兼容旧版本
     * @param mixed $options checkbox和radio时为value 、 兼容旧版本
     *      select时传option 如：[['1', '是', 'selected']] 生成的html <option value="1" selected >是</option>、
     *      editor时为图片上传的服务端地址
     *
     * 例:
     * ->addFormItem(function () use ($parentTree) {
     *     $nicknameInput = new Text();
     *     $nicknameInput->setLabel('params')->setName('params')
     *                    ->setPlaceholder('请输入url参数如id/1');
     *      return $nicknameInput;
     * })
     * ->addFormItem(function () use ($parentTree) {
     *      $nicknameInput = new Radio();
     *      $nicknameInput->setLabel('是否显示到菜单')->setName('isshow')
     *      ->setPlaceholder('是')
     *      ->setOptions(1);
     *      return $nicknameInput;
     * })
     * ->addFormItem(function () use ($parentTree) {
     *      $nicknameInput = new Radio();
     *      $nicknameInput->setLabel('是否显示到菜单')->setNam
     * e('isshow')
     *      ->setPlaceholder('否')
     *      ->setOptions(0);
     *      return $nicknameInput;
     * })
     * ->addFormItem('排序', 'sort')
     * ->addFormItem(function () {
     *      $idInput = new Hidden();
     *      return $idInput->setName('app');
     * })
     *
     * @return $this;
     */
    public function addFormItem($label, $name = '', $placeholder = '', $class = '', $otherInfo = '', $type = InputType::Text, $options = [])
    {
        if (is_callable($label)) {
            $label = $label();
        }

        $accpet = '';
        $range = 'false';
        if ($label instanceof InputBase) {
            $name = $label->getName();
            $placeholder = $label->getPlaceholder();
            $class = $label->getClass();
            $otherInfo = $label->getOtherInfo();
            $type = $label->getType();
            $options = $label->getOptions();
            method_exists($label, 'getAccept') && $accept = $label->getAccept();
            method_exists($label, 'getRange') && $range = $label->getRange();
            $label = $label->getLabel();
        }

        if (is_array($options) && $type != InputType::UEditor) {
            $option = '';
            foreach ($options as $item) {
                $option .= "<option value='{$item[0]}' {$item[2]} >{$item[1]}</option>";
            }
            $options = $option;
        }

        $theme = Config::get('html_theme');

        switch ($type) {
            case InputType::UEditor:
                $ueditorHomeUrl = StaticResource::parseResourceUrl("adminbase/{$theme}/plugins/ueditor/", false);
                $ueditorServiceUrl = Response::url('adminbase/Public/uploadConfig', false);

                $this->toPageJs .= <<<str
    window.UEDITOR_HOME_URL = "{$ueditorHomeUrl}";
    window.UEDITOR_SERVER_URL = "{$ueditorServiceUrl}";

str;
                $this->needIncludeJs = [
                    "adminbase/{$theme}/plugins/ueditor/ueditor.config.js",
                    "adminbase/{$theme}/plugins/ueditor/ueditor.all.min.js",
                    "adminbase/{$theme}/plugins/ueditor/lang/zh-cn/zh-cn.js"
                ];

                $ueditorConfig = '';
                foreach ($options as $key => $val) {
                    $ueditorConfig .= "window.UEDITOR_CONFIG.{$key}='{$val}';";
                }
                $this->toPageJsAfterInclude .= <<<str
                $ueditorConfig;
   var ue = UE.getEditor('editor_{$name}');
str;

                break;
            case InputType::Editor:
                $options = Response::url($options, false);
                $this->toPageJs .= <<<str
    layui.use(['layedit', 'cml'], function(){
        var layedit = layui.layedit;
        layedit.set({
            uploadImage: {
                url: '{$options}' //接口url
                ,type: 'post' //默认post
            }
        });    
        var index = layedit.build('editor_{$name}');
        cml.layeditIndex.push(index);
    });
str;
                break;
            case InputType::Date:
                $otherInfo .= ' readonly';
                $type = 'text';//用date类型laydate有bug
                $class .= ' laydate_select_' . $name;
                $this->toPageJs .= <<<str
layui.use('laydate', function(){
    layui.laydate.render({elem: '.laydate_select_{$name}', type: 'date', format: 'yyyy-MM-dd', range:{$range}})
});
str;
                break;
            case InputType::DateTime:
                $otherInfo .= ' readonly';
                $class .= ' laydate_select_' . $name;
                $this->toPageJs .= <<<str
layui.use('laydate', function(){
    layui.laydate.render({elem: '.laydate_select_{$name}', type: 'datetime', format: 'yyyy-MM-dd HH:mm:ss', range:{$range}})
});
str;
                break;
            case InputType::Image:
            case InputType::File:
                $options = Response::url($options, false);
                //$baseUrl = Config::get("static__path", Cml::getContainer()->make("cml_route")->getSubDirName());
                //为了兼容editor。后端返回完整文件路径
                $baseUrl = '';
                $this->toPageJs .= <<<str
    layui.use(['upload', 'cml'], function(){
        var cml = layui.cml;
        var elem = $('.imageup_{$name}');
        layui.upload.render({
               elem:'.imageup_{$name}',
              url: '{$options}'
              ,before: function(input){
                   cml.showLoading();
              }
              ,accept:'{$accept}'
              ,done: function(res){
                    cml.closeLoading();
                    cml.showTip(res.msg);         
                    if (res.code == 0) {
                        $(elem).parents('.layui-input-block').find('img').attr('src', '{$baseUrl}' + res.data.src).removeClass('layui-hide');
                        $(elem).parents('.layui-input-block').find('a').attr('href', '{$baseUrl}' + res.data.src).html('{$baseUrl}' + res.data.src);
                        $(elem).parents('.layui-input-block').find('.imageup').val(res.data.src);
                    }         
              }
        });        
    });
str;

        }
        $this->table[$name][] = [
            'label' => $label,
            'name' => $name,
            'placeholder' => $placeholder,
            'class' => $class,
            'other' => $otherInfo,
            'type' => $type,
            'options' => $options
        ];
        return $this;
    }

    /**
     * 有时候有们要在某个表单项的前面加上大块的警告提示信息。只要在添加相关的表单项前调用本方法即可
     *
     * @param string $tip 要输出的提示信息.支持html
     *
     * @return $this;
     */
    public function addBlockTip($tip = '')
    {
        static $block = 1;

        $this->table['__block____' . $block++][] = [
            'type' => 'block_tip',
            'tip' => $tip
        ];
        return $this;
    }

    /**
     * 赋值给表单元素
     *
     * @param array $data
     *
     * @return $this;
     */
    public function withData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 渲染模板并输出
     *
     * @param string $layout 布局
     * @param string $layoutInOtherApp 布局所在的应用名
     * @param string $tpl 使用的模板
     */
    public function display($layout = '', $layoutInOtherApp = 'adminbase', $tpl = 'table')
    {
        parent::display($layout, $layoutInOtherApp);

        $layerWidthHeight = $this->getLayerWidthHeight();

        $layerWidthHeight && $this->toPageJsAfterInclude .= <<<str
cml.layerWidth = {$layerWidthHeight};
str;

        echo View::getEngine('Html')
            ->assignByRef('title', $this->title)
            ->assignByRef('table', $this->table)
            ->assignByRef('data', $this->data)
            ->assignByRef('toPageJs', $this->toPageJs)
            ->assignByRef('neeIncludeJs', $this->needIncludeJs)
            ->assignByRef('toPageJsAfterInclude', $this->toPageJsAfterInclude)
            ->fetch($tpl, false, true, true);
        Cml::cmlStop();
    }

    /**
     * 添加js块
     *
     * @param string $js
     */
    public function addJs($js)
    {
        $this->toPageJs .= $js;
    }

    /**
     * @param string $title 页面的标题
     *
     * @return $this
     */
    public static function create($title = '')
    {
        return new self($title);
    }
}