<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 11:03
 */

namespace EsSwoole\Base\Command;

use EasySwoole\Command\AbstractInterface\CommandHelpInterface;
use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\Color;
use EasySwoole\Command\CommandManager;
use EasySwoole\Utility\ArrayToTextTable;
use EasySwoole\Utility\File;
use EsSwoole\Base\Abstracts\ConfigPublishInterface;
use EsSwoole\Base\Common\Composer;

/**
 * Class PublishConfig
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class PublishConfig implements CommandInterface
{
    /**
     * 命令名
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/22 15:12
     */
    public function commandName(): string
    {
        return 'publish:config';
    }

    /**
     * Exec
     *
     * @return string|null
     * User: dongjw
     * Date: 2022/2/22 15:12
     */
    public function exec(): ?string
    {
        $vendor = CommandManager::getInstance()->getOpt('vendor') ?: '';
        if ($vendor) {
            $vendorConfig = Composer::getInstance()->getConfigPublish($vendor);
            if (!$vendorConfig) {
                return Color::error('没有该包config发布类');
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

                File::copyFile($source, $destination, false);
                echo Color::success('copy: ' . $source . ' - ' . $destination . ' success') . PHP_EOL;
            }
        } else {
            $vendorConfig = Composer::getInstance()->getConfigPublish();
            if (!$vendorConfig) {
                return Color::warning('未发现需要发布的配置包');
            }

            $table = [];
            foreach ($vendorConfig as $vendor => $publish) {
                $obj = new $publish();
                if (!($obj instanceof ConfigPublishInterface)) {
                    echo Color::warning("{$vendorConfig} 未实现接口类") . PHP_EOL;
                }

                $table[] = [
                    'name'         => $vendor,
                    'publishClass' => $publish,
                ];
            }

            return new ArrayToTextTable($table);
        }

        return null;
    }

    /**
     * Help
     *
     * @param CommandHelpInterface $commandHelp
     *
     * @return CommandHelpInterface
     * User: dongjw
     * Date: 2022/2/22 15:12
     */
    public function help(CommandHelpInterface $commandHelp): CommandHelpInterface
    {
        // 添加 自定义action 可选参数
        $commandHelp->addActionOpt('--vendor=', 'vendor包名,没有展示所有发现的配置包');

        return $commandHelp;
    }

    /**
     * 命令描述
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/22 15:12
     */
    public function desc(): string
    {
        return 'publish vendor config!';
    }
}
