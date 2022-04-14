<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 15:23
 */
return [
    //服务名
    'serviceName' => '{}服务',
    //钉钉api host
    'dingdingHost' => 'https://oapi.dingtalk.com',
    //异常配置
    'exception' => [
        //是否发送报告
        'isReport' => true,
        //多长时间报告一次
        'timeout' => 300,
        //异常redis记录连接(默认default)
        'redis' => '',
        'report' => [
            //邮件报告
            'mail' => [
                //发送邮件地址,可以是数组
                'sendBugMail' => 'dongjw.1@jifenn.com',
                //处理类
                'handle' => \EsSwoole\Base\Exception\MailReport::class,
                //是否发送报告
                'isReport' => true,
            ],
            //钉钉报告
            'dignding' => [
                //Webhook的token
                'token' => '8ff009ddb5fc6aa43979ec565d5bb6604ee672309aa1bab3feab39e75e5c5325',
                //处理类
                'handle' => \EsSwoole\Base\Exception\DingdingReport::class,
                //是否@所有人
                'isAtAll' => false,
                //@手机号
                'atMobiles' => [],
                //是否发送报告
                'isReport' => true
            ]
        ]
    ],
    //发送邮件配置
    'mail' => [
        'default' => [
            'host' => 'smtp.163.com',
            'port' => '465', //默认465
            'username' => 'yunjifen2020@163.com',
            'password' => 'NZLDIMFUDDTPWXNL',
            'timeout' => '' //默认5s
        ]
    ],
    'provider' => [
        \EsSwoole\Base\Provider\EsProvider::class
    ]
];
