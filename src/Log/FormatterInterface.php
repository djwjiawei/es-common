<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/23
 * Time: 15:25
 */

namespace EsSwoole\Base\Log;

/**
 * 日志格式化接口类
 *
 * @package EsSwoole\Base\Log
 */
interface FormatterInterface
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
    public function format($msg, $level, $isConsole, $location = []);
}
