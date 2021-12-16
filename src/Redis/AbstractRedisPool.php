<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 14:56
 */

namespace EsSwoole\Base\Redis;


use EasySwoole\Redis\Redis;
use EasySwoole\RedisPool\RedisPool;

/**
 * 字符串
 * @method set($key, $val, $timeout = 0)
 * @method get($key)
 * @method mGet($keys)
 * @method incr($key)
 * @method incrBy($key, $value)
 * @method decr($key)
 * @method decrBy($key, $value)
 * hash
 * @method hDel($key, ...$field)
 * @method hExists($key, $field)
 * @method hGet($key, $field)
 * @method hSet($key, $field, $value)
 * @method hLen($key)
 * @method hMGet($key, array $hashKeys)
 * @method hMSet($key, $data): bool
 * @method hIncrBy($key, $field, $increment)
 * @method hSetNx($key, $field, $value)
 * list
 * @method lLen($key)
 * @method lPush($key, ...$data)
 * @method lRange($key, $start, $stop)
 * @method lRem($key, $count, $value)
 * @method lSet($key, $index, $value): bool
 * @method lTrim($key, $start, $stop): bool
 * @method rPop($key)
 * @method rPush($key, ...$data)
 * 集合
 * @method sAdd($key, ...$data)
 * @method sCard($key)
 * @method sDiff($key1, ...$keys)
 * @method sInter($key1, ...$keys)
 * @method sIsMember($key, $member)
 * @method sMembers($key)
 * @method sMove($source, $destination, $member)
 * @method sPop($key, $count = 1)
 * @method sRandMember($key, $count = null)
 * @method sRem($key, $member1, ...$members)
 * 有序集合
 * @method zAdd($key, $score1, $member1, ...$data)
 * @method zCard($key)
 * @method zCount($key, $min, $max)
 * @method zInCrBy($key, $increment, $member)
 * @method zRange($key, $start, $stop, $withScores = false)
 * @method zRangeByScore($key, $min, $max, array $options)
 * @method zRank($key, $member)
 * @method zRem($key, $member, ...$members)
 * @method zRemRangeByScore($key, $min, $max)
 * @method zScore($key, $member)
 * key
 * @method exists($key)
 * @method del(...$keys)
 * @method expire($key, $expireTime = 60)
 * @method pExpire($key, $expireTime = 60000)
 * @method expireAt($key, $expireTime)
 * @method ttl($key)
 *
 * @see \EasySwoole\Redis\Redis
 */

abstract class AbstractRedisPool
{

    //使用哪个redis 连接
    protected $connection = 'default';

    //连接池获取连接超时时间
    protected $poolTimeout = 3;

    /**
     * 键不存在时设置
     * @param $key
     * @param $val
     * @param int $expireTime
     * @return mixed|null
     * User: dongjw
     * Date: 2021/9/14 16:25
     */
    public function setNx($key,$val,$expireTime = 0)
    {
        return RedisPool::invoke(function (Redis $redis) use($key,$val,$expireTime) {
            return $redis->set($key, $val, ['NX','EX' => $expireTime]);
        },$this->connection,$this->poolTimeout);
    }

    /**
     * 键已存在时设置
     * @param $key
     * @param $val
     * @return mixed|null
     * User: dongjw
     * Date: 2021/9/14 16:25
     */
    public function setXx($key,$val,$expireTime = 0)
    {
        return RedisPool::invoke(function (Redis $redis) use($key,$val,$expireTime) {
            return $redis->set($key, $val, ['XX','EX' => $expireTime]);
        },$this->connection,$this->poolTimeout);
    }

    /**
     * zadd,如果keu不存在设置过期时间
     * @param $key
     * @param $score
     * @param $member
     * @param $expireTime
     * @return mixed|null
     * User: dongjw
     * Date: 2021/12/14 18:13
     */
    public function zaddLock($key, $score, $member, $expireTime)
    {
        return RedisPool::invoke(function (Redis $redis) use($key, $score, $member, $expireTime) {
            return $redis->rawCommand([
                'eval',
                $this->getZaddExpireLua(),
                '1',
                $key,
                $score,
                $member,
                $expireTime
            ]);
        },$this->connection,$this->poolTimeout);
    }

    protected function getZaddExpireLua()
    {
        return <<<'LUA'
--先判断有序集合是否存在
local exists = redis.call('exists', KEYS[1])
if(exists == 1)
then
    -- 存在,添加有序集合的数据
    return redis.call('zadd', KEYS[1], ARGV[1], ARGV[2])
else
    -- 不存在,添加有序集合的数据 并设置过期时间
    local val = redis.call('zadd', KEYS[1], ARGV[1], ARGV[2])
    redis.call('expire', KEYS[1], ARGV[3])
    return val
end
LUA;
    }

    public function __call($name, $arguments)
    {
        return RedisPool::invoke(function (Redis $redis) use($name,$arguments) {
            return call_user_func_array([$redis, $name], $arguments);
        },$this->connection,$this->poolTimeout);
    }
}