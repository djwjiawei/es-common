<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/10
 * Time: 14:13
 */

namespace EsSwoole\Base\Task;

use EasySwoole\Log\LoggerInterface;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use EasySwoole\EasySwoole\Logger;

/**
 * Class LogTask
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class LogTask implements TaskInterface
{
    protected $msg;

    protected $level;

    protected $category;

    /**
     * LogTask constructor.
     *
     * @param string $msg
     * @param string $level
     * @param string $category
     */
    public function __construct($msg, $level, $category)
    {
        $this->msg      = $msg;
        $this->level    = $level;
        $this->category = $category;
    }

    /**
     * Run
     *
     * @param int $taskId
     * @param int $workerIndex
     * User: dongjw
     * Date: 2022/2/22 18:07
     */
    public function run(int $taskId, int $workerIndex)
    {
        \EsSwoole\Base\Log\Logger::getInstance()->logWrite($this->category, $this->level, $this->msg);
    }

    /**
     * 触发异常执行的方法
     *
     * @param \Throwable $throwable
     * @param int        $taskId
     * @param int        $workerIndex
     * User: dongjw
     * Date: 2022/2/22 18:07
     */
    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        Logger::getInstance()->console(
            "logTask Excception: {$throwable->getMessage()} at file:{$throwable->getFile()} line:{$throwable->getLine()}",
            LoggerInterface::LOG_LEVEL_WARNING, 'waring'
        );
    }

}
