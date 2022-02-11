<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 15:23
 */
return [
    'swooleHook' => SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL,
    'exception' => [
        //过期时间
        'mailTimeout' => 300,
        //异常发送邮件收件人地址
        'sendBugMail' => '',
        //异常redis记录连接(默认default)
        'redis' => ''
    ],
    'mail' => [
        'default' => [
            'host' => 'smtp.163.com',
            'port' => '465', //默认465
            'username' => 'yunjifen2020@163.com',
            'password' => 'NZLDIMFUDDTPWXNL',
            'from' => '{}服务',
            'timeout' => '' //默认5s
        ]
    ],
    'log' => [
        'mode' => \EsSwoole\Base\Log\Logger::TASK_MODE
    ],
    'provider' => [
        \EsSwoole\Base\Provider\EsProvider::class
    ]
];