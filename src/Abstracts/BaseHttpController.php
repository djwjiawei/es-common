<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/23
 * Time: 16:14
 */

namespace EsSwoole\Base\Abstracts;


use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Base\Util\RequestUtil;
use EsSwoole\Base\Log\HttpClientLog;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Validate\Validate;
use EsSwoole\Base\Common\Api;

abstract Class BaseHttpController extends Controller
{

    public function onRequest(?string $action): ?bool
    {
        //request注入
        RequestUtil::injectRequest($this->request());

        //log记录
        HttpClientLog::log([
            'logTag' => '_request_in',
            'fileName' => __FILE__,
            'functionName' => __FUNCTION__,
            'number' => __LINE__,
            'msg' => '==请求开始==',
        ]);

        return true;
    }

    public function outJson($code = 0,$msg = '',$data = [])
    {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code" => $code,
                "msg" => $msg,
                "data" => $data
            );
            //log记录
            HttpClientLog::log([
                'logTag' => '_request_out',
                'fileName' => __FILE__,
                'functionName' => __FUNCTION__,
                'number' => __LINE__,
                'code' => $data['code'],
                'response' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'elapsed' => AppUtil::getElapsedTime(),
                'msg' => '==请求结束==||==消耗内存' . AppUtil::getMemoryUsage() . '==',
            ]);
            $this->response()->write(json_encode($data));
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
        return $this->_validate($rules,$this->request()->getQueryParams());
    }

    public function validateForm($rules)
    {
        return $this->_validate($rules,$this->request()->getParsedBody());
    }

    public function validateJson($rules)
    {
        return $this->_validate($rules,json_decode($this->request()->getBody()->__toString(),true) ?: []);
    }

    public function validate($rules)
    {
        //将get query和post body与raw数据合并进行校验
        $params = $this->request()->getRequestParam() ?: [];
        $raw = $this->request()->getBody()->__toString();
        if ($raw) {
            $rawArr = json_decode($raw, true) ?: [];
            $params = array_merge($params, $rawArr);
        }
        return $this->_validate($rules,$params);
    }

    private function _validate($rules,$params)
    {
        if (!$params) {
            return Api::arr(config('statusCode.param'),'请求参数为空');
        }
        $validate = Validate::make($rules);
        $res = $validate->validate($params);
        if(!$res){
            return Api::arr(config('statusCode.param'),$validate->getError()->__toString());
        }
        return Api::arr(config('statusCode.success'),'',$params);
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

}