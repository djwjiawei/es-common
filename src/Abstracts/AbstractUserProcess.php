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

/**
 * 自定义进程抽象类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
abstract class AbstractUserProcess extends AbstractProcess
{
    /**
     * 触发异常后执行的方法
     *
     * @param \Throwable $throwable
     * @param mixed      ...$args
     * User: dongjw
     * Date: 2022/2/22 13:42
     */
    public function onException(\Throwable $throwable, ...$args)
    {
        //记录日志
        Trigger::getInstance()->throwable($throwable);

        //发送异常邮件
        $processName = $this->getProcessName() ?: '自定义进程';
        ExceptionHandler::report($throwable, "{$processName} 异常: " . $throwable->getMessage());
    }
}
