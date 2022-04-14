<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/3/11
 * Time: 9:49
 */

namespace EsSwoole\Base\Common;

use EsSwoole\Base\Exception\RequestException;

/**
 * 请求类断言
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class RequestAssert extends Assert
{
    /**
     * 抛出Request类异常
     *
     * @param bool   $bool
     * @param string $message
     * @param int    $errCode
     *
     * @throws RequestException
     * User: dongjw
     * Date: 2022/3/11 09:14
     */
    protected static function throwException($bool, $message, $errCode = 0)
    {
        if ($bool === false) {
            throw new RequestException($message, $errCode);
        }
    }
}