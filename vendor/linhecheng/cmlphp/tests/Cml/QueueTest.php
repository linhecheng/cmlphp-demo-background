<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Queue测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Cml;
use Cml\Config;
use Cml\Queue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testRedisQueue()
    {
        Config::set(
            'redis_cache', [
                'on' => 1,
                'driver' => 'Redis',
                'prefix' => 'deadssm_',
                'server' => [
                    [
                        'host' => $GLOBALS['cache_redis_host'],
                        'port' => $GLOBALS['cache_redis_port'],
                        'password' => $GLOBALS['cache_redis_password']
                    ]
                ]
            ]
        );
        Cml::getContainer()->singleton('cml_queue', Queue\Redis::class);

        Queue::getQueue('redis_cache')->lPush('phpunit-queue', 1);
        $this->assertEquals(Queue::getQueue('redis_cache')->rPop('phpunit-queue'), 1);
    }
}
