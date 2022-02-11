<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/13
 * Time: 14:48
 */

namespace EsSwoole\Base\Util;


use EasySwoole\Validate\Validate;
use EsSwoole\Base\Common\Api;
use EsSwoole\Base\Exception\LogicAssertException;

class ValidateUtil
{
    public static function validate($rules,$params)
    {
        $validate = Validate::make($rules);
        $res = $validate->validate($params);
        if(!$res){
            throw new LogicAssertException($validate->getError()->__toString());
        }
        return $params;
    }
}