<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/25
 * Time: 15:06
 */

namespace EsSwoole\Base\Exception;

use EasySwoole\Http\Request;

/**
 * 发送异常报告
 *
 * @package EsSwoole\Base\Exception
 */
interface ReportInterface
{
    /**
     * 发送报告
     *
     * @param mixed        $config
     * @param Request|null $request
     * @param \Throwable   $exception
     * @param string       $traceId
     * @param string       $msg
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/25 15:16
     */
    public static function report($config, $request, \Throwable $exception, $traceId = '', $msg = '');
}
