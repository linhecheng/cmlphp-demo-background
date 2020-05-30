<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Psr7  HTTP工厂
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use InvalidArgumentException;
use RuntimeException;

/**
 *  Psr17  HTTP 工厂
 *
 * @package Cml\Http\Message\Psr
 */
class Psr17Factory
{
    /**
     * 创建一个新的请求
     *
     * @param string $method 请求使用的 HTTP 方法。
     * @param Uri|string $uri 请求关联的 URI。
     *
     * @return Request
     */
    public function createRequest($method, $uri)
    {
        return new Request($method, $uri);
    }

    /**
     * 创建一个响应对象。
     *
     * @param int $code HTTP 状态码，默认值为 200。
     * @param string $reasonPhrase 与状态码关联的原因短语。如果未提供，实现 **可能** 使用 HTTP 规范中建议的值。
     *
     * @return Response
     */
    public function createResponse($code = 200, $reasonPhrase = '')
    {
        if (2 > func_num_args()) {
            $reasonPhrase = null;
        }

        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    /**
     * 创建一个服务端请求。
     *
     * 注意服务器参数要精确的按给定的方式获取 - 不执行给定值的解析或处理。
     * 尤其是不要从中尝试获取 HTTP 方法或 URI，这两个信息一定要通过函数参数明确给出。
     *
     * @param string $method 与请求关联的 HTTP 方法。
     * @param Uri|string $uri 与请求关联的 URI。
     * @param array $serverParams 用来生成请求实例的 SAPI 参数。
     *
     * @return ServerRequest
     */
    public function createServerRequest($method, $uri, array $serverParams = [])
    {
        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    /**
     * 从字符串创建一个流。
     *
     * @param string $content 用于填充流的字符串内容。
     *
     * @return Stream
     */
    public function createStream($content = '')
    {
        return Stream::create($content);
    }

    /**
     * 通过现有文件创建一个流。
     *
     * @param string $filename 用作流基础的文件名或 URI。
     * @param string $mode 用于打开基础文件名或流的模式。
     *
     * @return Stream
     * @throws RuntimeException 如果文件无法被打开时抛出。
     * @throws InvalidArgumentException 如果模式无效会被抛出。
     */
    public function createStreamFromFile($filename, $mode = 'r')
    {
        $resource = @fopen($filename, $mode);
        if (false === $resource) {
            if ('' === $mode || false === in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
                throw new InvalidArgumentException('The mode ' . $mode . ' is invalid.');
            }

            throw new RuntimeException('The file ' . $filename . ' cannot be opened.');
        }

        return Stream::create($resource);
    }

    /**
     * 通过现有资源创建一个流。
     *
     * 流必须是可读的并且可能是可写的。
     *
     * @param resource $resource 用作流的基础的 PHP 资源。
     *
     * @return Stream
     */
    public function createStreamFromResource($resource)
    {
        return Stream::create($resource);
    }

    /**
     * 创建一个上传文件接口的对象。
     *
     * 如果未提供大小，将通过检查流的大小来确定。
     *
     * @link http://php.net/manual/features.file-upload.post-method.php
     * @link http://php.net/manual/features.file-upload.errors.php
     *
     * @param Stream $stream 表示上传文件内容的流。
     * @param int $size 文件的大小，以字节为单位。
     * @param int $error PHP 上传文件的错误码。
     * @param string $clientFilename 如果存在，客户端提供的文件名。
     * @param string $clientMediaType 如果存在，客户端提供的媒体类型。
     *
     * @return UploadedFile
     *
     * @throws InvalidArgumentException 如果文件资源不可读时抛出异常。
     */
    public function createUploadedFile(Stream $stream, $size = null, $error = UPLOAD_ERR_OK, $clientFilename = null, $clientMediaType = null)
    {
        if (null === $size) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /**
     * 创建一个 URI。
     *
     * @param string $uri 要解析的 URI。
     *
     * @return Uri
     *
     * @throws InvalidArgumentException 如果给定的 URI 无法被解析时抛出。
     */
    public function createUri(string $uri = '')
    {
        return new Uri($uri);
    }
}
