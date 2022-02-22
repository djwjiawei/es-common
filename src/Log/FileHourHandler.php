<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/02/09
 * Time: 16:37
 */

namespace EsSwoole\Base\Log;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Utils;

/**
 * 每小时轮转日志
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class FileHourHandler extends AbstractProcessingHandler
{
    /**
     * Chunck Size
     */
    const MAX_CHUNK_SIZE = 2147483647;

    /**
     * 默认chunk大小 10MB
     */
    const DEFAULT_CHUNK_SIZE = 10 * 1024 * 1024;

    /**
     * Stream Chunk Size
     *
     * @var float|int|mixed
     */
    protected $streamChunkSize;

    /**
     * 文件资源
     *
     * @var resource|null
     */
    protected $stream;

    /**
     * 文件路径
     *
     * @var string|null
     */
    protected $url = null;

    /**
     * 目录是否已创建过
     *
     * @var true|null
     */
    private $dirCreated = null;

    /**
     * 日志根目录
     *
     * @var string
     */
    protected $rootPath;

    /**
     * 文件名
     *
     * @var string
     */
    protected $fileName;

    /**
     * 文件名日期格式
     *
     * @var string
     */
    protected $dateFormat = 'H';

    /**
     * 是否需要轮转
     *
     * @var bool
     */
    protected $mustRotate = false;

    /**
     * 下次轮转时间
     *
     * @var \DateTimeImmutable
     */
    protected $nextRotation;

    /**
     * FileHourHandler constructor.
     *
     * @param string $rootPath
     * @param string $filename
     * @param int    $level
     */
    public function __construct(string $rootPath, string $filename, $level = Logger::DEBUG)
    {
        parent::__construct($level, true);

        //设置chunkSize
        if (($phpMemoryLimit = Utils::expandIniShorthandBytes(ini_get('memory_limit'))) !== false) {
            if ($phpMemoryLimit > 0) {
                // use max 10% of allowed memory for the chunk size, and at least 100KB
                $this->streamChunkSize = min(static::MAX_CHUNK_SIZE, max((int)($phpMemoryLimit / 10), 100 * 1024));
            } else {
                // memory is unlimited, set to the default 10MB
                $this->streamChunkSize = static::DEFAULT_CHUNK_SIZE;
            }
        } else {
            // no memory limit information, set to the default 10MB
            $this->streamChunkSize = static::DEFAULT_CHUNK_SIZE;
        }

        $this->rootPath = $rootPath;

        $this->fileName = $filename;

        //设置文件名
        $this->url = Utils::canonicalizePath($this->getTimedFilename());

        //设置下次轮转时间
        $this->nextRotation = $this->getNextRotationTime();
    }

    /**
     * 获取当前小时的文件名
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/9 16:29
     */
    protected function getTimedFilename(): string
    {
        rtrim($this->rootPath, DIRECTORY_SEPARATOR);

        //处理文件名 + 小时
        $fileInfoArr = pathinfo($this->fileName);

        $timedFilename = $fileInfoArr['filename'] . '_' . date($this->dateFormat);

        if (isset($fileInfoArr['extension'])) {
            $timedFilename .= '.' . $fileInfoArr['extension'];
        }

        //根目录+年月日+文件名
        return $this->rootPath . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR . $timedFilename;
    }

    /**
     * 写日志
     *
     * @param array $record
     * User: dongjw
     * Date: 2022/2/9 16:30
     */
    protected function write(array $record): void
    {
        if ($this->nextRotation <= $record['datetime']) {
            $this->mustRotate = true;
            $this->close();
        }

        $this->writeStream($record);
    }

    /**
     * Fwrite
     *
     * @param array $record
     * User: dongjw
     * Date: 2022/2/22 15:52
     */
    protected function writeStream(array $record): void
    {
        if (!is_resource($this->stream)) {
            $url = $this->url;
            if (null === $url || '' === $url) {
                throw new \LogicException(
                    'Missing stream url, the stream can not be opened. This may be caused by a premature call to close().'
                );
            }

            $this->createDir($url);
            $this->stream = fopen($url, 'a');
            if (!is_resource($this->stream)) {
                $this->stream = null;

                throw new \UnexpectedValueException(
                    sprintf('The stream or file "%s" could not be opened in append mode', $url)
                );
            }

            stream_set_chunk_size($this->stream, $this->streamChunkSize);
        }

        fwrite($this->stream, (string)$record['formatted']);
    }

    /**
     * 关闭日志资源
     * User: dongjw
     * Date: 2022/2/9 16:30
     */
    public function close(): void
    {
        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream     = null;
        $this->dirCreated = null;

        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }

    /**
     * 生成新的小时日志文件
     * User: dongjw
     * Date: 2022/2/9 16:30
     */
    protected function rotate(): void
    {
        $this->url          = $this->getTimedFilename();
        $this->nextRotation = $this->getNextRotationTime();

        $this->mustRotate = false;
    }

    /**
     * 获取下次轮转时间
     *
     * @return \DateTimeImmutable
     * User: dongjw
     * Date: 2022/2/10 11:33
     */
    protected function getNextRotationTime()
    {
        switch ($this->dateFormat) {
            case 'H':
                return new \DateTimeImmutable('+1 hour');

            case 'i':
                return new \DateTimeImmutable('+1 minute');

            case 's':
                return new \DateTimeImmutable('+1 second');

            default:
                return new \DateTimeImmutable('+1 hour');
        }
    }

    /**
     * 获取formatHandle
     *
     * @return FormatterInterface
     * User: dongjw
     * Date: 2022/2/10 11:34
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message%\n", null, false, true
        );
    }

    /**
     * 获取日志文件目录
     *
     * @param string $stream
     *
     * @return string|null
     * User: dongjw
     * Date: 2022/2/10 11:34
     */
    protected function getDirFromStream(string $stream): ?string
    {
        $pos = strpos($stream, '://');
        if ($pos === false) {
            return dirname($stream);
        }

        if ('file://' === substr($stream, 0, 7)) {
            return dirname(substr($stream, 7));
        }

        return null;
    }

    /**
     * 创建日志目录
     *
     * @param string $url
     * User: dongjw
     * Date: 2022/2/10 11:33
     */
    protected function createDir(string $url): void
    {
        if ($this->dirCreated) {
            return;
        }

        $dir = $this->getDirFromStream($url);
        if (null !== $dir && !is_dir($dir)) {
            $status = @mkdir($dir, 0777, true);

            if (false === $status && !is_dir($dir)) {
                throw new \UnexpectedValueException(
                    sprintf('There is no existing directory at "%s" and it could not be created', $dir)
                );
            }
        }

        $this->dirCreated = true;
    }

}
