<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 ServerRequestInterface创建者
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use Cml\Http\Message\HttpFactory;
use InvalidArgumentException;
use RuntimeException;
use \Cml\Http\Message\Request as CmlServerRequest;

/**
 *  处理服务器参数生成psr7的ServerRequest
 *
 * @package Cml\Http\Message\Psr
 */
class ServerRequestCreator
{
    /**
     * @var HttpFactory
     */
    private $serverRequestFactory;

    /**
     * @var HttpFactory
     */
    private $uriFactory;

    /**
     * @var HttpFactory
     */
    private $uploadedFileFactory;

    /**
     * @var HttpFactory
     */
    private $streamFactory;

    public function __construct(
        $serverRequestFactory,
        $uriFactory,
        $uploadedFileFactory,
        $streamFactory
    )
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->uriFactory = $uriFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * 从当前环境变量创建新的服务器请求。
     *
     * @return CmlServerRequest
     *
     * @throws InvalidArgumentException ，如果无法确定有效的方法或URI
     */
    public function fromGlobals()
    {
        $server = $_SERVER;
        if (false === isset($server['REQUEST_METHOD'])) {
            $server['REQUEST_METHOD'] = 'GET';
        }

        $headers = function_exists('getallheaders') ? getallheaders() : static::getHeadersFromServer($_SERVER);

        return $this->fromArrays($server, $headers, $_COOKIE, $_GET, $_POST, $_FILES, fopen('php://input', 'r') ?: null);
    }

    /**
     * Create a new server request from a set of arrays.
     *
     * @param array $server 标准的 $_SERVER 或类似结构
     * @param array $headers getallheaders()的输出 或类似结构
     * @param array $cookie 标准的 $_COOKIE 或类似结构
     * @param array $get 标准的 $_GET 或类似结构
     * @param array $post 标准的 $_POST 或类似结构
     * @param array $files 标准的 $_FILES 或类似结构
     * @param Stream|resource|string|null $body Typically stdIn
     *
     * @return CmlServerRequest
     *
     * @throws InvalidArgumentException if no valid method or URI can be determined
     */
    public function fromArrays(array $server, array $headers = [], array $cookie = [], array $get = [], array $post = [], array $files = [], $body = null)
    {
        $method = $this->getMethodFromEnv($server);
        $uri = $this->getUriFromEnvWithHTTP($server);
        $protocol = isset($server['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL']) : '1.1';

        $serverRequest = $this->serverRequestFactory->createServerRequest($method, $uri, $server);
        foreach ($headers as $name => $value) {
            $serverRequest = $serverRequest->withHeader($name, $value);
        }

        /**
         * @var CmlServerRequest $serverRequest
         */
        $serverRequest = $serverRequest
            ->withProtocolVersion($protocol)
            ->withCookieParams($cookie)
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withUploadedFiles($this->normalizeFiles($files));

        if (null === $body) {
            return $serverRequest;
        }

        if (is_resource($body)) {
            $body = $this->streamFactory->createStreamFromResource($body);
        } elseif (is_string($body)) {
            $body = $this->streamFactory->createStream($body);
        } elseif (!$body instanceof Stream) {
            throw new InvalidArgumentException('The $body parameter to ServerRequestCreator::fromArrays must be string, resource or StreamInterface');
        }

        return $serverRequest->withBody($body);
    }

    /**
     * 从server中获取头
     *
     * @param array $server
     *
     * @return array
     */
    public static function getHeadersFromServer(array $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            // Apache prefixes environment variables with REDIRECT_
            // if they are added by rewrite rules
            if (0 === strpos($key, 'REDIRECT_')) {
                $key = substr($key, 9);

                // We will not overwrite existing variables with the
                // prefixed versions, though
                if (array_key_exists($key, $server)) {
                    continue;
                }
            }

            if ($value && 0 === strpos($key, 'HTTP_')) {
                $name = strtr(strtolower(substr($key, 5)), '_', '-');
                $headers[$name] = $value;

                continue;
            }

            if ($value && 0 === strpos($key, 'CONTENT_')) {
                $name = 'content-' . strtolower(substr($key, 8));
                $headers[$name] = $value;

                continue;
            }
        }

        return $headers;
    }

    /**
     * 获取HTTP请求方法
     *
     * @param array $environment
     *
     * @return mixed
     */
    private function getMethodFromEnv(array $environment)
    {
        if (false === isset($environment['REQUEST_METHOD'])) {
            throw new InvalidArgumentException('Cannot determine HTTP method');
        }

        return $environment['REQUEST_METHOD'];
    }

    /**
     * 获取uri
     *
     * @param array $environment
     *
     * @return Uri
     */
    private function getUriFromEnvWithHTTP(array $environment)
    {
        $uri = $this->createUriFromArray($environment);
        if (empty($uri->getScheme())) {
            $uri = $uri->withScheme('http');
        }

        return $uri;
    }

    /**
     * 返回UploadedFile实例数组
     *
     * @param array $files
     *
     * @return UploadedFile[]
     *
     * @throws InvalidArgumentException
     */
    private function normalizeFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = $this->normalizeFiles($value);
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    /**
     * 从$_FILES解析返回UploadFile实例
     *
     * @param array $value $_FILES struct
     *
     * @return array|UploadedFile
     */
    private function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }

        if (!is_uploaded_file($value['tmp_name'])) {
            throw new InvalidArgumentException('Invalid File');
        }

        try {
            $stream = $this->streamFactory->createStreamFromFile($value['tmp_name']);
        } catch (RuntimeException $e) {
            $stream = $this->streamFactory->createStream();
        }

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            (int)$value['size'],
            (int)$value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * 规范化文件数组
     *
     * @param array $files
     *
     * @return UploadedFile[]
     */
    private function normalizeNestedFileSpec(array $files = [])
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = $this->createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    /**
     * 创建新的Uri
     *
     * @param array $server
     *
     * @return Uri
     */
    private function createUriFromArray(array $server)
    {
        $uri = $this->uriFactory->createUri('');

        if (isset($server['HTTP_X_FORWARDED_PROTO'])) {
            $uri = $uri->withScheme($server['HTTP_X_FORWARDED_PROTO']);
        } else {
            if (isset($server['REQUEST_SCHEME'])) {
                $uri = $uri->withScheme($server['REQUEST_SCHEME']);
            } elseif (isset($server['HTTPS'])) {
                $uri = $uri->withScheme('on' === $server['HTTPS'] ? 'https' : 'http');
            }

            if (isset($server['SERVER_PORT'])) {
                $uri = $uri->withPort($server['SERVER_PORT']);
            }
        }

        if (isset($server['HTTP_HOST'])) {
            if (1 === preg_match('/^(.+)\:(\d+)$/', $server['HTTP_HOST'], $matches)) {
                $uri = $uri->withHost($matches[1])->withPort($matches[2]);
            } else {
                $uri = $uri->withHost($server['HTTP_HOST']);
            }
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['REQUEST_URI'])) {
            $uri = $uri->withPath(current(explode('?', $server['REQUEST_URI'])));
        }

        if (isset($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }
}
