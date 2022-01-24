<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/8
 * Time: 10:37
 */

namespace EsSwoole\Base\Traits;


use EasySwoole\EasySwoole\Logger;

trait LoggerCommand
{
    protected $logTraceId;

    protected function infoLog($msg,$category = 'info')
    {
        Logger::getInstance()->info($this->formatLog($msg),$category);
    }

    protected function errorLog($msg,$category = 'error')
    {
        Logger::getInstance()->error($this->formatLog($msg),$category);
    }

    protected function formatLog($msg)
    {
        if (!$this->logTraceId) {
            $this->logTraceId = substr(md5(uniqid()), 8, 16);
        }
        return "[{$this->logTraceId}]" . $msg;
    }
}