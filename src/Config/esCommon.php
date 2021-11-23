<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 15:23
 */
return [
    'esCommon' => [
        'exception' => [
            //过期时间
            'mailTimeout' => 300,
            //异常发送邮件
            'sendBugMail' => ''
        ],
        'mail' => [
            'default' => [
                'host' => '',
                'port' => '', //默认465
                'username' => '',
                'password' => '',
                'from' => '',
                'timeout' => '' //默认5s
            ]
        ]
    ]
];