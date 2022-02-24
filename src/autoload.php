<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 15:02
 */

use EasySwoole\EasySwoole\Config;
use EsSwoole\Base\Util\RequestUtil;
use EsSwoole\Base\Util\TraceIdUtil;

if (!function_exists('config')) {

    /**
     * 获取配置
     *
     * @param string $key
     *
     * @return array|mixed|null
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function config($key)
    {
        return Config::getInstance()->getConf($key);
    }
}

if (!function_exists('configPath')) {

    /**
     * 获取配置文件路径
     *
     * @param string $path
     *
     * @return array|mixed|null
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function configPath($path = '')
    {
        return EASYSWOOLE_ROOT . '/Config' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('getCurrentMilliseconds')) {

    /**
     * 获取当前毫秒时间戳
     *
     * @return float
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function getCurrentMilliseconds()
    {
        list($t1, $t2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}

if (!function_exists('getStartToEndDate')) {

    /**
     * 获取从开始日期到结束日期的数组
     *
     * @param string $start 2021-09-01
     * @param string $end   2021-10-01
     *
     * @return array
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function getStartToEndDate($start, $end)
    {
        $return = [];
        if ($start <= $end) {
            $return[] = $start;
            while ($start < $end) {
                $start    = date('Y-m-d', strtotime('+1 day', strtotime($start)));
                $return[] = $start;
            }
        }

        return $return;
    }
}

if (!function_exists('getEndToStartDate')) {

    /**
     * 获取从结束日期到开始日期的数组
     *
     * @param string $end
     * @param string $start
     *
     * @return array
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function getEndToStartDate($end, $start)
    {
        $return = [];
        if ($end >= $start) {
            $return[] = $end;
            while ($end > $start) {
                $end      = date('Y-m-d', strtotime('-1 day', strtotime($end)));
                $return[] = $end;
            }
        }

        return $return;
    }
}

if (!function_exists('getRequestIp')) {

    /**
     * 获取请求ip
     *
     * @return mixed|string
     * User: dongjw
     * Date: 2021/9/13 17:11
     */
    function getRequestIp()
    {
        $request = RequestUtil::getRequest();
        if ($request) {
            if ($request->getHeaders()['x-forwarded-for']) {
                //有多个代理的情况,nginx 配置X-Forwarded-For
                return explode(',', $request->getHeaders()['x-forwarded-for'][0])[0];
            } elseif ($request->getHeaders()['x-real-ip']) {
                //nginx配置x-real-ip
                return $request->getHeaders()['x-real-ip'][0];
            } else {
                return $request->getServerParams()['remote_addr'] ?? '';
            }
        }

        return '';
    }
}

if (!function_exists('isWorkerProcess')) {

    /**
     * 是否是worker进程
     *
     * @return mixed|string
     * User: dongjw
     * Date: 2021/9/13 17:11
     */
    function isWorkerProcess()
    {
        if (!empty(\EasySwoole\EasySwoole\ServerManager::getInstance()->getSwooleServer())) {
            return \EasySwoole\EasySwoole\ServerManager::getInstance()->getSwooleServer()->worker_id >= 0;
        } else {
            return false;
        }
    }
}

if (!function_exists('goTry')) {

    /**
     * 捕获go的异常
     *
     * @param callable $callable
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/22 16:37
     */
    function goTry(callable $callable)
    {
        return go(
            function () use ($callable) {
                try {
                    $callable();
                } catch (Throwable $e) {
                    $l = new \EasySwoole\Trigger\Location();
                    $l->setFile($e->getFile());
                    $l->setLine($e->getLine());
                    $description = '协程go未捕获异常: ' . $e->getMessage();
                    \EasySwoole\EasySwoole\Trigger::getInstance()->error($description, E_USER_ERROR, $l);

                    //发送邮件
                    \EsSwoole\Base\Exception\ExceptionHandler::report($e, $description);
                }
            }
        );
    }
}

