<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/3/10
 * Time: 15:45
 */

namespace EsSwoole\Base\Exception;

class RequestException extends LogicAssertException
{
    /**
     * 错误分组码
     */
    const GROUP_CODE = 102;

    /**
     * 默认错误码
     */
    const DEFAULT_CODE = 500;
}