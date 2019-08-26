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
 * 数据库迁移-回滚
 *
 * @package Cml\Console\Commands\Migrate
 */
class Rollback extends AbstractCommand
{
    protected $description = "rollback the last or to a specific migration";

    protected $arguments = [
    ];

    protected $options = [
        '--t=xxx | --target=xxx' => 'The version number to rollback to',
        '--d=xxx | --date=xxx' => 'The date to rollback to',
        '-f | --force' => 'Force rollback to ignore breakpoints',
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
        '-e|--e=path|--export=path' => "do not execute sql but save sql to file",
        '-m|--merge' => "merge multi migrate to one sql file",
    ];

    protected $help = <<<EOF
The rollback command reverts the last migration, or optionally up to a specific version

php index.php migrate:rollback
php index.php migrate:rollback --target=20111018185412
php index.php migrate:rollback --t=20111018185412
php index.php migrate:rollback --date=20111018
php index.php migrate:rollback --d=20111018
php index.php migrate:rollback --target=20111018185412 -f

If you have a breakpoint set, then you can rollback to target 0 and the rollbacks will stop at the breakpoint.
php index.php migrate:rollback --target=0
EOF;


    /**
     * 回滚迁移
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->bootstrap($args, $options);

        $version = isset($options['target']) ? $options['target'] : $options['t'];
        $date = isset($options['date']) ? $options['date'] : $options['d'];
        $force = isset($options['force']) ? $options['force'] : $options['f'];

        // rollback the specified environment
        $start = microtime(true);
        if (null !== $date) {
            $this->getManager()->rollbackToDateTime(new \DateTime($date), $force);
        } else {
            $this->getManager()->rollback($version, $force);
        }
        $end = microtime(true);

        Output::writeln('');
        Output::writeln(Colour::colour('All Done. Took ', Colour::GREEN) . sprintf('%.4fs', $end - $start));
    }
}
