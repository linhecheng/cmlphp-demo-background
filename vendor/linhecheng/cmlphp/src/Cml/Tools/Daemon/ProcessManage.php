<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/01/23 17:30
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 守护进程工作进程
 * *********************************************************** */
namespace Cml\Tools\Daemon;

use Cml\Cml;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;
use Cml\Exception\PhpExtendNotInstall;

/**
 * 守护进程工作进程工作类
 *
 * @package Cml\Tools\Daemon
 */
class ProcessManage
{
    private static $pidFile; //pid文件
    private static $log; //log文件
    private static $status; //状态文件
    private static $user = 'nobody'; //用户组

    /**
     *检查是否安装了相关扩展
     *
     */
    private static function checkExtension()
    {
        if (!extension_loaded('posix')) {
            throw new PhpExtendNotInstall('please install PHP posix extension!');
        }

        // 检查扩展
        if (!extension_loaded('pcntl')) {
            throw new PhpExtendNotInstall('please install PHP pcntl extension!');
        }

    }

    /**
     * 向shell输出一条消息
     *
     * @param string $message
     */
    private static function message($message = '')
    {
        $message = sprintf("%s %d %d %s", date('Y-m-d H:i:s'), posix_getpid(), posix_getppid(), $message);
        Output::writeln(Colour::colour($message, Colour::GREEN));
    }

    /**
     * 获取进程id
     *
     * @return int
     */
    private static function getPid()
    {
        if (!is_file(self::$pidFile)) {
            return 0;
        }

        $pid = intval(file_get_contents(self::$pidFile));
        return $pid;
    }

