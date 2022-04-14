<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/23
 * Time: 14:07
 */

namespace EsSwoole\Base\Log;

use EasySwoole\Utility\FileSystem;

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
     * 文件后缀
     *
     * @var string
     */
    protected $fileExtension = '';

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

        //日志根目录
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);

        //日志文件名
        $fileInfoArr    = pathinfo($filename);
        $this->fileName = $fileInfoArr['filename'];

        //日志文件扩展名
        if (isset($fileInfoArr['extension'])) {
            $this->fileExtension = '.' . $fileInfoArr['extension'];
        }

        //设置文件名
        $this->url = canonicalizePath($this->getNewSizeFile(false));

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
        if ($this->nextRotation <= time()) {
            //如果到下次轮转时间了,关闭上个资源,开启下此轮转
            $this->timeMustRotate = true;
            $this->close();
        } elseif (is_resource($this->stream)) {
            //这里需要清除文件缓存,不然filesize会一直保持不变
            clearstatcache(true, $this->url);

            if (filesize($this->url) >= $this->maxFileSize) {
                //如果文件大小到了,直接开启新的资源
                $this->fileMustRotate = true;
                $this->close();
            }
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
            //基于时间的轮转,直接生成下一个时间段的文件
            $this->nextRotation = $this->getNextRotationTime();
            $this->url          = $this->getNewDateFile();

            $this->timeMustRotate = false;
        } elseif ($this->fileMustRotate === true) {
            //基于文件大小的轮转,在当前时间基础上 生成一个新的文件名
            $this->url = $this->getNewSizeFile();

            $this->fileMustRotate = false;
        }
    }

    /**
     * 获取一个新的文件名
     *
     * @return string
     * User: dongjw
     * Date: 2022/3/8 16:53
     */
    protected function getNewDateFile()
    {
        return $this->getWriteDir() . DIRECTORY_SEPARATOR . $this->fileName . $this->getDateFileName(
            ) . $this->fileExtension;
    }

    /**
     * 基于上一个文件大小生成一个新的文件名
     *
     * @param bool $force
     *
     * @return string
     * User: dongjw
     * Date: 2022/3/8 16:54
     */
    protected function getNewSizeFile($force = true)
    {
        //write的目录
        $writeDir = $this->getWriteDir();

        //需要匹配的上一个文件的文件前缀
        $searchBefore = $this->fileName . $this->getDateFileName();

        //遍历目录查找匹配的文件名
        $lastFile = [];
        if (is_dir($writeDir)) {
            $lastFile = $this->getLastFileByScan();
        }

        if (!$lastFile) {
            return $this->getNewDateFile();
        }

        $endFile = $lastFile['file'];

        //不强制生成的话,看上一个文件是否超过大小 没超过的话 直接返回上一个文件名
        if (!$force) {
            $endPath = $writeDir . DIRECTORY_SEPARATOR . $endFile;
            if (filesize($endPath) < $this->maxFileSize) {
                return $endPath;
            }
        }

        //生成一个新的索引值
        if ($lastFile['value']) {
            $newIndexValue = $lastFile['value'] + 1;
        } else {
            $newIndexValue = 1;
        }

        return $writeDir . DIRECTORY_SEPARATOR . $searchBefore . '-' . $newIndexValue . $this->fileExtension;
    }

    /**
     * 获取索引值最大的文件名
     *
     * @return array
     * User: dongjw
     * Date: 2022/3/8 17:46
     */
    protected function getLastFileByScan()
    {
        //需要匹配的上一个文件的文件前缀
        $searchBefore = $this->fileName . $this->getDateFileName();

        //遍历目录
        $fileList = scandir($this->getWriteDir());
        if (!$fileList) {
            return [];
        }

        //匹配的文件列表
        $matchFileArr = [];
        foreach ($fileList as $file) {
            $matchRes = strpos($file, $searchBefore);
            if ($matchRes !== false) {
                $matchFileArr[] = $file;
            }

            if ($matchRes === false && !empty($matchFileArr)) {
                break;
            }
        }

        if (!$matchFileArr) {
            return [];
        }

        $maxIndex = 0;
        $maxValue = '';
        //获取最大的文件索引
        foreach ($matchFileArr as $matchK => $file) {
            //去掉文件名前缀
            $afterFile = substr($file, strlen($searchBefore));

            //去掉后缀
            if ($this->fileExtension) {
                $afterFile = substr($afterFile, 0, -1 * strlen($this->fileExtension));
            }

            $afterFileArr = explode('-', $afterFile);

            //[1]即为当前文件名的索引值
            $fileIndexValue = $afterFileArr[1] ?? '';

            if ($fileIndexValue > $maxValue) {
                $maxIndex = $matchK;
                $maxValue = $fileIndexValue;
            }
        }

        return [
            'file'  => $matchFileArr[$maxIndex],
            'value' => $maxValue,
        ];
    }

    /**
     * 获取写入的目录路径
     *
     * @return string
     * User: dongjw
     * Date: 2022/3/8 18:52
     */
    protected function getWriteDir()
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . date('Y-m-d');
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
                return strtotime(date('Y-m-d H:00:00', strtotime("+{$this->intervalUnit} hour")));

            case 'i':
                return strtotime(date('Y-m-d H:i:00', strtotime("+{$this->intervalUnit} minute")));

            case 's':
                return strtotime("+{$this->intervalUnit} second");

            default:
                return strtotime(date('Y-m-d H:00:00', strtotime("+{$this->intervalUnit} hour")));
        }
    }

    /**
     * 获取对应时间的文件名格式
     *
     * @return string
     * User: dongjw
     * Date: 2022/3/8 18:55
     */
    protected function getDateFileName()
    {
        switch ($this->dateFormat) {
            case 'H':
                return '_' . date('H');

            case 'i':
                return '_' . date('H_i');

            case 's':
                return '_' . date('H_i_s');

            default:
                return '_' . date('H');
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
            $status = (new FileSystem())->makeDirectory($dir, 0777, true, true);

            if (false === $status && !is_dir($dir)) {
                throw new \UnexpectedValueException(
                    sprintf('There is no existing directory at "%s" and it could not be created', $dir)
                );
            }
        }

        $this->dirCreated = true;
    }
}
