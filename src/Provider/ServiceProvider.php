<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 15:45
 */

namespace EsSwoole\Base\Provider;

use EasySwoole\Component\Di;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\SysConst;
use EasySwoole\EasySwoole\Trigger;
use EsSwoole\Base\Common\Composer;
use EsSwoole\Base\Common\ConfigLoad;
use EsSwoole\Base\Log\Logger;
use EsSwoole\Base\Log\TriggerHandle;

/**
 * Class ServiceProvider
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ServiceProvider
{

    use Singleton;

    protected $hasRegist = false;

    protected $hasBoot = false;

    protected $providerArr = [];

    /**
     * ServiceProvider constructor.
     */
    public function __construct()
    {
        //加载config目录的配置
        ConfigLoad::loadFile('esCommon', configPath('esCommon.php'));
        ConfigLoad::loadFile('statusCode', configPath('statusCode.php'));

        //协程hook处理
        if (config('esCommon.swooleHook')) {
            \Co::set(['hook_flags' => config('esCommon.swooleHook')]);
        }

        //设置日志handler
        $logger = Logger::getInstance();
        Di::getInstance()->set(SysConst::LOGGER_HANDLER, $logger);
        \EasySwoole\EasySwoole\Logger::getInstance($logger);

        //设置trigger
        $triggerHandle = new TriggerHandle();
        Di::getInstance()->set(SysConst::TRIGGER_HANDLER, $triggerHandle);
        Trigger::getInstance($triggerHandle);

        //替换框架内的AbstractProcess,用来分发进程启动事件
        file_put_contents(
            EASYSWOOLE_ROOT . '/vendor/easyswoole/component/src/Process/AbstractProcess.php',
            file_get_contents(__DIR__ . '/../../Replace/AbstractReplaceProcess.php')
        );

        //发现的服务提供者
        $providerArr = array_values(Composer::getInstance()->getProvider());

        //需要排序的服务提供者
        $configProviderSort = config('esCommon.provider');

        //先按配置的顺序加载
        $sortProvider = array_intersect($configProviderSort, $providerArr);

        //最后再加载剩下的
        $remainProvider = array_diff($providerArr, $configProviderSort);

        $this->providerArr = array_merge($sortProvider, $remainProvider);
    }

    /**
     * 调用vendor包服务提供者的register方法(写在EasySwooleEvent的initialize方法中,可以在该方法中合并配置、初始化工作)
     *
     * @return bool
     * User: dongjw
     * Date: 2021/11/24 15:48
     */
    public function registerVendor()
    {
        if ($this->hasRegist) {
            return false;
        }

        foreach ($this->providerArr as $provider) {
            $obj = new $provider();
            if (method_exists($obj, 'register')) {
                $obj->register();
            }
        }

        return true;
    }

    /**
     * 调用vendor包服务提供者的boot方法(写在EasySwooleEvent的mainServerCreate方法中)
     *
     * @return bool
     * User: dongjw
     * Date: 2021/11/24 15:49
     */
    public function bootVendor()
    {
        if ($this->hasBoot) {
            return false;
        }

        foreach ($this->providerArr as $provider) {
            $obj = new $provider();
            if (method_exists($obj, 'boot')) {
                $obj->boot();
            }
        }

        return true;
    }
}