    /**
     * 设置进程名称
     *
     * @param $title
     */
    protected static function setProcessName($title)
    {
        $title = "cmlphp_daemon_{$title}";
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        } elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
            setproctitle($title);
        }
    }

    /**
     * 初始化守护进程
     *
     */
    private static function demonize()
    {
        php_sapi_name() != 'cli' && die('should run in cli');

        umask(0);
        $pid = pcntl_fork();
        if ($pid < 0) {
            die("can't Fork!");
        } else if ($pid > 0) {
            exit();
        }

        if (posix_setsid() === -1) {//使进程成为会话组长。让进程摆脱原会话的控制；让进程摆脱原进程组的控制；
            die('could not detach');
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            die("can't fork2!");
        } elseif ($pid > 0) {
            self::message('start success!');
            exit;
        }

        defined('STDIN') && fclose(STDIN);
        defined('STDOUT') && fclose(STDOUT);
        defined('STDERR') && fclose(STDERR);
        $stdin = fopen(self::$log, 'r');
        $stdout = fopen(self::$log, 'a');
        $stderr = fopen(self::$log, 'a');

        self::setUser(self::$user);

        file_put_contents(self::$pidFile, posix_getpid()) || die("can't create pid file");
        self::setProcessName('master');

        pcntl_signal(SIGINT, ['\\' . __CLASS__, 'signalHandler'], false);
        pcntl_signal(SIGUSR1, ['\\' . __CLASS__, 'signalHandler'], false);

        file_put_contents(self::$status, '<?php return ' . var_export([], true) . ';', LOCK_EX);
        self::createChildrenProcess();

        while (true) {
            pcntl_signal_dispatch();
            $pid = pcntl_wait($status, WUNTRACED);
            pcntl_signal_dispatch();

            if ($pid > 0) {
                $status = self::getStatus();
                if (isset($status['pid'][$pid])) {
                    unset($status['pid'][$pid]);
                    file_put_contents(self::$status, '<?php return ' . var_export($status, true) . ';', LOCK_EX);
                }
                self::createChildrenProcess();
            }
            sleep(1);
        }

        return;
    }

    /**
     * 设置运行的用户
     *
     * @param string $name
     *
     * @return bool
     */
    private static function setUser($name)
    {
        $result = false;
        if (empty($name)) {
            return true;
        }
        $user = posix_getpwnam($name);
        if ($user) {
            $uid = $user['uid'];
            $gid = $user['gid'];
            $result = posix_setuid($uid);
            posix_setgid($gid);
        }
        return $result;

    }

    /**
     * 信号处理
     *
     * @param int $sigNo
     *
     */
    private static function signalHandler($sigNo)
    {
        switch ($sigNo) {
            // stop
            case SIGINT:
                self::signStop();
                break;
            // reload
            case SIGUSR1:
                self::signReload();
                break;
        }
    }

    /**
     * reload
     *
     */
    private static function signReload()
    {
        $pid = self::getPid();
        if ($pid == posix_getpid()) {
            $status = self::getStatus();
            foreach ($status['pid'] as $cid) {
                posix_kill($cid, SIGUSR1);
            }
            $status['pid'] = [];
            file_put_contents(self::$status, '<?php return ' . var_export($status, true) . ';', LOCK_EX);
        } else {
            exit(posix_getpid() . 'reload...');
        }
    }

    /**
     * stop
     *
     */
    private static function signStop()
    {
        $pid = self::getPid();
        if ($pid == posix_getpid()) {
            $status = self::getStatus();
            foreach ($status['pid'] as $cid) {
                posix_kill($cid, SIGINT);
            }
            sleep(3);
            unlink(self::$pidFile);
            unlink(self::$status);
            echo 'stoped' . PHP_EOL;
        }
        exit(posix_getpid() . 'exit...');
    }

    /**
     * 添加任务
     *
     * @param string $task 任务的类名带命名空间
     * @param int $frequency 执行的频率
     *
     * @return void
     */
    public static function addTask($task, $frequency = 60)
    {
        self::initEvn();

        $frequency < 1 || $frequency = 60;

        $task || self::message('task is empty');

        $status = self::getStatus();

        isset($status['task']) || $status['task'] = [];

        $key = md5($task);
        isset($status['task'][$key]) || $status['task'][$key] = [
            'last_runtime' => 0,//上一次运行时间
            'frequency' => $frequency,//执行的频率
            'task' => $task
        ];
        file_put_contents(self::$status, '<?php return ' . var_export($status, true) . ';', LOCK_EX);

        self::message('task nums (' . count($status['task']) . ') list  [' . json_encode($status['task'], JSON_UNESCAPED_UNICODE) . ']');
    }

    /**
     * 删除任务
     *
     * @param string $task 任务的类名带命名空间
     *
     * @return void
     */
    public static function rmTask($task)
    {
        self::initEvn();

        $task || self::message('task name is empty');

        $status = self::getStatus();

        if (!isset($status['task']) || count($status['task']) < 1) {
            self::message('task is empty');
            return;
        }

        $key = md5($task);
        if (isset($status['task'][$key])) {
            unset($status['task'][$key]);
        } else {
            self::message($task . 'task not found');
            return;
        }

        self::message("rm task [{$task}] success");
        file_put_contents(self::$status, '<?php return ' . var_export($status, true) . ';', LOCK_EX);

        self::message('task nums (' . count($status['task']) . ') list  [' . json_encode($status['task'], JSON_UNESCAPED_UNICODE) . ']');
    }

    /**
     * 开始运行
     *
     */
    public static function start()
    {
        self::initEvn();
        if (self::getPid() > 0) {
            self::message('already running...');
        } else {
            self::message('starting...');
            self::demonize();
        }
    }

    /**
     * 检查脚本运气状态
     *
     * @param bool $showInfo 是否直接显示状态
     *
     * @return array|void
     */
    public static function getStatus($showInfo = false)
    {
        self::initEvn();

        $status = is_file(self::$status) ? Cml::requireFile(self::$status) : [];
        if (!$showInfo) {
            return $status;
        }

        if (self::getPid() > 0) {
            self::message('is running');
            self::message('master pid is ' . self::getPid());
            self::message('worker pid is [' . implode($status['pid'], ',') . ']');
            self::message('task nums (' . count($status['task']) . ') list  [' . json_encode($status['task'], JSON_UNESCAPED_UNICODE) . ']');
        } else {
            echo 'not running' . PHP_EOL;
        }
        return null;
    }

    /**
     * reload服务
     *
     */
    public static function reload()
    {
        self::initEvn();
        posix_kill(self::getPid(), SIGUSR1);
        self::message('reloading....');
    }

    /**
     * 终止后台进程
     *
     */
    public static function stop()
    {
        self::initEvn();
        posix_kill(self::getPid(), SIGINT);
        self::message('stop....');
    }

    /**
     * 初始化环境
     *
     */
    private static function initEvn()
    {
        if (!self::$pidFile) {
            self::$pidFile = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . 'DaemonProcess_.pid';
            self::$log = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . 'DaemonProcess_.log';
            self::$status = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . 'DaemonProcessStatus.php';
            self::checkExtension();
        }
    }

    /**
     * shell参数处理并启动守护进程
     *
     * @param string $cmd
     */
    public static function run($cmd)
    {
        self::initEvn();

        $param = is_array($cmd) && count($cmd) == 2 ? $cmd[1] : $cmd;
        switch ($param) {
            case 'start':
                self::start();
                break;
            case 'stop':
                self::stop();
                break;
            case 'reload':
                self::reload();
                break;
            case 'status':
                self::getStatus(true);
                break;
            case 'add-task':
                if (func_num_args() < 1) {
                    self::message('please input task name');
                    break;
                }
                $args = func_get_args();
                $frequency = isset($args[2]) ? intval($args[2]) : 60;
                self::addTask($args[1], $frequency);
                break;
            case 'rm-task':
                if (func_num_args() < 1) {
                    self::message('please input task name');
                    break;
                }
                $args = func_get_args();
                self::rmTask($args[1]);
                break;
            default:
                self::message('Usage: xxx.php cml.cmd DaemonWorker::run {start|stop|status|addtask|rmtask}');
                break;
        }
    }

    /**
     * 创建一个子进程
     *
     */
    protected static function createChildrenProcess()
    {
        $pid = pcntl_fork();

        if ($pid > 0) {
            $status = self::getStatus();
            $status['pid'][$pid] = $pid;
            isset($status['task']) || $status['task'] = [];
            file_put_contents(self::$status, '<?php return ' . var_export($status, true) . ';', LOCK_EX);
        } elseif ($pid === 0) {
            self::setProcessName('worker');
            while (true) {
                pcntl_signal_dispatch();
                $status = self::getStatus();
                if ($status['task']) {
                    foreach ($status['task'] as $key => $task) {
                        if (time() > ($task['last_runtime'] + $task['frequency'])) {
                            $status['task'][$key]['last_runtime'] = time();
                            file_put_contents(self::$status, '<?php return ' . var_export($status, true) . ';', LOCK_EX);
                            call_user_func($task['task']);
                        }
                    }
                    sleep(3);
                } else {
                    sleep(5);
                }
            }
        } else {
            exit('create process error');
        }
    }
}
