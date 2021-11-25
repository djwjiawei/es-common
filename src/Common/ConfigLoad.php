<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/25
 * Time: 10:04
 */

namespace EsSwoole\Base\Common;


use EasySwoole\EasySwoole\Config;
use EasySwoole\Utility\File;

class ConfigLoad
{

    public static function loadDir($dir,$noBeforeDir)
    {
        $fileArr = File::scanDirectory($dir);
        if (!$fileArr || empty($fileArr['files'])) {
            return false;
        }
        foreach ($fileArr['files'] as $file) {
            $pathinfo = pathinfo($file);
            //只对文件扩展名是php的加载
            if ($pathinfo['extension'] != 'php') {
                continue;
            }
            //替换不要的前缀dir
            $beforePrefix = '';
            if ($replaceDir = trim(str_replace($noBeforeDir, '', $pathinfo['dirname']), DIRECTORY_SEPARATOR)) {
                $beforePrefix = str_replace(DIRECTORY_SEPARATOR, '.', $replaceDir) . '.';
            }
            //加载配置
            Config::getInstance()->setConf($beforePrefix . $pathinfo['filename'],require $file);
        }
        return true;
    }

}