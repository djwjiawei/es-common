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
use EsSwoole\Base\Common\Prometheus;
use EasySwoole\Component\Di;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Exception\ExceptionHandler;
use EsSwoole\Base\Middleware\MiddlewareManager;

class EsProvider extends AbstractProvider
{
    public function register()
    {
        ConfigLoad::loadDir(configPath(),configPath(),'php');

        //合并该包配置
        $this->mergeConfig(__DIR__ . '/../config/statusCode.php', 'statusCode');

        //注入Prometheus实例
        $prometheus = new Prometheus();
        Di::getInstance()->set('prometheus', $prometheus);

        //注册异常
        ExceptionHandler::injectException();

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
            } else if ($task instanceof TaskInterface) {
                try {
                    $task->run(0, $serv->worker_id);
                } catch (\Throwable $throwable) {
                    $task->onException($throwable, 0, $serv->worker_id);
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