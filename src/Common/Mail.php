<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 16:09
 */

namespace EsSwoole\Base\Common;

use EsSwoole\Base\Task\MailTask;
use EsSwoole\Base\Util\TaskUtil;
use EasySwoole\Smtp\Mailer;
use EasySwoole\Smtp\Protocol\Response;
use EsSwoole\Base\Exception\LogicAssertException;
use EasySwoole\Smtp\Request\Html;

/**
 * 邮件发送
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class Mail
{

    /**
     * 同步发送
     *
     * @param string       $subject
     * @param string       $body
     * @param string|array $to
     * @param bool         $isBatch
     * @param string       $connection
     *
     * @return array
     * User: dongjw
     * Date: 2021/11/22 17:27
     */
    public static function syncSendMail($subject, $body, $to, $isBatch = true, $connection = 'default')
    {
        $checkConn = self::checkConn($connection);
        if ($checkConn['code'] !== 0) {
            return Api::fail($checkConn['msg']);
        }

        //        $checkConn['data'] = [
        //            'host' => 'smtp.163.com',
        //            'username' => 'yunjifen2020@163.com',
        //            'password' => 'NZLDIMFUDDTPWXNL',
        //            'from' => '互动营销分析平台'
        //        ];

        if (is_array($to) && count($to) > 1 && !$isBatch) {
            foreach ($to as $toItem) {
                $res = self::sendMail($checkConn['data'], $subject, $body, $toItem);
                if ($res['code'] !== 0) {
                    return Api::arr($res['code'], $toItem . '发送失败::' . $res['msg']);
                }
            }

            return Api::success();
        } else {
            return self::sendMail($checkConn['data'], $subject, $body, $to);
        }
    }

    /**
     * 异步发送
     *
     * @param string       $subject
     * @param string       $body
     * @param string|array $to
     * @param bool         $isBatch
     * @param string       $connection
     *
     * @return array
     * User: dongjw
     * Date: 2021/11/22 17:27
     */
    public static function asyncSendMail($subject, $body, $to, $isBatch = true, $connection = 'default')
    {
        $checkConn = self::checkConn($connection);
        if ($checkConn['code'] !== 0) {
            return Api::fail($checkConn['msg']);
        }

        if (is_array($to) && count($to) > 1 && !$isBatch) {
            foreach ($to as $toItem) {
                //投递到task中执行
                TaskUtil::async(new MailTask($connection, $subject, $body, $toItem));
            }
        } else {
            //投递到task中执行
            TaskUtil::async(new MailTask($connection, $subject, $body, $to));
        }

        return Api::success();
    }

    /**
     * 检查连接
     *
     * @param string $connection
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/22 15:37
     */
    private static function checkConn($connection)
    {
        try {
            $config = config("esCommon.mail.{$connection}") ?: [];
            Assert::assertNotEmpty($config['host'], 'host不能为空');
            Assert::assertNotEmpty($config['username'], 'username不能为空');
            Assert::assertNotEmpty($config['password'], 'password不能为空');
            Assert::assertNotEmpty($config['from'], 'from不能为空');

            return Api::success($config);
        } catch (LogicAssertException $e) {
            return Api::fail($e->getMessage());
        }
    }

    /**
     * 发送邮件
     *
     * @param array  $config
     * @param string $subject
     * @param string $body
     * @param string $to
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/22 15:38
     */
    private static function sendMail($config, $subject, $body, $to)
    {
        $mail = new Mailer(false);
        $mail->setTimeout($config['timeout'] ?: 5);
        $mail->setHost($config['host']);
        $mail->setUsername($config['username']);
        $mail->setPassword($config['password']);
        $mail->setFrom($config['username'], config('esCommon.serviceName') ?: $config['username']);
        if (is_array($to)) {
            foreach ($to as $toItem) {
                $mail->addAddress($toItem);
            }
        } else {
            $mail->addAddress($to);
        }

        if ($config['port']) {
            $mail->setPort($config['port']);
        }

        $text = new Html();
        $text->setSubject($subject);
        $text->setBody($body);

        $res = $mail->send($text);
        if ($res->getStatus() !== Response::STATUS_OK) {
            return Api::arr($res->getStatus(), $res->getMsg());
        }

        return Api::success();
    }

    /**
     * 根据请求环境自动同步/异步发送
     *
     * @param string $subject
     * @param string $body
     * @param string $to
     * @param bool   $isBatch
     * @param string $connection
     * User: dongjw
     * Date: 2021/11/22 17:34
     */
    public static function smartSend($subject, $body, $to, $isBatch = true, $connection = 'default')
    {
        if (isWorkerProcess()) {
            //worker进程中 异步发送
            return self::asyncSendMail($subject, $body, $to, $isBatch, $connection);
        } else {
            //不在worker进程中 同步发送
            return self::syncSendMail($subject, $body, $to, $isBatch, $connection);
        }
    }
}
