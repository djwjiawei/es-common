<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/23
 * Time: 16:52
 */

namespace EsSwoole\Base\Common;

use EasySwoole\Component\Singleton;

/**
 * Class Composer
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class Composer
{
    use Singleton;

    /**
     * 是否初始化过
     *
     * @var bool
     */
    protected $hasInit = false;

    /**
     * 所有包的extra内容
     *
     * @var array[]
     */
    protected $extra = ['config' => [], 'provider' => [], 'command' => []];

    /**
     * Composer constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->initVendorExtra();
    }

    /**
     * 初始化包的extra
     *
     * @return bool
     * @throws \Exception
     * User: dongjw
     * Date: 2022/2/22 15:34
     */
    private function initVendorExtra()
    {
        /**
         * Composer.json格式如下:
         * "extra": {
         * "es-swoole": {
         * "config" : "EsSwoole\\Base\\ConfigPublish",
         * "provider" : ""
         * }
         * }
         */
        if ($this->hasInit) {
            return false;
        }

        $installJsonPath = EASYSWOOLE_ROOT . '/vendor/composer/installed.json';
        if (!file_exists($installJsonPath)) {
            throw new \Exception('composer installed.json不存在');
        }

        $installedArr = json_decode(file_get_contents($installJsonPath), true);
        $packagesArr  = $installedArr['packages'] ?? [];

        foreach ($packagesArr as $vendor) {
            $esExtra = $vendor['extra']['es-swoole'] ?? [];

            if (empty($esExtra)) {
                continue;
            }

            if (!empty($esExtra['config'])) {
                $this->extra['config'][$vendor['name']] = $esExtra['config'];
            }

            if (!empty($esExtra['provider'])) {
                $this->extra['provider'][$vendor['name']] = $esExtra['provider'];
            }

            if (!empty($esExtra['command'])) {
                $this->extra['command'][$vendor['name']] = $esExtra['command'];
            }
        }

        $this->hasInit = true;

        return true;
    }

    /**
     * 获取包的config
     *
     * @param string $vendor
     *
     * @return array|mixed|string
     * User: dongjw
     * Date: 2022/2/22 15:34
     */
    public function getConfigPublish($vendor = '')
    {
        if ($vendor) {
            return $this->extra['config'][$vendor] ?: '';
        }

        return $this->extra['config'];
    }

    /**
     * 获取包的provider
     *
     * @param string $vendor
     *
     * @return array|mixed|string
     * User: dongjw
     * Date: 2022/2/22 15:35
     */
    public function getProvider($vendor = '')
    {
        if ($vendor) {
            return $this->extra['provider'][$vendor] ?: '';
        }

        return $this->extra['provider'];
    }

    /**
     * 获取包的command
     *
     * @param string $vendor
     *
     * @return array|mixed|string
     * User: dongjw
     * Date: 2022/2/22 15:35
     */
    public function getCommand($vendor = '')
    {
        if ($vendor) {
            return $this->extra['command'][$vendor] ?: '';
        }

        return $this->extra['command'];
    }
}
