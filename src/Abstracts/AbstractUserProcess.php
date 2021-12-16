<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/30
 * Time: 11:00
 */

namespace EsSwoole\Base\Abstracts;


use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Trigger;
use EsSwoole\Base\Exception\ExceptionHandler;

abstract class AbstractUserProcess extends AbstractProcess
{
    public function onException(\Throwable $throwable, ...$args)
    {
        //记录日志
        Trigger::getInstance()->throwable($throwable);

        //发送异常邮件
        $processName = $this->getProcessName() ?: '自定义进程';
        ExceptionHandler::report($throwable,"{$processName} 异常: " . $throwable->getMessage());
    }
}