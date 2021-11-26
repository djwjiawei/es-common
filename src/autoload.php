<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 15:02
 */

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Trigger\Location;
use EsSwoole\Base\Util\RequestUtil;


if (!function_exists('config')) {
    /**
     * 获取配置
     * @param $key
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
     * @param $key
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
     * @return float
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function getCurrentMilliseconds()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}


if (!function_exists('getStartToEndDate')) {
    /**
     * 获取从开始日期到结束日期的数组
     * @param $start 2021-09-01
     * @param $end 2021-10-01
     * @return array
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function getStartToEndDate($start,$end)
    {
        $return = [];
        if ($start <= $end) {
            $return[] = $start;
            while ($start < $end) {
                $start = date('Y-m-d', strtotime('+1 day', strtotime($start)));
                $return[] = $start;
            }
        }

        return $return;
    }
}


if (!function_exists('getEndToStartDate')) {
    /**
     * 获取从结束日期到开始日期的数组
     * @param $end
     * @param $start
     * @return array
     * User: dongjw
     * Date: 2021/11/19 15:03
     */
    function getEndToStartDate($end,$start)
    {
        $return = [];
        if ($end >= $start) {
            $return[] = $end;
            while ($end > $start) {
                $end = date('Y-m-d', strtotime('-1 day', strtotime($end)));
                $return[] = $end;
            }
        }
        return $return;
    }
}

if (!function_exists('getRequestIp')) {
    /**
     * 获取请求ip
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
                return explode(',',$request->getHeaders()['x-forwarded-for'][0])[0];
            }else if ($request->getHeaders()['x-real-ip']) {
                //nginx配置x-real-ip
                return $request->getHeaders()['x-real-ip'][0];
            }else{
                return $request->getServerParams()['remote_addr'] ?? '';
            }
        }
        return '';
    }
}

if (!function_exists('isWorkerProcess')) {
    /**
     * 是否是worker进程
     * @return mixed|string
     * User: dongjw
     * Date: 2021/9/13 17:11
     */
    function isWorkerProcess()
    {
        return \EasySwoole\EasySwoole\ServerManager::getInstance()->getSwooleServer()->worker_id >= 0;
    }
}

if (!function_exists('goTry')) {
    /**
     * 捕获go的异常
     * @return mixed|string
     * User: dongjw
     * Date: 2021/9/13 17:11
     */
    function goTry(callable $callable)
    {
        return go(function ()use($callable){
            try {
                $callable();
            } catch (Throwable $e) {
                $l = new \EasySwoole\Trigger\Location();
                $l->setFile($e->getFile());
                $l->setLine($e->getLine());
                $description = "协程go未捕获异常: " . $e->getMessage();
                \EasySwoole\EasySwoole\Trigger::getInstance()->error($description, E_USER_ERROR, $l);

                //发送邮件
                \EsSwoole\Base\Exception\ExceptionHandler::report($e,$description);
            }
        });
    }
}