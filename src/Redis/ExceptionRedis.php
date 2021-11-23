<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 14:57
 */

namespace EsSwoole\Base\Redis;


use EasySwoole\Component\Singleton;

class ExceptionRedis extends AbstractRedisPool
{
    use Singleton;

    //异常key
    const EXCEPTION_KEY = 'EXCEPTION::';

    //过期时间
    protected $timeout = 300;

    public function __construct()
    {
        if (config('esCommon.exception.redis')) {
            $this->connection = config('esCommon.exception.redis');
        }
        if (config('esCommon.exception.mailTimeout')) {
            $this->timeout = config('esCommon.exception.mailTimeout');
        }
    }

    public function check($key)
    {
        return $this->setNx(self::EXCEPTION_KEY . $key,1,$this->timeout);
    }

}