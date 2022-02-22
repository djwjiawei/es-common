<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/9
 * Time: 14:31
 */

namespace EsSwoole\Base\Util;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;

/**
 * Class FileUtil
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class FileUtil
{

    /**
     * 创建目录
     *
     * @param string $dir
     * @param int    $permission
     *
     * @throws \Exception
     * User: dongjw
     * Date: 2022/2/22 18:10
     */
    public static function createDir($dir, $permission = 0755)
    {
        if (is_dir($dir)) {
            return;
        }

        set_error_handler([self::class, 'fileErrorHandler']);

        mkdir($dir, $permission, true);

        restore_error_handler();

        if (!is_dir($dir)) {
            throw new \Exception("mkdir {$dir} fail");
        }
    }

    /**
     * 错误处理器
     *
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * User: dongjw
     * Date: 2022/2/22 18:11
     */
    public static function fileErrorHandler($errno, $errstr, $errfile, $errline)
    {
        Logger::getInstance()->console(
            "{$errstr} at file:{$errfile} line:{$errline}", LoggerInterface::LOG_LEVEL_WARNING, 'waring'
        );
    }

}
