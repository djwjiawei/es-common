<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/21
 * Time: 15:44
 */

namespace EsSwoole\Base\Command;

use EasySwoole\Command\CommandManager;
use Swoole\Coroutine\System;

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
     * @var string enbrands校验规则标准名
     */
    protected $enbrandsStandardName = 'Enbrands';

    /**
     * @var int 进程退出成功码
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
     * @param string $phpcs
     *
     * @return int
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
     * @param string $bin
     *
     * @return int
     */
    public function initEnbrandsConfig()
    {
        $bin = EASYSWOOLE_ROOT . '/vendor/bin/phpcs';

        $commands = [
            "{$bin} --config-set default_standard Enbrands",
            "{$bin} --config-set installed_paths {$this->getEnbrandsStdPath()}",
            "{$bin} --config-set php_version {$this->phpVersion4ProductionEnv}",
            "{$bin} --config-show"
        ];
        $res = System::exec(implode(' && ', $commands));

        if ($res['code'] != $this->exitSuccessCode) {
            throw new \Exception($res['output'] ?: '初始化enbrands standard异常');
        }else{
            echo $res['output'] . PHP_EOL;
        }

        return true;
    }

    protected function getCachePath()
    {
        $dir = sys_get_temp_dir() ?: '.';

        return $dir . '/' . $this->cacheFile;
    }

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