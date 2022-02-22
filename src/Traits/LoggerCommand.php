<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/8
 * Time: 10:37
 */

namespace EsSwoole\Base\Traits;

use EasySwoole\EasySwoole\Logger;

/**
 * Trait LoggerCommand
 *
 * @package EsSwoole\Base\Traits
 */
trait LoggerCommand
{
    protected $logTraceId;

    /**
     * 记录info
     *
     * @param string $msg
     * @param string $category
     * User: dongjw
     * Date: 2022/2/22 18:09
     */
    protected function infoLog($msg, $category = 'info')
    {
        Logger::getInstance()->info($this->formatLog($msg), $category);
    }

    /**
     * 记录error
     *
     * @param string $msg
     * @param string $category
     * User: dongjw
     * Date: 2022/2/22 18:09
     */
    protected function errorLog($msg, $category = 'error')
    {
        Logger::getInstance()->error($this->formatLog($msg), $category);
    }

    /**
     * 格式化日志
     *
     * @param string $msg
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/22 18:09
     */
    protected function formatLog($msg)
    {
        if (!$this->logTraceId) {
            $this->logTraceId = substr(md5(uniqid()), 8, 16);
        }

        return "[{$this->logTraceId}]" . $msg;
    }
}
