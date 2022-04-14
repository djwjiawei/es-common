<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/3/14
 * Time: 10:14
 */

namespace EsSwoole\Base\Abstracts;

use EasySwoole\EasySwoole\Trigger;
use EsSwoole\Base\Exception\ExceptionHandler;

class AbstractTask
{
    /**
     * 异常处理
     *
     * @param \Throwable $throwable
     * @param int        $taskId
     * @param int        $workerIndex
     * User: dongjw
     * Date: 2022/3/14 10:19
     */
    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        //记录日志
        Trigger::getInstance()->throwable($throwable);

        //发送异常邮件
        $taskName = __class__ . ' task任务';
        ExceptionHandler::report($throwable, "{$taskName} 异常: " . $throwable->getMessage());
    }
}