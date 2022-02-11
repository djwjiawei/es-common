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

class FileUtil
{

    public static function createDir($dir, $permission = 0755)
    {
        if (is_dir($dir)) {
            return;
        }

        set_error_handler([FileUtil::class, 'fileErrorHandler']);

        mkdir($dir, $permission, true);

        restore_error_handler();

        if (!is_dir($dir)) {
            throw new \Exception("mkdir {$dir} fail");
        }
    }

    public static function fileErrorHandler($errno, $errstr, $errfile, $errline)
    {
        Logger::getInstance()->console("{$errstr} at file:{$errfile} line:{$errline}",LoggerInterface::LOG_LEVEL_WARNING,'waring');
    }

}