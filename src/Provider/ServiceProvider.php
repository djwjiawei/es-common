<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 15:45
 */

namespace EsSwoole\Base\Provider;

use EasySwoole\Component\Singleton;
use EsSwoole\Base\Common\Composer;

class ServiceProvider
{

    use Singleton;

    protected $hasRegist = false;

    protected $hasBoot = false;

    /**
     * 调用vendor包服务提供者的register方法(写在EasySwooleEvent的initialize方法中,可以在该方法中合并配置、初始化工作)
     * @return bool
     * User: dongjw
     * Date: 2021/11/24 15:48
     */
    public function registerVendor()
    {
        if ($this->hasRegist) {
            return false;
        }
        $providerArr = Composer::getInstance()->getProvider();
        foreach ($providerArr as $provider) {
            $obj = new $provider();
            if (method_exists($obj, 'register')) {
                $obj->register();
            }
        }
        return true;
    }

    /**
     * 调用vendor包服务提供者的boot方法(写在EasySwooleEvent的mainServerCreate方法中)
     * @return bool
     * User: dongjw
     * Date: 2021/11/24 15:49
     */
    public function bootVendor()
    {
        if ($this->hasBoot) {
            return false;
        }
        $providerArr = Composer::getInstance()->getProvider();
        foreach ($providerArr as $provider) {
            $obj = new $provider();
            if (method_exists($obj, 'boot')) {
                $obj->boot();
            }
        }
        return true;
    }
}