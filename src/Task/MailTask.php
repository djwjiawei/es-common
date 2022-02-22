<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 15:59
 */

namespace EsSwoole\Base\Task;

use EsSwoole\Base\Common\Mail;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use EasySwoole\EasySwoole\Logger;

/**
 * Class MailTask
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class MailTask implements TaskInterface
{

    protected $conn;

    protected $subject;

    protected $body;

    protected $to;

    /**
     * MailTask constructor.
     *
     * @param string $conn
     * @param string $subject
     * @param string $body
     * @param string $to
     */
    public function __construct($conn, $subject, $body, $to)
    {
        $this->conn    = $conn;
        $this->subject = $subject;
        $this->body    = $body;
        $this->to      = $to;
    }

    /**
     * Run
     *
     * @param int $taskId
     * @param int $workerIndex
     * User: dongjw
     * Date: 2022/2/22 18:08
     */
    public function run(int $taskId, int $workerIndex)
    {
        // 发送邮件
        $res   = Mail::syncSendMail($this->subject, $this->body, $this->to, $this->conn);
        $logTo = is_array($this->to) ? json_encode($this->to) : $this->to;
        Logger::getInstance()->info('邮件发送结果:' . $this->conn . ':' . $logTo . json_encode($res));
    }

    /**
     * 触发异常后执行的方法
     *
     * @param \Throwable $throwable
     * @param int        $taskId
     * @param int        $workerIndex
     * User: dongjw
     * Date: 2022/2/22 18:08
     */
    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 异常处理
        Trigger::getInstance()->throwable($throwable);
    }
}
