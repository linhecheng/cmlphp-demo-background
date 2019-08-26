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

/**
 * 数据库迁移-断点
 *
 * @package Cml\Console\Commands\Migrate
 */
class Breakpoint extends AbstractCommand
{
    protected $description = "manage breakpoints";

    protected $arguments = [
    ];

    protected $options = [
        '--t=xxx | --target=xxx' => 'The version number to set or clear a breakpoint against',
        '-r | --remove-all' => 'Remove all breakpoints',
        '--env=xxx' => "the environment [cli, product, development] load accordingly config",
    ];

    protected $help = <<<EOT
The breakpoint command allows you to set or clear a breakpoint against a specific target to inhibit rollbacks beyond a certain target.
If no target is supplied then the most recent migration will be used.
You cannot specify un-migrated targets

php index.php migrate:breakpoint
php index.php migrate:breakpoint --target=20110103081132
php index.php migrate:breakpoint --t=20110103081132
php index.php migrate:breakpoint -r
EOT;

    /**
     * Toggle the breakpoint.
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->bootstrap($args, $options);

        $version = isset($options['target']) ? $options['target'] : $options['t'];
        $removeAll = isset($options['remove-all']) ? $options['remove-all'] : $options['r'];

        if ($version && $removeAll){
            throw new \InvalidArgumentException('Cannot toggle a breakpoint and remove all breakpoints at the same time.');
        }

        // Remove all breakpoints
        if ($removeAll){
            $this->getManager()->removeBreakpoints();
        } else {
            // Toggle the breakpoint.
            $this->getManager()->toggleBreakpoint($version);
        }
    }
}
