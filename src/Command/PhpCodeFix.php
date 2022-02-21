<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/21
 * Time: 17:11
 */

namespace EsSwoole\Base\Command;

use EasySwoole\Command\AbstractInterface\CommandHelpInterface;
use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\Color;
use EasySwoole\Command\CommandManager;
use Swoole\Coroutine\System;
use function Co\run;

class PhpCodeFix implements CommandInterface
{
    use PhpCsHelper;

    public function commandName(): string
    {
        return 'phpcbf';
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

                    echo Color::info("fixing...") . PHP_EOL;
                }else{
                    //校验路径检查
                    $checkPath = $this->getCheckDefaultPath();

                    $runCommand = $checkPath;

                    echo Color::info("fixing {$checkPath}...") . PHP_EOL;
                }

                //初始化校验配置
                $this->initConfig();

                //执行修复
                $res = System::exec(
                    EASYSWOOLE_ROOT . '/vendor/bin/phpcbf '.
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
            return Color::success('fix success');
        }else{
            echo Color::error('There are other bugs after the fix') . PHP_EOL;
            exit(1);
        }
    }

    public function help(CommandHelpInterface $commandHelp): CommandHelpInterface
    {
        $res = ['output' => ''];
        run(function () use(&$res) {
            $res = System::exec(EASYSWOOLE_ROOT . '/vendor/bin/phpcbf -h');
        });
        $commandHelp->addActionOpt('', $res['output']);

        return $commandHelp;
    }

    public function desc(): string
    {
        return '修复 代码风格（支持的参数与选项参见 -h）, 默认修复路径为App或src';
    }

}