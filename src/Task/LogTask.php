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

class LogTask implements TaskInterface
{
    protected $msg;
    
    protected $level;
    
    protected $category;
    
    public function __construct($msg, $level, $category)
    {
        $this->msg = $msg;
        $this->level = $level;
        $this->category = $category;
    }

    public function run(int $taskId, int $workerIndex)
    {
        \EsSwoole\Base\Log\Logger::getInstance()->logWrite($this->category, $this->level, $this->msg);
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        Logger::getInstance()->console("logTask Excception: {$throwable->getMessage()} at file:{$throwable->getFile()} line:{$throwable->getLine()}",LoggerInterface::LOG_LEVEL_WARNING,'waring');
    }

}