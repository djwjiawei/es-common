<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/14
 * Time: 16:09
 */

namespace EsSwoole\Base\Middleware;


use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EsSwoole\Base\Log\HttpClientLog;
use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Base\Util\RequestUtil;

class RequestMiddleware implements MiddlewareInterface
{
    public function before(Request $request, Response $response): bool
    {
        //request注入
        RequestUtil::injectRequest($request);

        //请求进来log记录
        HttpClientLog::log([
            'logTag' => '_request_in',
            'fileName' => __FILE__,
            'functionName' => __FUNCTION__,
            'number' => __LINE__,
            'msg' => '==请求开始==',
        ]);

        return true;
    }

    public function after(Request $request, Response $response)
    {
        //请求结束log记录
        HttpClientLog::log([
            'logTag' => '_request_out',
            'fileName' => __FILE__,
            'functionName' => __FUNCTION__,
            'number' => __LINE__,
            'code' => $response->getStatusCode(),
            'response' => $response->getBody()->__toString(),
            'elapsed' => AppUtil::getElapsedTime(),
            'msg' => '==请求结束==||==消耗内存' . AppUtil::getMemoryUsage() . '==',
        ]);
    }

}