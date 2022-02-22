<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/6
 * Time: 11:23
 */

namespace EsSwoole\Base\Common;

use EasySwoole\Component\MultiContainer;
use EasySwoole\Component\Singleton;

/**
 * Class Event
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class Event extends MultiContainer
{
    use Singleton;

    /**
     * 自定义进程启动事件
     */
    const USER_PROCESS_START_EVENT = 'processStart';

    /**
     * Hook
     *
     * @param string $event
     * @param mixed  ...$args
     *
     * @return bool|mixed|null
     * User: dongjw
     * Date: 2022/2/22 15:36
     */
    public function hook($event, ...$args)
    {
        $callArr = $this->get($event);
        if (!$callArr) {
            return false;
        }

        foreach ($callArr as $call) {
            if (is_callable($call)) {
                return call_user_func($call, ...$args);
            } else {
                return null;
            }
        }
    }
}
