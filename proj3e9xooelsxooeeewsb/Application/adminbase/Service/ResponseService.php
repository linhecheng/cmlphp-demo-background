<?php namespace adminbase\Service;

use Cml\Cml;
use Cml\Config;
use Cml\Http\Response;
use Cml\Service;
use Cml\View;

class ResponseService extends Service
{
    /**
     * 显示js Alert 并跳转到上一步|指定地址
     *
     * @param string $tip alert的提示信息
     * @param mixed $url 要跳转的地址
     */
    public static function showAlertAndGoBack($tip, $url = false)
    {
        $url = $url ? "window.location.href='" . Response::url($url, false) . "';" : 'window.history.back(-1);';
        $str = <<<str
            <script type="text/javascript">
                alert('{$tip}');
                {$url}
            </script>
str;
        exit($str);
    }


    /**
     * 渲染json输出
     *
     * @param int $code
     * @param string $msg
     * @param array $data
     * @param array $extraField 附加输出的字段
     */
    public static function renderJson($code, $msg = '', $data = [], $extraField = [])
    {
        if (is_array($msg)) {
            $data = $msg;
            $msg = '';
        }
        if (!$msg) {
            $codeFile = Cml::getApplicationDir('global_config_path') . '/code.php';
            $responseMsg = is_file($codeFile) ?
                Cml::requireFile($codeFile) : Config::load('code', false);
            $msg = $responseMsg[$code];
        }

        if (isset($data['totalCount']) && !isset($data['limit'])) {
            $data['limit'] = Config::get('page_num');
        }

        $view = View::getEngine('Json');
        $view->assign('code', $code)
            ->assign('msg', $msg)
            ->assign('data', $data);
        $extraField && $view->assign($extraField);
        $view->display();
    }

    /**
     * js跳转
     *
     * @param string $url
     */
    public static function jsJump($url)
    {
        $url = Response::url($url, false);
        echo <<<str
            <script>
                window.location.href='{$url}';
            </script>
str;
        exit();
    }
}