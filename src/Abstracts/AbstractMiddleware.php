<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/30
 * Time: 16:13
 */

namespace EsSwoole\Base\Abstracts;


use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EsSwoole\Base\Exception\LogicAssertException;
use EsSwoole\Base\Util\RequestUtil;

abstract class AbstractMiddleware
{
    public function success(Response $response, $data, $msg = '')
    {
        return RequestUtil::outJson($response,config('statusCode.success'), $msg, $data);
    }

    public function fail(Response $response, $msg, $data = [])
    {
        return RequestUtil::outJson($response, LogicAssertException::getErrCode(), $msg, $data);
    }

    /**
     * 控制器方法执行前,返回false时 退出请求
     * @param Request $request
     * @param Response $response 用来发送响应
     * @return mixed
     * User: dongjw
     * Date: 2021/12/14 15:45
     */
    abstract public function before(Request $request, Response $response):bool ;

    /**
     * 控制器方法执行后
     * @param Request $request
     * @param Response $response 用来发送响应
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 15:46
     */
    abstract public function after(Request $request, Response $response);
}