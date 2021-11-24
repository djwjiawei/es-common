<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/23
 * Time: 16:52
 */

namespace EsSwoole\Base\Common;


use EasySwoole\Component\Singleton;

class Composer
{
    use Singleton;

    protected $hasInit = false;

    protected $extra = ['config' => [],'provider' => []];

    public function __construct()
    {
        $this->_initVendorExtra();
    }

    private function _initVendorExtra()
    {
        /**
         composer.json格式如下:
         "extra": {
            "es-swoole": {
                "config" : "EsSwoole\\Base\\ConfigPublish",
                "provider" : ""
            }
         }
         */
        if (!$this->hasInit) {
            return false;
        }
        $installJsonPath = EASYSWOOLE_ROOT . '/vendor/composer/installed.json';
        if (!file_exists($installJsonPath)) {
            throw new \Exception("composer installed.json不存在");
        }
        $installedArr = json_decode(file_get_contents($installJsonPath),true);
        foreach ($installedArr as $vendor) {
            if (!isset($vendor['extra']['es-swoole'])) {
                continue;
            }
            if (!empty($vendor['extra']['es-swoole']['config'])) {
                $this->extra['config'][$vendor['name']] = $vendor['extra']['es-swoole']['config'];
            }
            if (!empty($vendor['extra']['es-swoole']['provider'])) {
                $this->extra['provider'][$vendor['name']] = $vendor['extra']['es-swoole']['provider'];
            }
        }
        $this->hasInit = true;
        return true;
    }

    public function getConfigPublish($vendor = '')
    {
        if ($vendor) {
            return $this->extra['config'][$vendor] ?: '';
        }
        return $this->extra['config'];
    }

    public function getProvider($vendor = '')
    {
        if ($vendor) {
            return $this->extra['provider'][$vendor] ?: '';
        }
        return $this->extra['provider'];
    }
}