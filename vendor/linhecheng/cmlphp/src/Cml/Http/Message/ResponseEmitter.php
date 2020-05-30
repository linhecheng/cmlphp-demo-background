<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Psr7 Response 发送
 * *********************************************************** */

namespace Cml\Http\Message;

/**
 * Psr7 Response 发送
 *
 * @package Cml\Http\Message
 */
class ResponseEmitter
{
    /**
     * @var int
     */
    private $responseChunkSize;

    /**
     * 构造函数
     *
     * @param int $responseChunkSize
     */
    public function __construct($responseChunkSize = 4096)
    {
        $this->responseChunkSize = $responseChunkSize;
    }

    /**
     * 发送响应
     *
     * @param Response $response
     *
     * @return void
     */
    public function emit(Response $response)
    {
        $isEmpty = $this->isResponseEmpty($response);
        if (headers_sent() === false) {
            $this->emitStatusLine($response);
            $this->emitHeaders($response);
        }

        if (!$isEmpty) {
            $this->emitBody($response);
        }
    }

    /**
     * 发送响应头
     *
     * @param Response $response
     */
    private function emitHeaders(Response $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            $first = strtolower($name) !== 'set-cookie';
            foreach ($values as $value) {
                $header = sprintf('%s: %s', $name, $value);
                header($header, $first);
                $first = false;
            }
        }
    }

    /**
     * 发送状态行
     *
     * @param Response $response
     */
    private function emitStatusLine(Response $response)
    {
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());
    }

    /**
     * 发送消息体
     *
     * @param Response $response
     */
    private function emitBody(Response $response)
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $amountToRead = (int)$response->getHeaderLine('Content-Length');
        if (!$amountToRead) {
            $amountToRead = $body->getSize();
        }

        if ($amountToRead) {
            while ($amountToRead > 0 && !$body->eof()) {
                $length = min($this->responseChunkSize, $amountToRead);
                $data = $body->read($length);
                echo $data;

                $amountToRead -= strlen($data);

                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (!$body->eof()) {
                echo $body->read($this->responseChunkSize);
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    /**
     * 判断响应是否为空
     *
     * @param Response $response
     *
     * @return bool
     */
    public function isResponseEmpty(Response $response)
    {
        if (in_array($response->getStatusCode(), [204, 205, 304], true)) {
            return true;
        }
        $stream = $response->getBody();
        $seekable = $stream->isSeekable();
        if ($seekable) {
            $stream->rewind();
        }
        return $seekable ? $stream->read(1) === '' : $stream->eof();
    }
}
