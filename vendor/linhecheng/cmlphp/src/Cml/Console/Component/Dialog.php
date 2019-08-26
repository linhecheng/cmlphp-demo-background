<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-121 下午19:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-对话框组件
 * *********************************************************** */
namespace Cml\Console\Component;

use Cml\Console\IO\Output;

/**
 * 命令行工具-对话框组件
 *
 * @package Cml\Console\Component
 */
class Dialog
{
    /**
     * @var mixed
     */
    private static $stty;

    /**
     * @var mixed
     */
    private static $shell;

    /**
     * 提问并获取用户输入
     *
     * @param string $question 问题
     * @param bool $isHidden 是否要隐藏输入
     * @param string $default 默认答案
     * @param bool $displayDefault 是否显示默认答案
     *
     * @return string
     */
    public function ask($question, $isHidden = false, $default = '', $displayDefault = true)
    {
        if ($displayDefault && !empty($default)) {
            $defaultText = $default;
            if (strlen($defaultText) > 30) {
                $defaultText = substr($default, 0, 30) . '...';
            }
            $question .= " [$defaultText]";
        }
        Output::write("$question ");
        return ($isHidden ? $this->askHidden() : trim(fgets(STDIN))) ?: $default;
    }


    /**
     * 隐藏输入如密码等
     *
     * @return string
     */
    private function askHidden()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $exe = __DIR__ . '/bin/hiddeninput.exe';

            // handle code running from a phar
            if ('phar:' === substr(__FILE__, 0, 5)) {
                $tmpExe = sys_get_temp_dir() . '/hiddeninput.exe';
                copy($exe, $tmpExe);
                $exe = $tmpExe;
            }

            $value = rtrim(shell_exec($exe));
            Output::writeln('');

            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            return $value;
        }

        if ($this->hasSttyAvailable()) {
            $sttyMode = shell_exec('stty -g');

            shell_exec('stty -echo');
            $value = fgets(STDIN, 4096);
            shell_exec(sprintf('stty %s', $sttyMode));

            if (false === $value) {
                throw new \RuntimeException('Aborted');
            }

            $value = trim($value);
            Output::writeln('');
            return $value;
        }

        if (false !== $shell = $this->getShell()) {
            $readCmd = $shell === 'csh' ? 'set mypassword = $<' : 'read -r mypassword';
            $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
            $value = rtrim(shell_exec($command));
            Output::writeln('');
            return $value;
        }

        throw new \RuntimeException('Unable to hide the response.');
    }

    /**
     * Returns a valid unix shell.
     *
     * @return string|bool The valid shell name, false in case no valid shell is found
     */
    private function getShell()
    {
        if (null !== self::$shell) {
            return self::$shell;
        }

        self::$shell = false;

        if (file_exists('/usr/bin/env')) {
            // handle other OSs with bash/zsh/ksh/csh if available to hide the answer
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (['bash', 'zsh', 'ksh', 'csh'] as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    self::$shell = $sh;
                    break;
                }
            }
        }

        return self::$shell;
    }

    /**
     * stty是否可用
     *
     * @return bool
     */
    private function hasSttyAvailable()
    {
        if (null !== self::$stty) {
            return self::$stty;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = $exitcode === 0;
    }

    /**
     * 确认对话框
     *
     * <code>
     * if($dialog->confirm('Are you sure?')) { ... }
     * if($dialog->confirm('Your choice?', null, ['a', 'b', 'c'])) { ... }
     * </code>
     *
     * @param string $question 问题
     * @param array $choices 选项
     * @param string $answer 通过的答案
     * @param string $default 默认选项
     * @param string $errorMessage 错误信息
     *
     * @return bool
     */
    public function confirm($question, array $choices = ['Y', 'n'], $answer = 'y', $default = 'y', $errorMessage = 'Invalid choice')
    {
        $text = $question . ' [' . implode('/', $choices) . ']';
        $choices = array_map('strtolower', $choices);
        $answer = strtolower($answer);
        $default = strtolower($default);
        do {
            $input = strtolower($this->ask($text));
            if (in_array($input, $choices)) {
                return $input === $answer;
            } else if (empty($input) && !empty($default)) {
                return $default === $answer;
            }
            Output::writeln($errorMessage);
            return false;
        } while (true);
    }
}
