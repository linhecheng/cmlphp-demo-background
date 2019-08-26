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

use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;

/**
 * 数据库迁移-执行迁移
 *
 * @package Cml\Console\Commands\Migrate
 */
class Migrate extends AbstractCommand
{

    protected $description = "migrate the database";

    protected $arguments = [
    ];

    protected $options = [
        '--t=xx | --target=xxx' => 'The version number to migrate to',
        '--d=xx | --date=xxx' => 'The date to migrate to',
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
        '-e|--e=path|--export=path' => "do not execute sql but save sql to file",
        '-m|--merge' => "merge multi migrate to one sql file",
    ];

    protected $help = <<<EOT
The migrate command runs all available migrations, optionally up to a specific version

php index.php migrate:run
php index.php migrate:run --target=20110103081132
php index.php migrate:run --t=20110103081132
php index.php migrate:run --date=20110103
php index.php migrate:run --d=20110103
EOT;

    /**
     * 运行迁移
     *
     * @param array $args 参数
     * @param array $options 选项
     *
     * @return int
     */
    public function execute(array $args, array $options = [])
    {
        $this->bootstrap($args, $options);

        $version = isset($options['target']) ? $options['target'] : $options['t'];
        $date = isset($options['date']) ? $options['date'] : $options['d'];

        // run the migrations
        $start = microtime(true);
        if (null !== $date) {
            $this->getManager()->migrateToDateTime(new \DateTime($date));
        } else {
            $this->getManager()->migrate($version);
        }
        $end = microtime(true);

        Output::writeln('');
        Output::writeln(Colour::colour('All Done. Took ' . sprintf('%.4fs', $end - $start), Colour::CYAN));

        return 0;
    }
}
