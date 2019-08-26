<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/11/2 14:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 创建控制器命令
 * *********************************************************** */

namespace Cml\Console\Commands\Make;

use Cml\Cml;
use Cml\Config;
use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;

/**
 * 创建控制器
 *
 * @package Cml\Console\Commands\Make
 */
class Controller extends Command
{
    protected $description = "Create a new controller class";

    protected $arguments = [
        'name' => 'The name of the class'
    ];

    protected $options = [
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
        '--template=xx' => 'Use an alternative template',
    ];

    protected $help = <<<EOF
The breakpoint command allows you to create a new controller class
eg:
`php index.php make:controller adminbase/test-Blog/Category`  this command will create a controller

<?php
namespace adminbase\test\Controller\Blog;

use Cml\Controller;

class CategoryController extends Controller
{
}
EOF;


    /**
     * 创建控制器
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $template = isset($options['template']) ? $options['template'] : false;
        $template || $template = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Controller.php.dist';

        $name = $args[0];
        $name = explode('-', $name);
        if (count($name) < 1) {
            throw new \InvalidArgumentException(sprintf(
                'The arg name "%s" is invalid. eg: adminbase-Blog/Category',
                $name
            ));
        }
        $namespace = str_replace('/', '\\', trim(trim($name[0], '\\/')));

        $path = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR
            . Cml::getApplicationDir('app_controller_path_name') . DIRECTORY_SEPARATOR;
        $component = explode('/', trim(trim($name[1], '/')));

        if (count($component) > 1) {
            $className = ucfirst(array_pop($component)) . Config::get('controller_suffix');
            $component = implode(DIRECTORY_SEPARATOR, $component);
            $path .= $component . DIRECTORY_SEPARATOR;
            $component = '\\' . $component;
        } else {
            $className = ucfirst($component[0]) . Config::get('controller_suffix');
            $component = '';
        }

        if (!is_dir($path) && false == mkdir($path, 0700, true)) {
            throw new \RuntimeException(sprintf(
                'The path "%s" could not be create',
                $path
            ));
        }

        $contents = strtr(file_get_contents($template), ['$namespace' => $namespace, '$component' => $component, '$className' => $className]);

        $file = $path . $className . '.php';
        if (is_file($file)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" is exist',
                $file
            ));
        }

        if (false === file_put_contents($file, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        Output::writeln(Colour::colour('Controller created successfully. ', Colour::GREEN));
    }
}
