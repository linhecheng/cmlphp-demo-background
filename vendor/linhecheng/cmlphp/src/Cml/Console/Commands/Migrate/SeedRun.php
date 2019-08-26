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
 * 数据库迁移-运行seed
 *
 * @package Cml\Console\Commands\Migrate
 */
class SeedRun extends AbstractCommand
{
    protected $description = "run database seeders";

    protected $arguments = [
        'name' => 'What is the name of the seeder?',
    ];

    protected $options = [
        '--s=xxx | --seed=xxx' => 'What is the name of the seeder?',
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
        '-e|--e=path|--export=path' => "do not execute sql but save sql to file",
        '-m|--merge' => "merge multi migrate to one sql file",
    ];

    protected $help = <<<EOT
The seed:run command runs all available or individual seeders

php index.php seed:run
php index.php seed:run --seed=UserSeeder
php index.php seed:run --s=UserSeeder
php index.php seed:run --s=UserSeeder --s=PermissionSeeder --s=LogSeeder

EOT;

    /**
     * 执行 seeders.
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->bootstrap($args, $options);

        $seedSet = isset($options['seed']) ? $options['seed'] : $options['s'];

        $start = microtime(true);

        if (empty($seedSet)) {
            // run all the seed(ers)
            $this->getManager()->seed();
        } else {
            is_array($seedSet) || $seedSet = [$seedSet];
            // run seed(ers) specified in a comma-separated list of classes
            foreach ($seedSet as $seed) {
                $this->getManager()->seed(trim($seed));
            }
        }

        $end = microtime(true);

        Output::writeln('');
        Output::writeln(Colour::colour('All Done. Took ' . sprintf('%.4fs', $end - $start), Colour::GREEN));
    }
}
