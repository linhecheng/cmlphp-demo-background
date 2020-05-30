<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Psr7  请求响应通用trait
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use InvalidArgumentException;

/**
 * 请求响应通用trait
 *
 * @package Cml\Http\Message\Psr
 */
trait MessageTrait
{
    /**
     * 头映射
     *
     * @var array
     */
    private $headers = [];

    /**
     * 小写头映射
     *
     * @var array
     */
    private $headerNames = [];

    /**
     * HTTP 协议版本
     *
     * @var string
     */
    private $protocol = '1.1';

    /**
     * @var Stream|null
     */
    private $stream;

    /**
     * 获取字符串形式的 HTTP 协议版本信息。
     *
     * @return string HTTP 协议版本
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }
    /**
     * 返回指定 HTTP 版本号的消息实例。
     *
     * @param string $version HTTP 版本信息
     *
     * @return self
     */
    public function withProtocolVersion($version)
    {
        if ($this->protocol === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * 获取所有的报头信息
     *
     * 返回的二维数组中，第一维数组的「键」代表单条报头信息的名字，「值」是
     * 以数组形式返回的，见以下实例：
     *
     *     // 把「值」的数据当成字串打印出来
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ': ' . implode(', ', $values);
     *     }
     *
     *     // 迭代的循环二维数组
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * 虽然报头信息是没有大小写之分，但是使用 `getHeaders()` 会返回保留了原本
     * 大小写形式的内容。
     *
     * @return string[][]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 检查是否报头信息中包含有此名称的值，不区分大小写
     *
     * @param string $header 不区分大小写的报头信息名称
     *
     * @return bool
     */
    public function hasHeader($header)
    {
        return isset($this->headerNames[strtolower($header)]);
    }

    /**
     * 根据给定的名称，获取一条报头信息，不区分大小写，以数组形式返回
     *
     * @param string $header 不区分大小写的报头字段名称。
     *
     * @return string[] 返回报头信息中，对应名称的，由字符串组成的数组值，如果没有对应
     *  的内容返回空数组。
     */
    public function getHeader($header)
    {
        $header = strtolower($header);
        if (!isset($this->headerNames[$header])) {
            return [];
        }

        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    /**
     * 根据给定的名称，获取一条报头信息，不区分大小写，以逗号分隔的形式返回
     * @notice 注意：不是所有的报头信息都可使用逗号分隔的方法来拼接，对于那些报头信息，请使用
     * `getHeader()` 方法来获取。
     *
     * @param string $header 不区分大小写的报头字段名称。
     *
     * @return string 返回报头信息中，对应名称的，由逗号分隔组成的字串，如果没有对应
     *  的内容，返回空字符串。
     */
    public function getHeaderLine($header)
    {
        return implode(', ', $this->getHeader($header));
    }

    /**
     * 返回替换指定报头信息「键/值」对的消息实例。
     * 此方法必须保留其传参时的大小写状态，并能够在调用 `getHeaders()` 的时候被取出。
     *
     *
     * @param string $header 不区分大小写的报头字段名称。
     * @param string|string[] $value 报头信息或报头信息数组。
     *
     * @return self
     *
     * @throws InvalidArgumentException 无效的报头字段或报头信息时抛出
     */
    public function withHeader($header, $value)
    {
        $value = $this->validateAndTrimHeader($header, $value);
        $normalized = strtolower($header);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }
        $new->headerNames[$normalized] = $header;
        $new->headers[$header] = $value;

        return $new;
    }

    /**
     * 返回一个报头信息增量的 HTTP 消息实例。
     * 原有的报头信息会被保留，新的值会作为增量加上，如果报头信息不存在的话，字段会被加上。
     *
     * @param string $header 不区分大小写的报头字段名称。
     * @param string|string[] $value 报头信息或报头信息数组。
     *
     * @return self
     *
     * @throws InvalidArgumentException 报头字段名称非法时会被抛出。报头头信息的值非法的时候会被抛出
     */
    public function withAddedHeader($header, $value)
    {
        if (!is_string($header) || '' === $header) {
            throw new InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
        }

        $new = clone $this;
        $new->setHeaders([$header => $value]);

        return $new;
    }


    /**
     * 返回被移除掉指定报头信息的 HTTP 消息实例。
     *
     * @param string $header 不区分大小写的头部字段名称。
     *
     * @return self
     */
    public function withoutHeader($header)
    {
        $normalized = strtolower($header);
        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $header = $this->headerNames[$normalized];
        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);

        return $new;
    }

    /**
     * 获取 HTTP 消息的内容。
     *
     * @return Stream 以数据流的形式返回。
     */
    public function getBody()
    {
        if (null === $this->stream) {
            $this->stream = Stream::create('');
        }

        return $this->stream;
    }

    /**
     * 返回指定内容的 HTTP 消息实例。
     *
     * @param Stream $body 数据流形式的内容。
     *
     * @return self
     *
     * @throws InvalidArgumentException 当消息内容不正确的时候抛出。
     */
    public function withBody(Stream $body)
    {
        if ($body === $this->stream) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    /**
     * 设置header头
     *
     * @param array $headers
     */
    private function setHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            $value = $this->validateAndTrimHeader($header, $value);
            $normalized = strtolower($header);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
    }

    /**
     * 校验相关的header头是否符合RFC 7230协议
     * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
     *
     * @param string $header
     * @param mixed $values
     *
     * @return array
     */
    private function validateAndTrimHeader($header, $values)
    {
        if (!is_string($header) || 1 !== preg_match("@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@", $header)) {
            throw new InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
        }

        if (!is_array($values)) {
            // This is simple, just one value.
            if ((!is_numeric($values) && !is_string($values)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string) $values)) {
                throw new InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }

            return [trim((string) $values, " \t")];
        }

        if (empty($values)) {
            throw new InvalidArgumentException('Header values must be a string or an array of strings, empty array given.');
        }

        // Assert Non empty array
        $returnValues = [];
        foreach ($values as $v) {
            if ((!is_numeric($v) && !is_string($v)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string) $v)) {
                throw new InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }

            $returnValues[] = trim((string) $v, " \t");
        }

        return $returnValues;
    }
}
