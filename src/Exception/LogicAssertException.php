<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/23
 * Time: 9:49
 */

namespace EsSwoole\Base\Exception;


class LogicAssertException extends \Exception
{
    const GROUP_CODE = 100;

    const DEFAULT_CODE = 302;

    const NO_CATCH_CODE = 500;

    public function __construct($message = "", $code = 0)
    {
        parent::__construct($message, static::getErrCode($code));
    }

    public static function getErrCode($code = 0)
    {
        $errCode = $code > 0 ? $code : static::DEFAULT_CODE;
        return intval(static::GROUP_CODE . $errCode);
    }

}