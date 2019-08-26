<?php
/* * *********************************************************
* 表单构建基础类
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/1/17 16:43
* *********************************************************** */
namespace adminbase\Service\Builder;

use Cml\Cml;
use Cml\Config;
use Cml\View;

abstract class Base
{

    /**
     * 页面中按钮打开layer弹出层的宽高
     *
     * @var string
     */
    private $layerWidthHeight = '';


    /**
     * 渲染模板并输出
     *
     * @param string $layout 布局
     * @param string $layoutInOtherApp
     */
    public function display($layout = 'regional', $layoutInOtherApp = 'adminbase')
    {
        if ($layout) {
            $layout = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR
                . ($layoutInOtherApp ? $layoutInOtherApp : Cml::getContainer()->make('cml_route')->getAppName())
                . DIRECTORY_SEPARATOR . Cml::getApplicationDir('app_view_path_name') . DIRECTORY_SEPARATOR
                . (Config::get('html_theme') != '' ? Config::get('html_theme') . DIRECTORY_SEPARATOR : '')
                . 'layout' . DIRECTORY_SEPARATOR . $layout . Config::get('html_template_suffix');
            View::getEngine('Html')->setLayout($layout);
        }
        View::getEngine('Html')->setHtmlEngineOptions('templateDir', __DIR__ . DIRECTORY_SEPARATOR)->assign('layerWidthHeight', $this->layerWidthHeight);
    }

    /**
     *
     * @return $this
     */
    public static function create()
    {
        $class = get_called_class();
        return new $class;
    }

    /**
     * ->setLayerWidthHeight("['100%', '100%']")
     * 页面中按钮打开layer弹出层的宽高
     *
     * @param string $width
     *
     * @return $this
     */
    public function setLayerWidthHeight($width = '500px')
    {
        $this->layerWidthHeight = $width;
        return $this;
    }

    /**
     * 页面中按钮打开layer弹出层的宽高
     *
     */
    public function getLayerWidthHeight()
    {
        return $this->layerWidthHeight;
    }
}