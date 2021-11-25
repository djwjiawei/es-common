<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 15:57
 */

namespace EsSwoole\Base\Abstracts;


use EasySwoole\EasySwoole\Config;

abstract class AbstractProvider
{

    public function register()
    {

    }

    public function boot()
    {

    }

    /**
     * 将用户配置的config覆盖掉vendor包中的配置
     * @param $path
     * @param $key
     * @return bool
     * User: dongjw
     * Date: 2021/11/24 16:03
     */
    public function mergeConfig($path,$key)
    {
        if (file_exists($path)) {
            Config::getInstance()->setConf($key,array_merge(
                require $path,Config::getInstance()->getConf($key) ?: []
            ));
            return true;
        }
        return false;
    }
}