<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/23
 * Time: 15:35
 */

namespace EsSwoole\Base\Util;

use Swoole\Coroutine;

/**
 * Class TraceIdUtil
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class TraceIdUtil
{
    protected static $traceIdArr = [];

    /**
     * 设置当前协程的traceid
     *
     * @param string $traceId
     *
     * @return bool
     * User: dongjw
     * Date: 2022/2/23 18:56
     */
    public static function setCurrentTraceId($traceId = '')
    {
        $cid = Coroutine::getCid();
        if ($cid === -1) {
            return false;
        }

        self::$traceIdArr[$cid] = $traceId ?: self::generateTraceId();
        defer(
            function () use ($cid) {
                //这里要用当前协程id
                if (isset(self::$traceIdArr[$cid])) {
                    unset(self::$traceIdArr[$cid]);
                }
            }
        );

        return true;
    }

    /**
     * 获取当前协程的traceId
     *
     * @return mixed|null
     * User: dongjw
     * Date: 2022/2/23 15:41
     */
    public static function getCurrentTraceId()
    {
        return self::$traceIdArr[CoroutineUtil::getTopCid()] ?? null;
    }

    /**
     * 生成一个traceId
     *
     * @return false|string
     * User: dongjw
     * Date: 2022/2/23 15:54
     */
    public static function generateTraceId()
    {
        return substr(md5(uniqid()), 8, 16);
    }
}
