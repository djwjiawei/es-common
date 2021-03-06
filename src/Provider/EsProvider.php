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
use EsSwoole\Base\Common\Event;
use EsSwoole\Base\Common\Prometheus;
use EasySwoole\Component\Di;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Exception\ExceptionHandler;
use EsSwoole\Base\Middleware\MiddlewareManager;

/**
 * Class EsProvider
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class EsProvider extends AbstractProvider
{
    /**
     * 注册服务
     * User: dongjw
     * Date: 2022/2/22 18:02
     */
    public function register()
    {
        ConfigLoad::loadDir(configPath(), configPath(), 'php');

        //合并该包配置
        $this->mergeConfig(__DIR__ . '/../config/statusCode.php', 'statusCode');

        //注入Prometheus实例
        $prometheus = new Prometheus();
        Di::getInstance()->set('prometheus', $prometheus);

        //注册异常
        ExceptionHandler::injectException();

        //中间件初始化
        MiddlewareManager::getInstance();

        //worker进程启动事件
        Event::getInstance()->add(Event::WORKER_PROCESS_START_EVENT, function () {
            //重新播种,以防子进程随机数有问题
            mt_srand();
        });

        //自定义进程启动事件
        Event::getInstance()->add(Event::USER_PROCESS_START_EVENT, function () {
            //重新播种,以防子进程随机数有问题
            mt_srand();
        });
    }

    /**
     * 启动服务
     * User: dongjw
     * Date: 2022/2/22 18:02
     */
    public function boot()
    {
        $register = ServerManager::getInstance()->getEventRegister();

        //Worker进程收到消息事件
        $register->add($register::onPipeMessage, function ($serv, $srcWorkerId, $message) {
            $task = unserialize($message);
            if (is_callable($task)) {
                call_user_func($task);
            } elseif ($task instanceof TaskInterface) {
                try {
                    $task->run(0, $serv->worker_id);
                } catch (\Throwable $throwable) {
                    $task->onException($throwable, 0, $serv->worker_id);
                }
            } elseif ($task instanceof ProcessMessageInterface) {
                try {
                    $task->run();
                } catch (\Throwable $throwable) {
                    $task->onException($throwable);
                }
            }
        });

        //Worker进程启动事件
        $register->add($register::onWorkerStart, function () {
            //触发worker进程启动事件
            Event::getInstance()->hook(Event::WORKER_PROCESS_START_EVENT);
        });
    }
}
