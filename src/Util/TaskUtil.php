<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/20
 * Time: 14:15
 */

namespace EsSwoole\Base\Util;


use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Task\TaskManager;

class TaskUtil
{

    public static function async($task)
    {
        $taskId = TaskManager::getInstance()->async($task);
        Logger::getInstance()->info('异步投递taskId为:' . $taskId);
        return $taskId;
    }

    public static function sync($task)
    {
        $res = TaskManager::getInstance()->sync($task);
        $logStr = is_array($res) ? json_encode($res) : $res;
        Logger::getInstance()->info('同步投递taskId结果为:' . $logStr);
        return $res;
    }
}