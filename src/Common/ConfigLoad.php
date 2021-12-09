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

    /**
     * 加载配置目录
     * @param $dir string 需要加载的目录
     * @param $noBeforeDir string 不要的目录前缀
     * @param string $filterExt string 允许的文件前缀
     * @return bool
     * User: dongjw
     * Date: 2021/12/8 17:44
     */
    public static function loadDir($dir,$noBeforeDir,$allowExt = '')
    {
        $fileArr = File::scanDirectory($dir);
        if (!$fileArr || empty($fileArr['files'])) {
            return false;
        }
        $noBeforeDir = rtrim($noBeforeDir,DIRECTORY_SEPARATOR);
        foreach ($fileArr['files'] as $file) {
            $pathinfo = pathinfo($file);
            //对扩展名检查
            $extCheckRes = $allowExt ? ($pathinfo['extension'] == $allowExt) : true;
            if (!$extCheckRes) {
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