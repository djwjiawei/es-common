<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/14
 * Time: 16:09
 */

namespace EsSwoole\Base\Middleware;


use EasySwoole\Http\Request;
use EsSwoole\Base\Abstracts\BaseHttpController;
use EsSwoole\Base\Log\HttpClientLog;
use EsSwoole\Base\Util\RequestUtil;

class RequestMiddleware implements MiddlewareInterface
{
    public function before(Request $request, BaseHttpController $response): bool
    {
        //request注入
        RequestUtil::injectRequest($request);

        //log记录
        HttpClientLog::log([
            'logTag' => '_request_in',
            'fileName' => __FILE__,
            'functionName' => __FUNCTION__,
            'number' => __LINE__,
            'msg' => '==请求开始==',
        ]);
    }

    public function after(Request $request, BaseHttpController $response)
    {
        return true;
    }

}