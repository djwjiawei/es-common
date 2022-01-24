<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/30
 * Time: 16:13
 */

namespace EsSwoole\Base\Abstracts;


use EasySwoole\Http\Response;

abstract class AbstractMiddleware
{
    public function success(Response $response, $data, $msg = '')
    {
        return $this->outJson($response,0, $msg, $data);
    }

    public function fail(Response $response, $msg, $data = [])
    {
        return $this->outJson($response,-1, $msg, $data);
    }

    public function outJson($response, $code = 0,$msg = '',$data = [])
    {
        if (!$response->isEndResponse()) {
            $response->write(json_encode([
                "code" => $code,
                "msg" => $msg,
                "data" => $data
            ],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $response->withHeader('Content-type', 'application/json;charset=utf-8');
            return true;
        } else {
            return false;
        }
    }
}