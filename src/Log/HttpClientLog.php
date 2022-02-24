<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 15:12
 */

namespace EsSwoole\Base\Log;

use EasySwoole\EasySwoole\Logger as EsLog;
use EsSwoole\Base\Util\RequestUtil;

/**
 * 请求类日志记录
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class HttpClientLog
{

    //Http请求日志标签
    const HTTP_REQUEST = 'http.client';

    //定义日志标签
    const LOG_REQUEST_IN      = '_request_in';
    const LOG_REQUEST_OUT     = '_request_out';
    const LOG_HTTP_REQUEST    = '_http_request';
    const LOG_HTTP_SUCCESS    = '_http_success';
    const LOG_HTTP_FAILURE    = '_http_failure';

    protected static $requestInOut = [
        self::LOG_REQUEST_IN,
        self::LOG_REQUEST_OUT,
    ];

    /**
     * 格式化日志
     *
     * @param array $param
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/22 17:03
     */
    public static function formatHttpLog($param)
    {
        /**
         * $params格式
         * 'logTag' => '_http_success',    //请求标识
         * 'callee' => '',                 //调用第三方地址
         * 'hintContent' => '',            //记录其他内容
         * 'uri' => '',                    //请求uri(_request_in和_request_out会自动记录)
         * 'request' => '',                //请求参数(_request_in和_request_out会自动记录)
         * 'response' => '',               //响应结果
         * 'elapsed' => 10,                //请求耗时(毫秒)
         * 'code' => 0,                    //请求code
         * 'msg' => $res->getErrMsg()      //请求msg
         */

        //日志标签
        $logTag = $param['logTag'] ?: self::LOG_REQUEST_IN;

        //url参数已传入的为准
        $url = $param['uri'] ?? '';

        //请求参数
        $requestParam = $param['request'] ?? '';

        //客户端ip
        $clientIp = getRequestIp();

        //请求host
        $host = '';

        $request = RequestUtil::getRequest();
        //如果是http请求
        if ($request) {
            //域名
            $host = $request->getUri()->getHost() ?? '';

            if (!$url && in_array($logTag, self::$requestInOut)) {
                $url        = $request->getUri()->getPath() ?: '/';
                $queryParam = $request->getUri()->getQuery() ?: '';
                if ($queryParam) {
                    $url = $url . '?' . $queryParam;
                }
            }

            if (!$requestParam && in_array($logTag, self::$requestInOut)) {
                if (RequestUtil::isJson()) {
                    $requestParam = $request->getBody()->__toString();
                } else {
                    $requestParam = $request->getParsedBody();
                }
            }
        }

        if (is_array($requestParam)) {
            $requestParam = json_encode($requestParam, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        //提示文字
        $msg = $param['msg'] ?? '';

        $response = $param['response'] ?? '';

        //接口耗时
        $elapsed = $param['elapsed'] ?? 0;

        //接口返回的状态吗
        $code = $param['code'] ?? '';

        $caller = $param['caller'] ?? '';

        $callee = $param['callee'] ?? '';

        //err_no
        $errNo = $param['errNo'] ?? 0;

        //重试次数
        $retry = $param['retry'] ?? 0;

        //日志
        switch ($logTag) {
            case self::LOG_REQUEST_OUT:
                return $logTag . '||host=' . $host . '||client_ip=' . $clientIp . '||url=' . $url . '||request=' . $requestParam . '||response=' . $response . '||elapsed=' . $elapsed . '||code=' . $code . '||msg=' . $msg;

            case self::LOG_HTTP_SUCCESS:
            case self::LOG_HTTP_FAILURE:
                return $logTag . '||host=' . $host . '||client_ip=' . $clientIp .
                          '||caller=' . $caller . '||callee=' . $callee . '||url=' . $url . '||request=' . $requestParam . '||response=' . $response . '||elapsed=' . $elapsed . '||err_no=' . $errNo . '||code=' . $code . '||msg=' . $msg;

            case self::LOG_HTTP_REQUEST:
                return $logTag . '||host=' . $host . '||client_ip=' . $clientIp. '||url=' . $url . '||request=' . $requestParam . '||uid=' . '||retry=' . $retry . '||msg=' . $msg;

            default:
                return $logTag . '||host=' . $host . '||client_ip=' . $clientIp . '||url=' . $url . '||request=' . $requestParam . '||msg=' . $msg;
        }
    }
}