if (!function_exists('retryDuration')) {

    /**
     * 重试指定持续时间，每次间隔多少秒
     *
     * @param callable  $callable
     * @param float|int $duration 持续多长时间/单位秒 最小0.001秒(1毫秒)
     * @param int       $sleep    每次重试间隔多长时间/单位秒  最小0.001秒(1毫秒)
     *
     * @return bool
     * User: dongjw
     * Date: 2021/12/13 19:15
     */
    function retryDuration(callable $callable, $duration = 1, $sleep = 0.1)
    {
        //最小时长 1毫秒
        $minTime = 1;
        try {
            //将秒转化为毫秒
            $duration = $duration * 1000;
            $sleep    = $sleep * 1000;

            //做最小时间比对
            $duration = ($duration < $minTime) ? $minTime : $duration;
            $sleep    = ($sleep < $minTime) ? $minTime : $sleep;

            //重试次数
            $retryTimes = 1;
            $startTime  = getCurrentMilliseconds();

            beginning:
            $res = $callable();
            if ($res) {
                return $res;
            } else {
                throw new \Exception('return false');
            }
        } catch (\Throwable $e) {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            \EasySwoole\EasySwoole\Logger::getInstance()->error(
                "retry exception:{$e->getMessage()} {$debug['file']}:{$debug['line']} retryTimes:{$retryTimes}"
            );

            //持续时间到了，直接return
            if (getCurrentMilliseconds() - $startTime >= $duration) {
                return false;
            }

            $retryTimes++;

            usleep($sleep * 1000);
            goto beginning;
        }
    }
}

if (!function_exists('getTraceId')) {

    /**
     * 获取traceId,如果没有 则生成一个新的traceId
     *
     * @return string
     * User: dongjw
     * Date: 2022/1/7 12:11
     */
    function getTraceId()
    {
        return TraceIdUtil::getCurrentTraceId() ?: TraceIdUtil::generateTraceId();
    }
}

if (!function_exists('strLengthReplace')) {

    /**
     * 字符替换
     *
     * @param string $str
     * @param int    $start   从0开始
     * @param int    $length  替换长度
     * @param string $replace 替换字符
     *
     * @return string
     * Date: 2022/1/17 16:51
     */
    function strLengthReplace($str, $start, $length, $replace = '*')
    {
        return mb_substr($str, 0, $start) . str_repeat($replace, $length) . mb_substr($str, $start + $length);
    }
}

if (!function_exists('strIndexReplace')) {

    /**
     * 按索引替换字符
     *
     * @param string $str
     * @param int    $start
     * @param int    $endIndex
     * @param string $replace
     *
     * @return string
     * User: dongjw
     * Date: 2022/1/17 17:15
     */
    function strIndexReplace($str, $start, $endIndex, $replace = '*')
    {
        $len = mb_strlen($str);
        if ($endIndex > 0) {
            $realEndIndex = $endIndex + 1;
        } else {
            $realEndIndex = $len + $endIndex;
        }

        if ($realEndIndex < $start) {
            return '';
        }

        $repLength = $realEndIndex - $start;

        return mb_substr($str, 0, $start) . str_repeat($replace, $repLength) . mb_substr($str, $endIndex);
    }

}

if (!function_exists('expandIniShorthandBytes')) {
    /**
     * 将一个带单位的大小转换为字节大小
     *
     * @param string $val
     *
     * @return bool|int
     * User: dongjw
     * Date: 2022/2/23 14:18
     */
    function expandIniShorthandBytes($val)
    {
        if (!is_string($val)) {
            return false;
        }

        // support -1
        if ((int)$val < 0) {
            return (int)$val;
        }

        if (!preg_match('/^\s*(?<val>\d+)(?:\.\d+)?\s*(?<unit>[gmk]?)\s*$/i', $val, $match)) {
            return false;
        }

        $val = (int)$match['val'];
        switch (strtolower($match['unit'] ?? '')) {
            //单位为G
            case 'g':
                $val *= 1024;
            //单位为M, 为G的话也会到此
            case 'm':
                $val *= 1024;
            //单位为K, 为G或m的话也会到此
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}

if (!function_exists('canonicalizePath')) {
    /**
     * 返回一个绝对路径
     *
     * @param string $streamUrl
     *
     * @return false|string
     * User: dongjw
     * Date: 2022/2/23 14:19
     */
    function canonicalizePath($streamUrl)
    {
        $prefix = '';
        if ('file://' === substr($streamUrl, 0, 7)) {
            $streamUrl = substr($streamUrl, 7);
            $prefix    = 'file://';
        }

        // other type of stream, not supported
        if (false !== strpos($streamUrl, '://')) {
            return $streamUrl;
        }

        // already absolute
        if (substr($streamUrl, 0, 1) === '/' ||
            substr($streamUrl, 1, 1) === ':' ||
            substr($streamUrl, 0, 2) === '\\\\'
        ) {
            return $prefix . $streamUrl;
        }

        $streamUrl = getcwd() . '/' . $streamUrl;

        return $prefix . $streamUrl;
    }
}
