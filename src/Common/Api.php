<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/11
 * Time: 13:45
 */

namespace EsSwoole\Base\Common;

/**
 * Class Api
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class Api
{

    /**
     * 返回响应数组
     *
     * @param int    $code
     * @param string $msg
     * @param array  $data
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/22 15:15
     */
    public static function arr($code, $msg = '', $data = [])
    {
        return [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];
    }

    /**
     * 返回响应成功的数组
     *
     * @param array  $data
     * @param string $msg
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/22 15:16
     */
    public static function success($data = [], $msg = '')
    {
        return [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
        ];
    }

    /**
     * 返回响应失败的数组
     *
     * @param string $msg
     * @param array  $data
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/22 15:16
     */
    public static function fail($msg = '', $data = [])
    {
        return [
            'code' => -1,
            'msg'  => $msg,
            'data' => $data,
        ];
    }

    /**
     * 返回响应异常的数组
     *
     * @param \Throwable $e
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/22 15:16
     */
    public static function exception(\Throwable $e)
    {
        return [
            'code' => $e->getCode(),
            'msg'  => $e->getMessage(),
            'data' => [],
        ];
    }
}
