<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/23
 * Time: 16:14
 */

namespace EsSwoole\Base\Abstracts;

use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;
use EsSwoole\Base\Exception\LogicAssertException;
use EsSwoole\Base\Middleware\MiddlewareManager;
use EasySwoole\Http\AbstractInterface\Controller;
use EsSwoole\Base\Util\RequestUtil;
use EsSwoole\Base\Util\ValidateUtil;

/**
 * 抽象控制器类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
abstract class BaseHttpController extends Controller
{

    /**
     * Action执行前 执行的方法
     *
     * @param string|null $action
     *
     * @return bool|null
     * User: dongjw
     * Date: 2022/2/22 14:17
     */
    protected function onRequest(?string $action): ?bool
    {
        if (!method_exists($this, $action)) {
            $this->actionNotFound($action);

            return false;
        }

        //执行中间件
        $middlewareBeforeRes = MiddlewareManager::getInstance()->handelBefore($this->request(), $this->response());
        if (!$middlewareBeforeRes) {
            return false;
        }

        return true;
    }

    /**
     * Action执行完后 执行的方法
     *
     * @param string|null $actionName
     * User: dongjw
     * Date: 2022/1/28 16:51
     */
    protected function afterAction(?string $actionName): void
    {
        MiddlewareManager::getInstance()->handelAfter($this->request(), $this->response());
    }

    /**
     * 输出一个json数据
     *
     * @param int    $code
     * @param string $msg
     * @param array  $data
     *
     * @return bool
     * User: dongjw
     * Date: 2022/1/28 16:51
     */
    public function outJson($code = 0, $msg = '', $data = [])
    {
        return RequestUtil::outJson($this->response(), $code, $msg, $data);
    }

    /**
     * 输出api数据
     *
     * @param array $apiData ['code' => 0, 'msg' => '', 'data' => []]
     *
     * @return bool
     * User: dongjw
     * Date: 2022/1/28 16:50
     */
    public function outApi($apiData)
    {
        return RequestUtil::outJson($this->response(), $apiData['code'], $apiData['msg'], $apiData['data']);
    }

    /**
     * 输出success数据
     *
     * @param array  $data
     * @param string $msg
     *
     * @return bool
     * User: dongjw
     * Date: 2022/1/28 16:49
     */
    public function success($data = [], $msg = '')
    {
        return RequestUtil::outJson($this->response(), config('statusCode.success'), $msg, $data);
    }

    /**
     * 输出fail数据,code为LogicAssertException默认错误码
     *
     * @param string $msg
     * @param array  $data
     *
     * @return bool
     * User: dongjw
     * Date: 2022/1/28 16:49
     */
    public function fail($msg = '', $data = [])
    {
        return RequestUtil::outJson($this->response(), LogicAssertException::getErrCode(), $msg, $data);
    }

    /**
     * 校验get参数
     *
     * @param array $rules
     *
     * @return mixed
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/1/28 16:48
     */
    public function validateGet($rules)
    {
        return $this->validateData($rules, $this->request()->getQueryParams());
    }

    /**
     * 校验post form参数
     *
     * @param array $rules
     *
     * @return mixed
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/1/28 16:48
     */
    public function validateForm($rules)
    {
        return $this->validateData($rules, $this->request()->getParsedBody());
    }

    /**
     * 校验json参数
     *
     * @param array $rules
     *
     * @return mixed
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/1/28 16:48
     */
    public function validateJson($rules)
    {
        return $this->validateData($rules, $this->getJsonData());
    }

    /**
     * 校验所有请求参数
     *
     * @param array $rules
     *
     * @return mixed
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/1/28 16:48
     */
    public function validate($rules)
    {
        //将get query和post body与raw数据合并进行校验
        return $this->validateData($rules, $this->getAllInput());
    }

    /**
     * 校验参数(校验成功后返回校验的参数,失败直接抛出异常)
     *
     * @param array $rules
     * @param array $params
     *
     * @return mixed
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/1/28 16:47
     */
    public function validateData($rules, $params)
    {
        if (!$params) {
            throw new LogicAssertException('请求参数为空');
        }

        return ValidateUtil::validate($rules, $params);
    }

    /**
     * 获取请求中的json数据
     *
     * @return array|mixed
     * User: dongjw
     * Date: 2022/1/28 16:47
     */
    public function getJsonData()
    {
        $raw = $this->request()->getBody()->__toString();
        if ($raw) {
            return json_decode($raw, true) ?: [];
        }

        return [];
    }

    /**
     * 获取request中的所有请求参数
     *
     * @return array
     * User: dongjw
     * Date: 2022/1/28 16:47
     */
    public function getAllInput()
    {
        return array_merge($this->request()->getRequestParam() ?: [], $this->getJsonData());
    }

    /**
     * Action未找到时 执行的方法
     *
     * @param string|null $action
     * User: dongjw
     * Date: 2021/9/13 11:49
     */
    protected function actionNotFound(?string $action)
    {
        $this->fail('no action match');
    }

    /**
     * 重写异常捕获,以便中间件可以拿到异常时的响应
     *
     * @param \Throwable $throwable
     * User: dongjw
     * Date: 2021/12/15 12:49
     */
    protected function onException(\Throwable $throwable): void
    {
        if ($throwable instanceof LogicAssertException) {
            //如果是logic异常,直接输出
            $this->outJson($throwable->getCode(), $throwable->getMessage());
        } else {
            call_user_func(
                Di::getInstance()->get(SysConst::HTTP_EXCEPTION_HANDLER), $throwable, $this->request(),
                $this->response()
            );
        }
    }

}
