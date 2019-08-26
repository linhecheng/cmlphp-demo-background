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
 * 数据库迁移-生成迁移
 *
 * @package Cml\Console\Commands\Migrate
 */
class Create extends AbstractCommand
{
    /**
     * The name of the interface that any external template creation class is required to implement.
     */
    const CREATION_INTERFACE = 'Phinx\Migration\CreationInterface';

    protected $description = "create a new migration";

    protected $arguments = [
        'name' => 'What is the name of the migration?',
    ];

    protected $options = [
        '--template=xx' => 'Use an alternative template',
        '--class=xxx' => 'Use a class implementing "' . self::CREATION_INTERFACE . '" to generate the template',
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
    ];

    /**
     * 创建一个迁移
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $className = $args[0];

        $this->bootstrap($args, $options);

        if (!Util::isValidPhinxClassName($className)) {
            throw new \InvalidArgumentException(sprintf(
                'The migration class name "%s" is invalid. Please use CamelCase format.',
                $className
            ));
        }

        // get the migration path from the config
        $path = $this->getConfig()->getMigrationPath();

        if (!is_dir($path)) {
            $ask = new Dialog();
            if ($ask->confirm(Colour::colour('Create migrations directory?', [Colour::RED, Colour::HIGHLIGHT]))) {
                mkdir($path, 0755, true);
            }
        }

        $this->verifyMigrationDirectory($path);

        $path = realpath($path);

        if (!Util::isUniqueMigrationClassName($className, $path)) {
            throw new \InvalidArgumentException(sprintf(
                'The migration class name "%s" already exists',
                $className
            ));
        }

        // Compute the file path
        $fileName = Util::mapClassNameToFileName($className);
        $filePath = $path . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                $filePath
            ));
        }

        // Get the alternative template and static class options from the command line, but only allow one of them.
        $altTemplate = $options['template'];
        $creationClassName = $options['class'];
        if ($altTemplate && $creationClassName) {
            throw new \InvalidArgumentException('Cannot use --template and --class at the same time');
        }

        // Verify the alternative template file's existence.
        if ($altTemplate && !is_file($altTemplate)) {
            throw new \InvalidArgumentException(sprintf(
                'The alternative template file "%s" does not exist',
                $altTemplate
            ));
        }

        if ($creationClassName) {
            // Supplied class does not exist, is it aliased?
            if (!class_exists($creationClassName)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" does not exist',
                    $creationClassName
                ));
            }

            // Does the class implement the required interface?
            if (!is_subclass_of($creationClassName, self::CREATION_INTERFACE)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" does not implement the required interface "%s"',
                    $creationClassName,
                    self::CREATION_INTERFACE
                ));
            }
        }

        // Determine the appropriate mechanism to get the template
        if ($creationClassName) {
            // Get the template from the creation class
            $creationClass = new $creationClassName();
            $contents = $creationClass->getMigrationTemplate();
        } else {
            // Load the alternative template if it is defined.
            $contents = file_get_contents($altTemplate ?: $this->getMigrationTemplateFilename());
        }

        // inject the class names appropriate to this migration
        $classes = [
            '$useClassName' => $this->getConfig()->getMigrationBaseClassName(false),
            '$className' => $className,
            '$version' => Util::getVersionFromFileName($fileName),
            '$baseClassName' => $this->getConfig()->getMigrationBaseClassName(true),
        ];
        $contents = strtr($contents, $classes);

        if (false === file_put_contents($filePath, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        // Do we need to do the post creation call to the creation class?
        if ($creationClassName) {
            $creationClass->postMigrationCreation($filePath, $className, $this->getConfig()->getMigrationBaseClassName());
        }

        Output::writeln('using migration base class ' . Colour::colour($classes['$useClassName'], Colour::GREEN));

        if (!empty($altTemplate)) {
            Output::writeln('using alternative template ' . Colour::colour($altTemplate, Colour::GREEN));
        } elseif (!empty($creationClassName)) {
            Output::writeln('using template creation class ' . Colour::colour($creationClassName, Colour::GREEN));
        } else {
            Output::writeln('using default template');
        }

        Output::writeln('created ' . str_replace(Cml::getApplicationDir('secure_src'), '{secure_src}', $filePath));
    }
}
