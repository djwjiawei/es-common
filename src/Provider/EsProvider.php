<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 15:51
 */

namespace EsSwoole\Base\Provider;


use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use EsSwoole\Base\Abstracts\ProcessMessageInterface;
use EsSwoole\Base\Common\ConfigLoad;
use EsSwoole\Base\Log\Logger;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Exception\ExceptionHandler;
use EsSwoole\Base\Middleware\MiddlewareManager;

class EsProvider extends AbstractProvider
{
    public function register()
    {
        //合并该包配置
        $this->mergeConfig(__DIR__ . '/../config/statusCode.php','statusCode');

        //设置日志handler
        $logger = new Logger();
        Di::getInstance()->set(SysConst::LOGGER_HANDLER, $logger);
        \EasySwoole\EasySwoole\Logger::getInstance($logger);

        //注册异常
        ExceptionHandler::injectException();

        //替换框架内的AbstractProcess,用来分发进程启动事件
        file_put_contents(
            EASYSWOOLE_ROOT . '/vendor/easyswoole/component/src/Process/AbstractProcess.php',
            file_get_contents(__DIR__ . '/../Abstracts/AbstractReplaceProcess.php')
        );

        //中间件初始化
        MiddlewareManager::getInstance();
    }

    public function boot()
    {
        $register = ServerManager::getInstance()->getEventRegister();
        $register->add($register::onPipeMessage, function ($serv, $srcWorkerId, $message) {
            $task = unserialize($message);
            if (is_callable($task)) {
                call_user_func($task);
            }else if ($task instanceof TaskInterface) {
                try{
                    $task->run(0,$serv->worker_id);
                }catch (\Throwable $throwable){
                    $task->onException($throwable,0,$serv->worker_id);
                }
            }else if ($task instanceof ProcessMessageInterface) {
                try{
                    $task->run();
                }catch (\Throwable $throwable){
                    $task->onException($throwable);
                }
            }
        });
    }
}