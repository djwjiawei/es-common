<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/23
 * Time: 9:49
 */

namespace EsSwoole\Base\Exception;

/**
 * Logic断言异常类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class LogicAssertException extends \Exception
{
    /**
     * 错误分组码
     */
    const GROUP_CODE = 100;

    /**
     * 默认错误码
     */
    const DEFAULT_CODE = 302;

    /**
     * 未捕捉的错误码
     */
    const NO_CATCH_CODE = 500;

    /**
     * LogicAssertException constructor.
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct($message = '', $code = 0)
    {
        parent::__construct($message, static::getErrCode($code));
    }

    /**
     * 获取错误码
     *
     * @param int $code
     *
     * @return int
     * User: dongjw
     * Date: 2022/2/22 17:01
     */
    public static function getErrCode($code = 0)
    {
        $errCode = $code > 0 ? $code : static::DEFAULT_CODE;

        return intval(static::GROUP_CODE . $errCode);
    }

}
