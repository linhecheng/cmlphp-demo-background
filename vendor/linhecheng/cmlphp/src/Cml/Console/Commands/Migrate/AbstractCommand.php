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
use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Phinx\Db\Adapter\AdapterInterface;

/**
 * 数据库迁移-抽象命令
 *
 * @package Cml\Console\Commands\Migrate
 */
abstract class AbstractCommand extends Command
{
    /**
     * The location of the default migration template.
     */
    const DEFAULT_MIGRATION_TEMPLATE = __DIR__ . '/../../../../../../cmlphp-ext-phinx/src/Phinx/Migration/Migration.template.php.dist';

    /**
     * The location of the default seed template.
     */
    const DEFAULT_SEED_TEMPLATE = __DIR__ . '/../../../../../../cmlphp-ext-phinx/src/Phinx/Seed/Seed.template.php.dist';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Manager
     */
    protected $manager;


    /**
     * Bootstrap Phinx.
     *
     * @param array $args
     * @param array $options
     */
    public function bootstrap(array $args, array $options = [])
    {
        if (false === class_exists('\Phinx\Config\Config')) {
            throw new \RuntimeException('please use `composer require linhecheng/cmlphp-ext-phinx` cmd to install phinx.');
        }

        if (!$this->getConfig()) {
            $this->loadConfig($options);
        }

        $this->loadManager($args, $options);
        // report the paths
        Output::writeln(
            'using migration path ' .
            Colour::colour(str_replace(Cml::getApplicationDir('secure_src'), '{secure_src}', $this->getConfig()->getMigrationPath()), Colour::GREEN)
        );
        Output::writeln(
            'using seed path ' .
            Colour::colour(str_replace(Cml::getApplicationDir('secure_src'), '{secure_src}', $this->getConfig()->getSeedPath()), Colour::GREEN)
        );

        $exportPath = false;
        if (isset($options['e'])) {
            $exportPath = $options['e'];
        } else if (isset($options['export'])) {
            $exportPath = $options['export'];
        }
        if ($exportPath) {
            is_dir($exportPath) || $exportPath = $this->getConfig()->getExportPath();
            is_dir($exportPath) || mkdir($exportPath, 0700, true);
            Output::writeln(
                'using export path:' .
                Colour::colour(str_replace(Cml::getApplicationDir('secure_src'), '{secure_src}', $exportPath), Colour::GREEN)
            );
            $merge = (isset($options['m']) || isset($options['merge'])) ? 'merge_export_' . date('Y-m-d-H-i-s') . '.sql' : false;
            $this->getManager()->getEnvironment()->setExportPath($exportPath, $merge);
        }

        $this->getConfig()->echoAdapterInfo();
    }

    /**
     * Sets the config.
     *
     * @param  Config $config
     *
     * @return AbstractCommand
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Gets the config.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Sets the database adapter.
     *
     * @param AdapterInterface $adapter
     * @return AbstractCommand
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Gets the database adapter.
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets the migration manager.
     *
     * @param Manager $manager
     * @return AbstractCommand
     */
    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * Gets the migration manager.
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Parse the config file and load it into the config object
     *
     * @param array $options 选项
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function loadConfig($options)
    {
        if (isset($options['env']) && !in_array($options['env'], ['cli', 'product', 'development'])) {
            throw new \InvalidArgumentException('option --env\'s value must be [cli, product, development]');
        }
        $env = 'development';
        isset($options['env']) && $env = $options['env'];

        Output::writeln('using config -- ' . Colour::colour($env, Colour::GREEN));

        $this->setConfig(new Config($env));
    }

    /**
     * Load the migrations manager and inject the config
     *
     * @param array $args
     * @param array $options
     */
    protected function loadManager($args, $options)
    {
        if (null === $this->getManager()) {
            $manager = new Manager($this->getConfig(), $args, $options);
            $this->setManager($manager);
        }
    }

    /**
     * Verify that the migration directory exists and is writable.
     *
     * @param string $path
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function verifyMigrationDirectory($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Migration directory "%s" does not exist',
                $path
            ));
        }

        if (!is_writable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Migration directory "%s" is not writable',
                $path
            ));
        }
    }

    /**
     * Verify that the seed directory exists and is writable.
     *
     * @param string $path
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function verifySeedDirectory($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Seed directory "%s" does not exist',
                $path
            ));
        }

        if (!is_writable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Seed directory "%s" is not writable',
                $path
            ));
        }
    }

    /**
     * Returns the migration template filename.
     *
     * @return string
     */
    protected function getMigrationTemplateFilename()
    {
        return self::DEFAULT_MIGRATION_TEMPLATE;
    }

    /**
     * Returns the seed template filename.
     *
     * @return string
     */
    protected function getSeedTemplateFilename()
    {
        return self::DEFAULT_SEED_TEMPLATE;
    }
}
