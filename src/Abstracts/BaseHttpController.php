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
use EsSwoole\Base\Middleware\MiddlewareManager;
use EasySwoole\Http\AbstractInterface\Controller;
use EsSwoole\Base\Common\Api;
use EsSwoole\Base\Util\ValidateUtil;

abstract Class BaseHttpController extends Controller
{

    protected function onRequest(?string $action): ?bool
    {
        //执行中间件
        $middlewareBeforeRes = MiddlewareManager::getInstance()->handelBefore($this->request(),$this->response());
        if (!$middlewareBeforeRes) {
            return false;
        }
        return true;
    }

    protected function afterAction(?string $actionName): void
    {
        MiddlewareManager::getInstance()->handelAfter($this->request(),$this->response());
    }

    public function outJson($code = 0,$msg = '',$data = [])
    {
        if (!$this->response()->isEndResponse()) {
            $this->response()->write(json_encode([
                "code" => $code,
                "msg" => $msg,
                "data" => $data
            ],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            return true;
        } else {
            return false;
        }
    }

    public function success($data = [],$msg = '',$code = 0)
    {
        return $this->outJson($code, $msg, $data);
    }

    public function fail($msg = '',$data = [],$code = -1)
    {
        return $this->outJson($code, $msg, $data);
    }

    public function validateGet($rules)
    {
        return $this->validateData($rules,$this->request()->getQueryParams());
    }

    public function validateForm($rules)
    {
        return $this->validateData($rules,$this->request()->getParsedBody());
    }

    public function validateJson($rules)
    {
        return $this->validateData($rules,$this->getJsonData());
    }

    public function validate($rules)
    {
        //将get query和post body与raw数据合并进行校验
        return $this->validateData($rules,$this->getAllInput());
    }

    public function validateData($rules,$params)
    {
        if (!$params) {
            return Api::arr(config('statusCode.param'),'请求参数为空');
        }
        return ValidateUtil::validate($rules,$params);
    }

    public function getJsonData()
    {
        $raw = $this->request()->getBody()->__toString();
        if ($raw) {
            return json_decode($raw, true) ?: [];
        }
        return [];
    }

    public function getAllInput()
    {
        return array_merge($this->request()->getRequestParam() ?: [],$this->getJsonData());
    }

    /**
     * action未找到时 执行的方法
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
     * @param \Throwable $throwable
     * @throws \Throwable
     * User: dongjw
     * Date: 2021/12/15 12:49
     */
    protected function onException(\Throwable $throwable): void
    {
        call_user_func(Di::getInstance()->get(SysConst::HTTP_EXCEPTION_HANDLER),$throwable,$this->request(),$this->response());
    }

}