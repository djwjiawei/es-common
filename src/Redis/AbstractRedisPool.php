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

abstract class AbstractRedisPool
{

    //使用哪个redis 连接
    protected $connection = 'default';

    //连接池获取连接超时时间
    protected $poolTimeout = 3;

    public function set($key,$val,$expireTime = 0)
    {
        return RedisPool::invoke(function (Redis $redis) use($key,$val,$expireTime) {
            return $redis->set($key, $val, $expireTime);
        },$this->connection,$this->poolTimeout);
    }

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
    public function setXx($key,$val)
    {
        return RedisPool::invoke(function (Redis $redis) use($key,$val) {
            return $redis->set($key, $val, ['XX']);
        },$this->connection,$this->poolTimeout);
    }

    public function get($key)
    {
        return RedisPool::invoke(function (Redis $redis) use($key) {
            return $redis->get($key);
        },$this->connection,$this->poolTimeout);
    }

    public function ttl($key)
    {
        return RedisPool::invoke(function (Redis $redis) use($key) {
            return $redis->ttl($key);
        },$this->connection,$this->poolTimeout);
    }

    public function del($key)
    {
        return RedisPool::invoke(function (Redis $redis) use($key) {
            return $redis->del($key);
        },$this->connection,$this->poolTimeout);
    }
}