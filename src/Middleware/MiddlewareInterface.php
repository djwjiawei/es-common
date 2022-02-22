<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/14
 * Time: 15:44
 */

namespace EsSwoole\Base\Middleware;

use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

/**
 * Interface MiddlewareInterface
 *
 * @package EsSwoole\Base\Middleware
 */
interface MiddlewareInterface
{
    /**
     * 控制器方法执行前,返回false时 退出请求
     *
     * @param Request  $request
     * @param Response $response 用来发送响应
     *
     * @return mixed
     * User: dongjw
     * Date: 2021/12/14 15:45
     */
    public function before(Request $request, Response $response): bool;

    /**
     * 控制器方法执行后
     *
     * @param Request  $request
     * @param Response $response 用来发送响应
     *
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 15:46
     */
    public function after(Request $request, Response $response);
}
