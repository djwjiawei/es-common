<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 15:51
 */

namespace EsSwoole\Base\Provider;


use EsSwoole\Base\Common\ConfigLoad;
use EsSwoole\Base\Log\Logger;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Exception\ExceptionHandler;

class EsProvider extends AbstractProvider
{
    public function register()
    {
        //加载config目录的配置
        ConfigLoad::loadDir(configPath(),configPath());

        //合并该包配置
        $this->mergeConfig(__DIR__ . '/../config/statusCode.php','statusCode');

        //设置日志handler
        $logger = new Logger();
        Di::getInstance()->set(SysConst::LOGGER_HANDLER, $logger);
        \EasySwoole\EasySwoole\Logger::getInstance($logger);

        //注册异常
        ExceptionHandler::injectException();
    }
}