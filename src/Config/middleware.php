<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/14
 * Time: 14:25
 */

return [
    //全局中间件
    '*' => [
        \EsSwoole\Base\Middleware\RequestMiddleware::class
    ]
];
