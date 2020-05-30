<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 UploadedFileInterface实现
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use InvalidArgumentException;
use RuntimeException;

/**
 * Psr\Http\Message\UploadedFileInterface实现
 * 通过 HTTP 请求上传的一个文件内容。
 *
 * @package Cml\Http\Message\Psr
 */
class UploadedFile
{
    /**
     * 错误信息
     *
     * @var array
     */
    private const ERRORS = [
        UPLOAD_ERR_OK => 1,
        UPLOAD_ERR_INI_SIZE => 1,
        UPLOAD_ERR_FORM_SIZE => 1,
        UPLOAD_ERR_PARTIAL => 1,
        UPLOAD_ERR_NO_FILE => 1,
        UPLOAD_ERR_NO_TMP_DIR => 1,
        UPLOAD_ERR_CANT_WRITE => 1,
        UPLOAD_ERR_EXTENSION => 1,
    ];

    /**
     * @var string
     */
    private $clientFilename;

    /**
     * @var string
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var string|null
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var int
     */
    private $size;

    /**
     * @var Stream|null
     */
    private $stream;

    /**
     * 构造方法
     *
     * @param Stream|string|resource $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     */
    public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null)
    {
        if (false === is_int($errorStatus) || !isset(self::ERRORS[$errorStatus])) {
            throw new InvalidArgumentException('Upload file error status must be an integer value and one of the "UPLOAD_ERR_*" constants.');
        }

        if (false === is_int($size)) {
            throw new InvalidArgumentException('Upload file size must be an integer');
        }

        if (null !== $clientFilename && !is_string($clientFilename)) {
            throw new InvalidArgumentException('Upload file client filename must be a string or null');
        }

        if (null !== $clientMediaType && !is_string($clientMediaType)) {
            throw new InvalidArgumentException('Upload file client media type must be a string or null');
        }

        $this->error = $errorStatus;
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if (UPLOAD_ERR_OK === $this->error) {
            // Depending on the value set file or stream variable.
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } elseif (is_resource($streamOrFile)) {
                $this->stream = Stream::create($streamOrFile);
            } elseif ($streamOrFile instanceof Stream) {
                $this->stream = $streamOrFile;
            } else {
                throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
            }
        }
    }

    /**
     * 是否已经处理完毕|文件上传有错
     *
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive()
    {
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * 获取上传文件的数据流。
     * 此方法的目的在于允许 PHP 对获取到的数据流直接操作，如 stream_copy_to_stream() 。
     *
     * @return Stream 上传文件的数据流
     *
     * @throws RuntimeException 没有数据流的情形下。| 无法创建数据流。
     */
    public function getStream()
    {
        $this->validateActive();

        if ($this->stream instanceof Stream) {
            return $this->stream;
        }

        $resource = fopen($this->file, 'r');

        return Stream::create($resource);
    }

    /**
     * 把上传的文件移动到新目录。
     * 如果你希望操作数据流的话，请使用 `getStream()` 方法，因为在 SAPI 场景下，无法
     * 保证书写入数据流目标。
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     *
     * @param string $targetPath 目标文件路径。可以是相对路径，也可以是绝对路径，使用 rename() 解析起来应该是一样的。
     *
     * @throws InvalidArgumentException 参数有问题时抛出异常。
     * @throws RuntimeException 发生任何错误，都抛出此异常。| 多次运行，也抛出此异常。
     */
    public function moveTo($targetPath)
    {
        $this->validateActive();

        if (!is_string($targetPath) || '' === $targetPath) {
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if (null !== $this->file) {
            $this->moved = 'cli' === PHP_SAPI ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            $dest = Stream::create(fopen($targetPath, 'w'));
            while (!$stream->eof()) {
                if (!$dest->write($stream->read(1048576))) {
                    break;
                }
            }

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath));
        }
    }

    /**
     * 获取文件大小。
     *
     * @return int|null 以 bytes 为单位，或者 null 未知的情况下。
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * 获取上传文件时出现的错误。
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     *
     * @return int PHP 的 UPLOAD_ERR_XXX 常量。
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取客户端上传的文件的名称。
     * @warning 永远不要信任此方法返回的数据，客户端有可能发送了一个恶意的文件名来攻击你的程序。
     *
     * @return string|null 用户上传的名字，或者 null 如果没有此值。
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * 客户端提交的文件类型。
     *
     * @warning  永远不要信任此方法返回的数据，客户端有可能发送了一个恶意的文件类型名称来攻击你的程序。
     *
     * @return string|null 用户上传的类型，或者 null 如果没有此值。
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * 目标是否写入完毕
     *
     * @return bool
     */
    public function isMoved()
    {
        return $this->moved;
    }
}
