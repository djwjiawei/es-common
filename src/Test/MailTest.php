<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 16:36
 */

require "../../vendor/autoload.php";
class MailTest
{

    public function send()
    {
        go(function (){
            $res = \EsSwoole\Base\Common\Mail::smartSend("test subject",'test body',["1172391478@qq.com","djwjiawei@163.com"],false);
            var_dump($res);
        });

    }
}
(new MailTest())->send();