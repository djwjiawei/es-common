<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/1/28
 * Time: 18:29
 */

namespace EsSwoole\Base\Util;

use EasySwoole\Http\Response;

/**
 * Http响应类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ResponseUtil
{
    /**
     * 按返回格式 统一返回json
     *
     * @param Response $response
     * @param int      $code
     * @param string   $msg
     * @param array    $data
     * @param array    $extraData
     *
     * @return bool
     * User: dongjw
     * Date: 2022/1/28 17:38
     */
    public static function outJson(Response $response, $code, $msg, $data, $extraData = [])
    {
        if (!$response->isEndResponse()) {
            $return = [
                'retcode' => $code,
                'errmsg'  => $msg,
                'content' => $data,
            ];
            if ($extraData) {
                $return = array_merge($return, $extraData);
            }

            $response->write(json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $response->withHeader('Content-type', 'application/json;charset=utf-8');

            return true;
        } else {
            return false;
        }
    }
}
