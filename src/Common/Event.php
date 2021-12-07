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

class Event extends MultiContainer
{
    use Singleton;

    //自定义进程启动事件
    const USER_PROCESS_START_EVENT = 'processStart';

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