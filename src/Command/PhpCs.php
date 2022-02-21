<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/21
 * Time: 15:07
 */

namespace EsSwoole\Base\Command;

use EasySwoole\Command\AbstractInterface\CommandHelpInterface;
use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\Color;
use EasySwoole\Command\CommandManager;
use Swoole\Coroutine\System;
use function Co\run;

class PhpCs implements CommandInterface
{
    use PhpCsHelper;

    /**
     * @var string 校验的文件路径
     */
    protected $path;

    public function commandName(): string
    {
        return 'phpcs';
    }

    public function exec(): ?string
    {
        //获取命令行参数
        $runArgv = CommandManager::getInstance()->getOriginArgv();
        array_shift($runArgv);
        array_shift($runArgv);

        //设置报错级别,主要是为了忽略校验包的warning错误
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

        //运行结果
        $runRes = true;

        run(function () use(&$runRes, $runArgv) {
            try {
                //检查依赖
                if (!file_exists(EASYSWOOLE_ROOT . '/vendor/bin/phpcs')) {
                    throw new \Exception('请先安装squizlabs/php_codesniffer依赖');
                }

                if ($runArgv) {
                    //运行命令参数
                    $runCommand = implode(' ', $runArgv);

                    //如果没有cache,添加默认cache
                    if (strpos($runCommand, '--cache') === false) {
                        $runCommand .= ' --cache='. $this->getCachePath();
                    }

                    echo Color::info("checking...") . PHP_EOL;
                }else{
                    //校验路径检查
                    $checkPath = $this->getCheckDefaultPath();

                    $runCommand = $checkPath . ' --cache=' . $this->getCachePath();

                    echo Color::info("checking {$checkPath}...") . PHP_EOL;
                }

                //初始化校验配置
                $this->initConfig();

                //执行检查
                $res = System::exec(
                    EASYSWOOLE_ROOT . '/vendor/bin/phpcs '.
                    $runCommand
                );

                if ($res['code'] != $this->exitSuccessCode) {
                    throw new \Exception($res['output']);
                }else{
                    echo $res['output'] . PHP_EOL;
                }

            } catch (\Throwable $e) {
                echo Color::error($e->getMessage()) . PHP_EOL;
                $runRes = false;
            }
        });

        if ($runRes) {
            return Color::success('check success');
        }else{
            exit(1);
        }
    }

    public function help(CommandHelpInterface $commandHelp): CommandHelpInterface
    {
        $res = ['output' => ''];
        run(function () use(&$res) {
            $res = System::exec(EASYSWOOLE_ROOT . '/vendor/bin/phpcs -h');
        });
        $commandHelp->addActionOpt('', $res['output']);

        return $commandHelp;
    }

    public function desc(): string
    {
        return '检查 代码风格（支持的参数与选项参见 -h）, 默认检查路径为App或src';
    }

}