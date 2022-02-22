<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 15:57
 */

namespace EsSwoole\Base\Abstracts;

use EasySwoole\EasySwoole\Config;

/**
 * 抽象服务提供者类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
abstract class AbstractProvider
{
    /**
     * 服务提供者注册,在EasySwooleEvent initialize中触发
     *
     * User: dongjw
     * Date: 2022/2/22 11:38
     */
    public function register()
    {
    }

    /**
     * 服务提供者启动,在EasySwooleEvent mainServerCreate中触发
     *
     * User: dongjw
     * Date: 2022/2/22 11:40
     */
    public function boot()
    {
    }

    /**
     * 将用户配置的config覆盖掉vendor包中的配置
     *
     * @param string $path
     * @param string $key
     *
     * @return bool
     * User: dongjw
     * Date: 2021/11/24 16:03
     */
    public function mergeConfig($path, $key)
    {
        if (file_exists($path)) {
            Config::getInstance()->setConf(
                $key, array_merge(
                    include $path, Config::getInstance()->getConf($key) ?: []
                )
            );

            return true;
        }

        return false;
    }
}
