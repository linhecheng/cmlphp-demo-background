<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 StreamInterface实现
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Psr\Http\Message\StreamInterface实现
 * 描述数据流。实例将包装PHP流; 提供了最常见操作的包装，包括将整个流序列化为字符串。
 *
 * @package Cml\Http\Message\Psr
 */
class Stream
{
    /**
     * 资源引用
     *
     * @var resource|null
     */
    private $stream;

    /**
     * seekable
     *
     * @var bool
     */
    private $seekable;

    /**
     * 是否可读
     *
     * @var bool
     */
    private $readable;

    /**
     * 是否可写
     *
     * @var bool
     */
    private $writable;

    /**
     * uri
     *
     * @var array|mixed|void|null
     */
    private $uri;

    /**
     * size
     *
     * @var int|null
     */
    private $size;

    /**
     * 可读、可写流类型映射
     *
     * @var array
     */
    private const READ_WRITE_HASH = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    /**
     * 创新一个符合 PSR-7 的流
     *
     * @param string|resource|self $body
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function create($body = '')
    {
        if ($body instanceof self) {
            return $body;
        }

        if (is_string($body)) {
            $resource = fopen('php://temp', 'rw+');
            fwrite($resource, $body);
            $body = $resource;
        }

        if (is_resource($body)) {
            $new = new self();
            $new->stream = $body;
            $meta = stream_get_meta_data($new->stream);
            $new->seekable = $meta['seekable'] && 0 === fseek($new->stream, 0, SEEK_CUR);
            $new->readable = isset(self::READ_WRITE_HASH['read'][$meta['mode']]);
            $new->writable = isset(self::READ_WRITE_HASH['write'][$meta['mode']]);
            $new->uri = $new->getMetadata('uri');

            return $new;
        }

        throw new InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface.');
    }

    /**
     * 关闭
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 从头到尾将流中的所有数据读取到字符串。
     * @warning 警告：这可能会尝试将大量数据加载到内存中。
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     *
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }

            return $this->getContents();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * 关闭流和任何底层资源。
     *
     * @return void
     */
    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * 从流中分离任何底层资源。
     *
     * 分离之后，流处于不可用状态。
     *
     * @return resource|null 如果存在的话，返回底层 PHP 流。
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * 如果可知，获取流的数据大小。
     *
     * @return int|null 如果可知，返回以字节为单位的大小，如果未知返回 `null`。
     */
    public function getSize()
    {
        if (null !== $this->size) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    /**
     * 返回当前读/写的指针位置。
     *
     * @return int 指针位置。
     *
     * @throws RuntimeException 产生错误时抛出。
     */
    public function tell()
    {
        if (false === $result = ftell($this->stream)) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * 返回是否位于流的末尾。
     *
     * @return bool
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * 返回流是否可随机读取。
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * 定位流中的指定位置。
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     * @param int $offset 要定位的流的偏移量。
     * @param int $whence 指定如何根据偏移量计算光标位置。有效值与 PHP 内置函数 `fseek()` 相同。
     *     SEEK_SET：设定位置等于 $offset 字节。默认。
     *     SEEK_CUR：设定位置为当前位置加上 $offset。
     *     SEEK_END：设定位置为文件末尾加上 $offset （要移动到文件尾之前的位置，offset 必须是一个负值）。
     *
     * @throws RuntimeException 失败时抛出。
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (-1 === fseek($this->stream, $offset, $whence)) {
            throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    /**
     * 定位流的起始位置。
     *
     * 如果流不可以随机访问，此方法将引发异常；否则将执行 seek(0)。
     *
     * @throws RuntimeException 失败时抛出。
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @see seek()
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * 返回流是否可写。
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * 向流中写数据。
     *
     * @param string $string 要写入流的数据。
     *
     * @return int 返回写入流的字节数。
     *
     * @throws RuntimeException 失败时抛出。
     */
    public function write($string)
    {
        if (!$this->writable) {
            throw new RuntimeException('Cannot write to a non-writable stream');
        }

        $this->size = null;

        if (false === $result = fwrite($this->stream, $string)) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * 返回流是否可读。
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * 从流中读取数据。
     *
     * @param int $length 从流中读取最多 $length 字节的数据并返回。如果数据不足，则可能返回少于 $length 字节的数据。
     *
     * @return string 返回从流中读取的数据，如果没有可用的数据则返回空字符串。
     *
     * @throws RuntimeException 失败时抛出。
     */
    public function read($length)
    {
        if (!$this->readable) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        return fread($this->stream, $length);
    }

    /**
     * 返回字符串中的剩余内容。
     *
     * @return string
     *
     * @throws RuntimeException 如果无法读取则抛出异常。| 如果在读取时发生错误则抛出异常。
     */
    public function getContents()
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Unable to read stream contents');
        }

        if (false === $contents = stream_get_contents($this->stream)) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * 获取流中的元数据作为关联数组，或者检索指定的键。
     *
     * 返回的键与从 PHP 的 stream_get_meta_data() 函数返回的键相同。
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key 要检索的特定元数据。
     *
     * @return array|mixed|null 如果没有键，则返回关联数组。如果提供了键并且找到值，
     *     则返回特定键值；如果未找到键，则返回 null。
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if (null === $key) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
