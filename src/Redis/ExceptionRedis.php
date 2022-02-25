<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 14:57
 */

namespace EsSwoole\Base\Redis;

use EasySwoole\Component\Singleton;

/**
 * 异常redis类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ExceptionRedis extends AbstractRedisPool
{
    use Singleton;

    //异常key
    const EXCEPTION_KEY = 'EXCEPTION::';

    //过期时间
    protected $timeout = 300;

    /**
     * ExceptionRedis constructor.
     */
    public function __construct()
    {
        if (config('esCommon.exception.redis')) {
            $this->connection = config('esCommon.exception.redis');
        }

        if (config('esCommon.exception.timeout')) {
            $this->timeout = config('esCommon.exception.timeout');
        }
    }

    /**
     * 检验是否可以发送异常
     *
     * @param string $key
     *
     * @return mixed|null
     * User: dongjw
     * Date: 2022/2/22 18:06
     */
    public function check($key)
    {
        return $this->setNx(self::EXCEPTION_KEY . $key, 1, $this->timeout);
    }

}
