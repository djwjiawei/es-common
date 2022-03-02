<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/21
 * Time: 15:44
 */

namespace EsSwoole\Base\Command;

use Swoole\Coroutine\System;

/**
 * Trait PhpCsHelper
 *
 * @package EsSwoole\Base\Command
 */
trait PhpCsHelper
{
    /**
     * PHPCS Cache Path
     *
     * @var string
     */
    protected $cacheFile = 'phpcs_cache';

    /**
     * Same as the PHP version in the production environment
     * e.g.: PHP version 7.4.25 -> value should be 70425
     *
     * @var string
     */
    protected $phpVersion4ProductionEnv = '70425';

    /**
     * Enbrands校验规则标准名
     *
     * @var string
     */
    protected $enbrandsStandardName = 'Enbrands';

    /**
     * 进程退出成功码
     *
     * @var int
     */
    protected $exitSuccessCode = 0;

    /**
     * 获取云积分代码风格规范的路径
     *
     * @return string
     */
    public function getEnbrandsStdPath()
    {
        return EASYSWOOLE_ROOT . '/vendor/enbrands/coding-standard/';
    }

    /**
     * 初始化云积分代码风格规范的设置
     *
     * @return bool|int
     * @throws \Exception
     */
    public function initConfig()
    {
        include EASYSWOOLE_ROOT . '/vendor/squizlabs/php_codesniffer/CodeSniffer.conf';

        $phpcsConfig = $phpCodeSnifferConfig ?? [];

        $defaultStandardName = $phpcsConfig['default_standard'] ?? '';

        return $defaultStandardName != $this->enbrandsStandardName ? $this->initEnbrandsConfig() : true;
    }

    /**
     * 初始化云积分代码风格规范的设置
     *
     * @return bool
     * @throws \Exception
     */
    public function initEnbrandsConfig()
    {
        $bin = EASYSWOOLE_ROOT . '/vendor/bin/phpcs';

        $commands = [
            "{$bin} --config-set default_standard Enbrands",
            "{$bin} --config-set installed_paths {$this->getEnbrandsStdPath()}",
            "{$bin} --config-set php_version {$this->phpVersion4ProductionEnv}",
            "{$bin} --config-show",
        ];
        $res      = System::exec(implode(' && ', $commands));

        if ($res['code'] != $this->exitSuccessCode) {
            throw new \Exception($res['output'] ?: '初始化enbrands standard异常');
        } else {
            echo $res['output'] . PHP_EOL;
        }

        return true;
    }

    /**
     * 获取代码格式校验的缓存路径
     *
     * @return string
     */
    protected function getCachePath()
    {
        return EASYSWOOLE_ROOT . '/' . $this->cacheFile;
    }

    /**
     * 获取校验的默认目录
     *
     * @return string
     * @throws \Exception
     */
    protected function getCheckDefaultPath()
    {
        //先默认为app目录
        $checkPath = EASYSWOOLE_ROOT . '/App';

        //不存在的话,设为src目录,主要是composer包会用这个目录
        if (!is_dir($checkPath) && !is_file($checkPath)) {
            $checkPath = EASYSWOOLE_ROOT . '/src';
        }

        if (!is_dir($checkPath) && !is_file($checkPath)) {
            throw new \Exception($checkPath . ' 资源未找到');
        }

        return $checkPath;
    }
}
