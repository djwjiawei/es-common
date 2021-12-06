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

abstract class AbstractUserProcess extends AbstractProcess
{
    public function onException(\Throwable $throwable, ...$args)
    {
        Trigger::getInstance()->throwable($throwable);
    }
}