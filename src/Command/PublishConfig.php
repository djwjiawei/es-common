<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 11:03
 */

namespace EsSwoole\Command;

use EasySwoole\Command\AbstractInterface\CommandHelpInterface;
use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\Color;
use EasySwoole\Command\CommandManager;
use EasySwoole\Utility\File;
use EsSwoole\Base\Abstracts\ConfigPublishInterface;
use EsSwoole\Base\Common\Composer;

class PublishConfig implements CommandInterface
{
    public function commandName(): string
    {
        return 'publish:config';
    }

    public function exec(): ?string
    {
        $vendor = CommandManager::getInstance()->getOpt('vendor') ?: '';
        if ($vendor) {
            $vendorConfig = Composer::getInstance()->getConfigPublish($vendor);
            if (!$vendorConfig) {
                return Color::error("没有该包config发布类");
            }
            $obj = new $vendorConfig();
            if (!($obj instanceof ConfigPublishInterface)) {
                return Color::error("{$vendorConfig} 未实现接口类");
            }
            $needPublish = $obj->publish();
            if (!$needPublish) {
                return Color::warning("{$vendorConfig} 未实现接口类");
            }
            foreach ($needPublish as $source => $destination) {
                if (!file_exists($source)) {
                    echo Color::warning("{$source} 源文件不存在") . PHP_EOL;
                    continue;
                }
                if (file_exists($destination)) {
                    echo Color::warning("{$destination} 目标文件已存在") . PHP_EOL;
                    continue;
                }
                $desDir = dirname($destination);
                if (!is_dir($desDir)) {
                    File::createDirectory($desDir);
                }
                File::copyFile($source,$destination,false);
                echo Color::success($source . " copy: " . $destination . 'success') . PHP_EOL;
            }
        }
    }

    public function help(CommandHelpInterface $commandHelp): CommandHelpInterface
    {
        // 添加 自定义action 可选参数
        $commandHelp->addActionOpt('--vendor=', 'vendor包名');
        return $commandHelp;
    }

    // 设置自定义命令描述
    public function desc(): string
    {
        return 'publish vendor config!';
    }
}