<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Socket客户端扩展
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * Socket客户端扩展
 *
 * @package Cml\Vendor
 */
class Socket
{
    protected $config = [
        'persistent'    => false, //持久化
        'host'          => 'localhost',
        'protocol'      => 'tcp', //协议
        'port'          => 80,
        'timeout'       => 30
    ];

    /**
     * 保存连接资源符
     *
     * @var null
     */
    public $connection = null;

    /**
     * 是否建立了连接
     *
     * @var bool
     */
    public $connected = false;

    /**
     * 构造函数
     *
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        if (!is_numeric($this->config['protocol'])) {
            $this->config['protocol'] = getprotobyname($this->config['protocol']);
        }
    }

    /**
     * 创建连接
     *
     * @return resource
     */
    public function connect()
    {
        if ($this->connection != null) {
            $this->disconnect();
        }

        if ($this->config['persistent'] == true) {
            $tmp = null;
            $this->connection = @pfsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
        } else {
            $this->connection = fsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
        }

        if (!empty($errNum) || !empty($errStr)) {
            $this->error($errStr, $errNum);
        }

        $this->connected = is_resource($this->connection);

        return $this->connected;
    }


    /**
     * 错误处理
     *
     * @param string $errStr 错误文本
     * @param int $errNum 错误数字
     *
     * @throws \Exception
     */
    public function error($errStr, $errNum)
    {
        $error = 'fsockopen error errorStr:'.$errStr.';errorNum:'.$errNum;
        throw new \UnexpectedValueException($error);
    }

    /**
     * 向服务端写数据
     *
     * @param mixed $data 发送给服务端的数据
     *
     * @return bool|int
     */
    public function write($data)
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }
        return fwrite($this->connection, $data, strlen($data));
    }

    /**
     * 从服务端读取数据
     *
     * @param int $length 要读取的字节数
     *
     * @return bool|string
     */
    public function read($length = 1024)
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }

        if (!feof($this->connection)) {
            return fread($this->connection, $length);
        } else {
            return false;
        }
    }

    /**
     * 关闭连接
     *
     * @return bool
     */
    public function disconnect()
    {
        if (!is_resource($this->connection)) {
            $this->connected = false;
            return true;
        }
        $this->connected = !fclose($this->connection);

        if (!$this->connected) {
            $this->connection = null;
        }
        return !$this->connected;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->disconnect();
    }

}