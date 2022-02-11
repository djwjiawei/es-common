<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 15:01
 */

namespace EsSwoole\Base\Util;


class AppUtil
{

    //测试环境
    const DEV_ENV = 'dev';

    //生产环境
    const PROD_ENV = 'prod';

    /**
     * 是否是生产环境
     * @return bool
     * User: dongjw
     * Date: 2021/11/3 14:31
     */
    public static function isProd()
    {
        return config('APP_ENV') == self::PROD_ENV ? true :false;
    }

    /**
     * 获取当前请求执行时间
     * @param int $decimals
     * @return int|string
     * User: dongjw
     * Date: 2021/11/22 14:29
     */
    public static function getElapsedTime($decimals = 2)
    {
        $request = RequestUtil::getRequest();
        if ($request) {
            $requestTime = $request->getServerParams()['request_time_float'];
            return number_format(1000*(microtime(true) - $requestTime), $decimals, '.', '');
        }
        return 0;
    }

    /**
     * 获取当前占用内存
     * @param int $precision
     * @return string
     * User: dongjw
     * Date: 2021/11/22 14:29
     */
    public static function getMemoryUsage($precision = 2)
    {
        $size = memory_get_usage(true);
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), $precision) . $unit[$i];
    }

}