<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/11
 * Time: 13:45
 */

namespace EsSwoole\Base\Common;


class Api
{

    public static function arr($code, $msg = '', $data = [])
    {
        return [
           'code' => $code,
           'msg' => $msg,
           'data' => $data
        ];
    }

    public static function success($data = [], $msg = '')
    {
        return [
            'code' => 0,
            'msg' => $msg,
            'data' => $data
        ];
    }

    public static function fail($msg = '', $data = [])
    {
        return [
            'code' => -1,
            'msg' => $msg,
            'data' => $data
        ];
    }

    public static function exception(\Throwable $e)
    {
        return [
            'code' => $e->getCode(),
            'msg' => $e->getMessage(),
            'data' => []
        ];
    }
}