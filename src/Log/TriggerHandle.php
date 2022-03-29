<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/3/22
 * Time: 13:55
 */

namespace EsSwoole\Base\Log;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;
use EasySwoole\Trigger\Location;
use EasySwoole\Trigger\TriggerInterface;
use EsSwoole\Base\Exception\ErrorException;

class TriggerHandle implements TriggerInterface
{

    //Trigger文件名
    const TRIGGER_NAME = 'trigger';

    public function error($msg, int $errorCode = E_USER_ERROR, Location $location = null)
    {
        if ($location == null) {
            $location = new Location();
            $debugTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $caller = array_shift($debugTrace);
            $location->setLine($caller['line']);
            $location->setFile($caller['file']);
        }

        $exception = new ErrorException($msg, $errorCode, $location->getFile(), $location->getLine());
        Logger::getInstance()->log((string) $exception, $this->errorMapLogLevel($errorCode), self::TRIGGER_NAME);
    }

    public function throwable(\Throwable $throwable)
    {
        Logger::getInstance()->log((string) $throwable, LoggerInterface::LOG_LEVEL_ERROR, self::TRIGGER_NAME);
    }

    public function errorMapLogLevel(int $errorCode)
    {
        switch ($errorCode) {
            case E_PARSE:
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return LoggerInterface::LOG_LEVEL_ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                return LoggerInterface::LOG_LEVEL_WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return LoggerInterface::LOG_LEVEL_NOTICE;
            default :
                return LoggerInterface::LOG_LEVEL_INFO;
        }
    }
}