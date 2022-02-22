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

/**
 * Class ConfigLoad
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ConfigLoad
{

    /**
     * 加载配置目录
     *
     * @param string $dir         需要加载的目录
     * @param string $noBeforeDir 不要的目录前缀
     * @param string $allowExt    允许的文件前缀
     *
     * @return bool
     * User: dongjw
     * Date: 2021/12/8 17:44
     */
    public static function loadDir($dir, $noBeforeDir, $allowExt = '')
    {
        $fileArr = File::scanDirectory($dir);
        if (!$fileArr || empty($fileArr['files'])) {
            return false;
        }

        $noBeforeDir = rtrim($noBeforeDir, DIRECTORY_SEPARATOR);
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
            Config::getInstance()->setConf($beforePrefix . $pathinfo['filename'], include $file);
        }

        return true;
    }

    /**
     * 加载一个文件的配置
     *
     * @param string $key
     * @param string $file
     * User: dongjw
     * Date: 2022/1/28 17:55
     */
    public static function loadFile($key, $file)
    {
        //加载配置
        Config::getInstance()->setConf($key, include $file);
    }

}
