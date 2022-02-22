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

/**
 * 异步task助手类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class TaskUtil
{

    /**
     * 异步投递
     *
     * @param mixed $task
     *
     * @return int|null
     * User: dongjw
     * Date: 2022/2/22 18:13
     */
    public static function async($task)
    {
        $taskId = TaskManager::getInstance()->async($task);
        Logger::getInstance()->info('异步投递taskId为:' . $taskId);

        return $taskId;
    }

    /**
     * 同步投递
     *
     * @param mixed $task
     *
     * @return array|int|mixed
     * User: dongjw
     * Date: 2022/2/22 18:14
     */
    public static function sync($task)
    {
        $res    = TaskManager::getInstance()->sync($task);
        $logStr = is_array($res) ? json_encode($res) : $res;
        Logger::getInstance()->info('同步投递taskId结果为:' . $logStr);

        return $res;
    }
}
