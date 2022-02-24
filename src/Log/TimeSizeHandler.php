<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/23
 * Time: 14:07
 */

namespace EsSwoole\Base\Log;

/**
 * Class TimeSizeHandler
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class TimeSizeHandler
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
     * 间隔单位
     *
     * @var int
     */
    protected $intervalUnit = 1;

    /**
     * 时间轮转
     *
     * @var bool
     */
    protected $timeMustRotate = false;

    /**
     * 文件大小轮转
     *
     * @var bool
     */
    protected $fileMustRotate = false;

    /**
     * 下次轮转时间
     *
     * @var int
     */
    protected $nextRotation;

    /**
     * 单个文件最大大小(默认1G) 单位为字节
     *
     * @var float|int
     */
    protected $maxFileSize = 1 * 1024 * 1024 * 1024;

    /**
     * TimeSizeHandler constructor.
     *
     * @param string $rootPath
     * @param string $filename
     * @param int    $maxFileSize
     * @param string $dateFormat
     * @param int    $intervalUnit
     */
    public function __construct(string $rootPath, string $filename, int $maxFileSize = 0, string $dateFormat = '', int $intervalUnit = 0)
    {
        //设置chunkSize
        if (($phpMemoryLimit = expandIniShorthandBytes(ini_get('memory_limit'))) !== false) {
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

        if ($maxFileSize) {
            $this->maxFileSize = $maxFileSize;
        }

        if ($dateFormat) {
            $this->dateFormat = $dateFormat;
        }

        if ($intervalUnit) {
            $this->intervalUnit = $intervalUnit;
        }

        $this->rootPath = $rootPath;

        $this->fileName = $filename;

        //设置文件名
        $this->url = canonicalizePath($this->getTimedFilename());

        //设置下次轮转时间
        $this->nextRotation = $this->getNextRotationTime();
    }

    /**
     * 写日志
     *
     * @param string $msg
     * User: dongjw
     * Date: 2022/2/23 18:54
     */
    public function write($msg): void
    {
        //如果到下次轮转时间了,关闭上个资源,开启下此轮转
        if ($this->nextRotation <= time()) {
            $this->timeMustRotate = true;
            $this->fileMustRotate = true;

            $this->close();
        } elseif (is_resource($this->stream) && filesize($this->url) >= $this->maxFileSize) {
            //如果文件大小到了,直接开启新的资源,时间轮转保持不变
            $this->fileMustRotate = true;
            $this->close();
        }

        $this->writeStream($msg);
    }

    /**
     * Fwrite
     *
     * @param string $msg
     * User: dongjw
     * Date: 2022/2/23 18:54
     */
    protected function writeStream($msg): void
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

        fwrite($this->stream, $msg);
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

        if ($this->timeMustRotate === true) {
            $this->nextRotation = $this->getNextRotationTime();

            $this->timeMustRotate = false;
        }

        if ($this->fileMustRotate === true) {
            $this->url = $this->getTimedFilename();

            $this->fileMustRotate = false;
        }
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

        $timedFilename = $fileInfoArr['filename'] . '-' . (new \DateTimeImmutable())->format('H-i-s');

        if (isset($fileInfoArr['extension'])) {
            $timedFilename .= '.' . $fileInfoArr['extension'];
        }

        //根目录+年月日+文件名
        return $this->rootPath . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR . $timedFilename;
    }

    /**
     * 获取下次轮转时间
     *
     * @return int
     * User: dongjw
     * Date: 2022/2/10 11:33
     */
    protected function getNextRotationTime()
    {
        switch ($this->dateFormat) {
            case 'H':
                $date = date('Y-m-d H:00:00', strtotime("++{$this->intervalUnit}} hour"));

                return strtotime($date);

            case 'i':
                $date = date('Y-m-d H:i:00', strtotime("++{$this->intervalUnit}} minute"));

                return strtotime($date);

            case 's':
                return strtotime("+{$this->intervalUnit} second");

            default:
                $date = date('Y-m-d H:00:00', strtotime("++{$this->intervalUnit}} hour"));

                return strtotime($date);
        }
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

        $dir = dirname($url);
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
