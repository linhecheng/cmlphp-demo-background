<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/12/27 11:44
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Lang测试类
 * *********************************************************** */
namespace tests\Cml;

use Cml\Cache\Memcache;
use Cml\Cache\Redis;
use Cml\Cml;
use Cml\Config;
use Cml\Lock;
use Cml\Logger\File;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{
    public function testFileLock()
    {
        Cml::getContainer()->bind('cml_lock', Lock\File::class);

        $lock1 = Cml::getContainer()->make('cml_lock');
        $lock2 = Cml::getContainer()->make('cml_lock');
        $this->assertEquals($lock1->lock('phpunit_test_lock'), true);
        $this->assertEquals($lock2->lock('phpunit_test_lock'), false);
        $lock1->unlock('phpunit_test_lock');
        $this->assertEquals($lock2->lock('phpunit_test_lock'), true);
    }

    public function testRedisLock()
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

        Cml::getContainer()->bind('cml_log', File::class);
        Cml::getContainer()->bind('cache_redis', Redis::class);
        Cml::getContainer()->bind('cml_lock', Lock\Redis::class);


        $lock1 = Cml::getContainer()->make('cml_lock', 'redis_cache');
        $lock2 = Cml::getContainer()->make('cml_lock', 'redis_cache');
        $this->assertEquals($lock1->lock('phpunit_test_lock'), true);
        $this->assertEquals($lock2->lock('phpunit_test_lock'), false);
        $lock1->unlock('phpunit_test_lock');
        $this->assertEquals($lock2->lock('phpunit_test_lock'), true);
    }

    public function testMemcacheLock()
    {
        Cml::getContainer()->bind('cml_log', File::class);
        Cml::getContainer()->bind('cache_memcache', Memcache::class);
        Cml::getContainer()->bind('cml_lock', Lock\Memcache::class);

        Config::set(
            'memcache_cache', [
                'on' => 1,
                'driver' => 'Memcache',
                'prefix' => 'deadssm_',
                'server' => [
                    [
                        'host' => $GLOBALS['cache_memcache_host'],
                        'port' => $GLOBALS['cache_memcache_port']
                    ]
                ]
            ]
        );

        $lock1 = Cml::getContainer()->make('cml_lock', 'memcache_cache');
        $lock2 = Cml::getContainer()->make('cml_lock', 'memcache_cache');
        $this->assertEquals($lock1->lock('phpunit_test_lock'), true);
        $this->assertEquals($lock2->lock('phpunit_test_lock'), false);
        $lock1->unlock('phpunit_test_lock');
        $this->assertEquals($lock2->lock('phpunit_test_lock'), true);
    }
}
