<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Psr7 Response实现
 * *********************************************************** */

namespace Cml\Http\Message;

use Cml\Cml;
use Cml\Config;
use Cml\Http\Message\Psr\Response as PsrResponse;
use Cml\Http\Message\Psr\Stream;
use Cml\View;
use Cml\Http\Response as StaticResponse;

/**
 * Psr7 Response实现
 *
 * @mixin PsrResponse
 *
 * @package Cml\Http\Message
 */
class Response
{
    /**
     * @var PsrResponse
     */
    protected $psrResponse = null;

    /**
     * 构造函数
     *
     * @param int $status 标准HTTP状态码
     * @param array $headers 响应头
     * @param string|resource|Stream|null $body 响应体
     * @param string $version HTTP 版本信息
     * @param string|null $reason 响应短语(未传获取默认值)
     */
    public function __construct($status = 200, array $headers = [], $body = null, $version = '1.1', $reason = null)
    {
        if (is_null($this->psrResponse)) {
            $this->psrResponse = Cml::getContainer()->make('psr7_response', func_get_args());
        }
    }

    public function __call($method, $arguments)
    {
        $result = call_user_func_array([$this->psrResponse, $method], $arguments);
        if ($result instanceof $this->psrResponse) {
            $this->psrResponse = $result;
            return $this;
        } else {
            return $result;
        }
    }

    /**
     * 使用html模板引擎渲染
     *
     * @param string $template 模板文件路径
     * @param array $data 要赋值到模板的数组
     * @param bool $inOtherApp 是否为载入其它应用的模板
     * @param bool $doNotSetDir 不自动根据当前请求设置目录模板目录。用于特殊模板显示
     * @param bool $donNotWriteCacheFileImmediateReturn 不要使用模板缓存，实时渲染(系统模板使用)
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function html($template, array $data = [], $inOtherApp = false, $doNotSetDir = false, $donNotWriteCacheFileImmediateReturn = false)
    {
        $engine = $this->getEngine('Html');
        $html = $engine->assign($data)
            ->fetch($template, $inOtherApp, $doNotSetDir, $donNotWriteCacheFileImmediateReturn);

        return $this->engineWithHeaders($engine)->withBody(Stream::create($html));
    }


    /**
     * 使用布局模板渲染
     *
     * @param string $templateFile 模板文件
     * @param array $data 要赋值到模板的数组
     * @param string $layout 布局文件
     * @param bool|false $layoutInOtherApp 布局文件是否在其它应用
     * @param bool|false $tplInOtherApp 模板是否在其它应用
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function htmlWithLayout($templateFile = '', array $data = [], $layout = 'master', $layoutInOtherApp = false, $tplInOtherApp = false)
    {
        $engine = $this->getEngine('Html');
        $html = $engine->assign($data)
            ->fetchWithLayout($templateFile, $layout, $layoutInOtherApp, $tplInOtherApp);

        return $this->engineWithHeaders($engine)->withBody(Stream::create($html));
    }

    /**
     * 使用json模板引擎渲染
     *
     * @param array $data 要赋值到模板的数组
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function json($data)
    {
        $engine = $this->getEngine('Json');
        $json = $engine->assign($data)
            ->fetch();

        return $this->engineWithHeaders($engine)->withBody(Stream::create($json));
    }

    /**
     * 使用xml模板引擎渲染
     *
     * @param array $data 要赋值到模板的数组
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function xml($data)
    {
        $engine = $this->getEngine('Xml');
        $xml = $engine->assign($data)
            ->fetch();

        return $this->engineWithHeaders($engine)->withBody(Stream::create($xml));
    }

    /**
     * 使用excel模板引擎渲染
     *
     * @param array $data 要赋值到模板的数组
     * @param string $filename 文件名
     * @param array $titleRaw 标题行
     * @param string $encoding 编码
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function excel($data, $filename = '', array $titleRaw = [], $encoding = 'utf-8')
    {
        $engine = $this->getEngine('Excel');
        $excel = $engine->assign($data)
            ->fetch($filename, $titleRaw, $encoding);

        return $this->engineWithHeaders($engine)->withBody(Stream::create($excel));
    }

    /**
     * 输出原始文本
     *
     * @param string $data 要输入的数据
     * @param string $contentType content-type类型
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function raw($data, $contentType = 'text/plain')
    {
        $response = clone $this;
        $response = $response->withAddedHeader('Content-Type', "$contentType; charset=" . Config::get('default_charset'));
        return $response->withBody(Stream::create((string)$data));
    }

    /**
     * 重定向
     *
     * @param string $toUrl 重写向的目标地址
     * @param int $status HTTP状态码
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    public function redirect($toUrl, $status = 302)
    {
        strpos($toUrl, 'http') === false && $toUrl = StaticResponse::url($toUrl, false);

        return $this->withStatus($status)->withAddedHeader('Location', $toUrl);
    }

    /**
     * 返回引擎
     *
     * @param $type
     *
     * @return View\Html
     */
    private function getEngine($type)
    {
        $engine = View::getEngine($type);
        return $engine;
    }

    /**
     * 设置响应头
     *
     * @param View\Html $engine
     *
     * @return Response 根据psr规范返回的是一个新的对象
     */
    private function engineWithHeaders($engine)
    {
        $response = clone $this;
        foreach ($engine->getHeader() as $key => $val) {
            $response = $response->withAddedHeader($key, $val);
        }
        return $response;
    }
}
