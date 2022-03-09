<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/22
 * Time: 10:37
 */

namespace EsSwoole\Base\Provider;

use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\Color;
use EasySwoole\Command\CommandManager;
use EasySwoole\Component\Singleton;
use EsSwoole\Base\Common\Composer;

/**
 * Vendor命令加载类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class CommandLoad
{
    use Singleton;

    protected $hasLoad = false;

    /**
     * 加载vendor命令
     * User: dongjw
     * Date: 2022/2/22 18:01
     */
    public function load()
    {
        if ($this->hasLoad) {
            return;
        }

        $commands = Composer::getInstance()->getCommand();

        if (empty($commands)) {
            return;
        }

        foreach ($commands as $vendor => $vendorCommand) {
            if (!is_array($vendorCommand)) {
                $vendorCommand = [$vendorCommand];
            }

            foreach ($vendorCommand as $commandItem) {
                if (!class_exists($commandItem)) {
                    echo Color::warning($commandItem . '命令未发现') . PHP_EOL;
                    continue;
                }

                $obj = new $commandItem();
                if (!($obj instanceof CommandInterface)) {
                    echo Color::warning($commandItem . '命令未实现CommandInterface') . PHP_EOL;
                    continue;
                }

                CommandManager::getInstance()->addCommand($obj);

//                echo Color::info('find command: ' . $commandItem) . PHP_EOL;
            }
        }

        $this->hasLoad = true;
    }
}
