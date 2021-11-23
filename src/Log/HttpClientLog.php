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

class HttpClientLog
{

    //定义日志标签
    const LOG_REQUEST_IN = '_request_in';
    const LOG_REQUEST_OUT = '_request_out';
    const LOG_MYSQL_EXCEPTION = '_mysql_exception';
    const LOG_REDIS_EXCEPTION = '_redis_exception';
    const LOG_HTTP_REQUEST = '_http_request';
    const LOG_HTTP_SUCCESS = '_http_success';
    const LOG_HTTP_FAILURE = '_http_failure';
    const LOG_UNDEFINED = '_undefined';

    protected static $requestInOut = [
        self::LOG_REQUEST_IN,
        self::LOG_REQUEST_OUT
    ];

    /**
     * 记录http请求日志
     * @param $param
     * User: dongjw
     * Date: 2021/11/19 15:16
     */
    public static function log($param)
    {
        /**
        $params格式
        'logTag' => '_http_success',    //请求标识
        'callee' => '',                 //调用第三方地址
        'hintContent' => '',            //记录其他内容
        'uri' => '',                    //请求uri(_request_in和_request_out会自动记录)
        'request' => '',                //请求参数(_request_in和_request_out会自动记录)
        'response' => '',               //响应结果
        'fileName' => '',               //文件名
        'functionName' => '',           //方法名
        'number' => 1,                  //行号
        'uid' => 1                      //uid
        'merchantNum' => 1000012        //请求merchantNum
        'elapsed' => 10,                //请求耗时(毫秒)
        'code' => 0,                    //请求code
        'msg' => $res->getErrMsg()      //请求msg
         */
        EsLog::getInstance()->info(
            self::formatHttpLog($param),
            Logger::HTTP_REQUEST
        );
    }

    public static function formatHttpLog($param)
    {
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

        //uid
        $uid = $param['uid'] ?? '';

        $request = RequestUtil::getRequest();
        //如果是http请求
        if ($request) {
            //域名
            $host = $request->getUri()->getHost() ?? '';

            if (!$url && in_array($logTag,self::$requestInOut)) {
                $url = $request->getUri()->getPath() ?: '/';
                $queryParam = $request->getUri()->getQuery() ?: '';
                if ($queryParam) {
                    $url = $url . '?' . $queryParam;
                }
            }

            if (!$requestParam && in_array($logTag,self::$requestInOut)) {
                if (RequestUtil::isJson()) {
                    $requestParam = $request->getBody()->__toString();
                }else{
                    $requestParam = $request->getParsedBody();
                }
            }
        }

        if (is_array($requestParam)) {
            $requestParam = json_encode($requestParam,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }

        //hintContent（格式为数组）
        $hintContent = $param['hintContent'] ?? '';
        if (is_array($hintContent)) {
            $hintContent = json_encode($hintContent,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }

        //提示文字
        $msg = $param['msg'] ?? '';

        //文件名
        $fileName = $param['fileName'] ?? '';

        //方法名
        $functionName = $param['functionName'] ?? '';

        //行号
        $number = $param['number'] ?? '';

        //商家编号
        $merchantNum = $param['merchantNum'] ?? '';

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
                $return = '[' . $fileName . '-' . $functionName . '-' . $number . ']' . $logTag . '||host=' . $host . '||client_ip=' . $clientIp .
                    '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $requestParam . '||uid=' . $uid . '||merchant_num=' . $merchantNum . '||response=' . $response . '||elapsed=' . $elapsed . '||code=' . $code . '||msg=' . $msg;
                break;
            case self::LOG_HTTP_SUCCESS:
            case self::LOG_HTTP_FAILURE:
                $return = '[' . $fileName . '-' . $functionName . '.' . $number . ']' . $logTag . '||host=' . $host . '||client_ip=' . $clientIp .
                    '||caller=' . $caller . '||callee=' . $callee . '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $requestParam . '||uid=' . $uid . '||merchant_num=' . $merchantNum . '||response=' . $response . '||elapsed=' . $elapsed . '||err_no=' . $errNo . '||code=' . $code . '||msg=' . $msg;
                break;
            case self::LOG_HTTP_REQUEST:
                $return = '[' . $fileName . '-' . $functionName . '-' . $number . ']' . $logTag . '||host=' . $host . '||client_ip=' . $clientIp .
                    '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $requestParam . '||uid=' . $uid . '||merchant_num=' . $merchantNum . '||retry=' . $retry . '||msg=' . $msg;
                break;
            default:
                $return = '[' . $fileName . '-' . $functionName . '-' . $number . ']' . $logTag . '||host=' . $host . '||client_ip=' . $clientIp .
                    '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $requestParam . '||uid=' . $uid . '||merchant_num=' . $merchantNum . '||msg=' . $msg;
        }
        return $return;
    }
}