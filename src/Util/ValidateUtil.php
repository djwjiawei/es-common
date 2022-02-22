<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/13
 * Time: 14:48
 */

namespace EsSwoole\Base\Util;

use EasySwoole\Validate\Validate;
use EsSwoole\Base\Exception\LogicAssertException;

/**
 * 参数校验助手类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ValidateUtil
{
    /**
     * 校验参数
     *
     * @param array $rules
     * @param array $params
     *
     * @return mixed
     * @throws LogicAssertException
     * @throws \EasySwoole\Validate\Exception\Runtime
     * User: dongjw
     * Date: 2022/2/22 18:15
     */
    public static function validate($rules, $params)
    {
        $validate = Validate::make($rules);
        $res      = $validate->validate($params);
        if (!$res) {
            throw new LogicAssertException($validate->getError()->__toString());
        }

        return $params;
    }
}
