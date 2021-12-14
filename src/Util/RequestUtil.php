<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 14:56
 */

namespace EsSwoole\Base\Util;


use EasySwoole\Http\Request;
use Swoole\Coroutine;

class RequestUtil
{
    protected static $requestArr = [];

    /**
     * 注入http请求
     * @param Request $request
     * User: dongjw
     * Date: 2021/8/12 15:10
     */
    public static function injectRequest(Request $request)
    {
        $cid = Coroutine::getCid();
        if ($cid !== -1 && !isset(self::$requestArr[$cid])) {
            //request注入traceId
            $requestTraceId = $request->getHeader('traceid')[0] ?: $request->getRequestParam('traceId');
            $traceId = $requestTraceId ?: substr(md5(uniqid()), 8, 16);
            $request->withAttribute('traceId',$traceId);

            self::$requestArr[$cid] = $request;
            //协程退出时,删除请求信息
            defer(function () use($cid) {
                //这里要用当前协程id
                if (isset(self::$requestArr[$cid])){
                    unset(self::$requestArr[$cid]);
                }
            });
        }
    }

    /**
     * 获取当前请求的request对象,需要注意 如果是在请求的子协程中获取，有可能请求父协程已结束request已经被unset 那就会返回null
     * @return Request|null
     * User: dongjw
     * Date: 2021/8/11 15:39
     */
    public static function getRequest()
    {
        //可能在子协程里获取request，所以这里用顶级协程id获取
        return self::$requestArr[CoroutineUtil::getTopCid()] ?? null;
    }

    /**
     * 是否是json请求
     * @return bool
     * User: dongjw
     * Date: 2021/8/20 16:46
     */
    public static function isJson()
    {
        $request = self::getRequest();
        if ($request) {
            $contentType = $request->getHeader('content-type')[0];
            if ($contentType) {
                foreach (['/json','+json'] as $needle) {
                    if ($needle !== '' && mb_strpos($contentType, $needle) !== false) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function getJsonData()
    {
        $request = self::getRequest();
        if (!$request) {
            return [];
        }
        $raw = $request->getBody()->__toString();
        if ($raw) {
            return json_decode($raw, true) ?: [];
        }
        return [];
    }

    public static function getAllInput($key = '',$default = null)
    {
        $request = self::getRequest();
        if (!$request) {
            return [];
        }
        $rawData = [];
        $raw = $request->getBody()->__toString();
        if ($raw) {
            $rawData = json_decode($raw, true) ?: [];
        }
        $data = array_merge($request->getRequestParam() ?: [],$rawData);
        if ($key) {
            return $data[$key] ?? $default;
        }
        return $data;
    }
}