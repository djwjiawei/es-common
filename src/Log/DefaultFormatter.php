<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/23
 * Time: 15:24
 */

namespace EsSwoole\Base\Log;

/**
 * 默认格式化日志类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class DefaultFormatter implements FormatterInterface
{
    /**
     * 格式化消息
     *
     * @param string $msg
     * @param string $level
     * @param bool   $isConsole
     * @param array  $location
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/23 15:26
     */
    public function format($msg, $level, $isConsole, $location = [])
    {
        //[%date{yyyy-MM-dd HH:mm:ss.SSS}][%level][%X{traceId}][%file:%line] %msg%n
        $file = $location['file'] ?? '';
        $line = $location['line'] ?? '';

        if (!$isConsole) {
            $msg = str_replace(["\r\n", "\r", "\n"], ' ', $msg);
        }

        return '[' . $this->getMillisecondsDate() . ']' .
               '[' . $level . ']' .
               '[' . getTraceId() . ']' .
               '[' . $file . ':' . $line . '] ' .
               $msg . PHP_EOL;
    }

    /**
     * 获取当前毫秒日期
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/23 16:07
     */
    protected function getMillisecondsDate()
    {
        $time = new \DateTimeImmutable();

        return $time->format('Y-m-d H:i:s:') . substr($time->format('u'), 0, 3);
    }

}
