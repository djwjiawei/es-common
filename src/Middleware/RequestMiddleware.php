<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/14
 * Time: 16:09
 */

namespace EsSwoole\Base\Middleware;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Log\LoggerInterface;
use EsSwoole\Base\Abstracts\AbstractMiddleware;
use EsSwoole\Base\Log\HttpClientLog;
use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Base\Util\RequestUtil;
use EsSwoole\Base\Util\TraceIdUtil;

/**
 * 请求中间件
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class RequestMiddleware extends AbstractMiddleware
{

    /**
     * 请求之前记录
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     * User: dongjw
     * Date: 2022/2/22 18:00
     */
    public function before(Request $request, Response $response): bool
    {
        //request注入
        RequestUtil::injectRequest($request);

        //注入traceId
        $requestTraceId = $request->getHeader('traceid')[0] ?: $request->getRequestParam('traceId');
        TraceIdUtil::setCurrentTraceId($requestTraceId);

        //请求进来log记录
        $msg = HttpClientLog::formatHttpLog(
            [
                'logTag'       => HttpClientLog::LOG_REQUEST_IN,
                'msg'          => '==请求开始==',
            ]
        );
        Logger::getInstance()->log($msg, LoggerInterface::LOG_LEVEL_INFO, HttpClientLog::HTTP_REQUEST);

        return true;
    }

    /**
     * 请求之后记录
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool|void
     * User: dongjw
     * Date: 2022/2/22 18:00
     */
    public function after(Request $request, Response $response)
    {
        //请求结束log记录
        $msg = HttpClientLog::formatHttpLog(
            [
                'logTag'       => HttpClientLog::LOG_REQUEST_OUT,
                'code'         => $response->getStatusCode(),
                'response'     => $response->getBody()->__toString(),
                'elapsed'      => AppUtil::getElapsedTime(),
                'msg'          => '==请求结束==||==消耗内存' . AppUtil::getMemoryUsage() . '==',
            ]
        );

        Logger::getInstance()->log($msg, LoggerInterface::LOG_LEVEL_INFO, HttpClientLog::HTTP_REQUEST);
    }

}
