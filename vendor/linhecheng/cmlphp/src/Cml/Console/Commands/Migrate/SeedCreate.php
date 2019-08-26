<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 数据库迁移命令
 * 修改自https://github.com/robmorgan/phinx/tree/0.6.x-dev/src/Phinx/Console/Command
 * *********************************************************** */

namespace Cml\Console\Commands\Migrate;

use Cml\Cml;
use Cml\Console\Component\Dialog;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;
use Phinx\Util\Util;

/**
 * 数据库迁移-创建seed
 *
 * @package Cml\Console\Commands\Migrate
 */
class SeedCreate extends AbstractCommand
{
    protected $description = "create a new database seeder";

    protected $arguments = [
        'name' => 'What is the name of the seeder?',
    ];

    protected $options = [
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
    ];

    /**
     * 创建一个新的seeder
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->bootstrap($args, $options);

        // get the seed path from the config
        $path = $this->getConfig()->getSeedPath();

        if (!file_exists($path)) {
            $ask = new Dialog();
            if ($ask->confirm(Colour::colour('Create seeds directory?', [Colour::RED, Colour::HIGHLIGHT]))) {
                mkdir($path, 0755, true);
            }
        }

        $this->verifySeedDirectory($path);
        $path = realpath($path);

        $className = $args[0];

        if (!Util::isValidPhinxClassName($className)) {
            throw new \InvalidArgumentException(sprintf(
                'The seed class name "%s" is invalid. Please use CamelCase format',
                $className
            ));
        }

        // Compute the file path
        $filePath = $path . DIRECTORY_SEPARATOR . $className . '.php';

        if (is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                basename($filePath)
            ));
        }

        // inject the class names appropriate to this seeder
        $contents = file_get_contents($this->getSeedTemplateFilename());
        $classes = [
            '$useClassName' => 'Phinx\Seed\AbstractSeed',
            '$className' => $className,
            '$baseClassName' => 'AbstractSeed',
        ];
        $contents = strtr($contents, $classes);

        if (false === file_put_contents($filePath, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        Output::writeln('using seed base class ' . $classes['$useClassName']);
        Output::writeln('created ' . str_replace(Cml::getApplicationDir('secure_src'), '{secure_src}', $filePath));
    }
}
