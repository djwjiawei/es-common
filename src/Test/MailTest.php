<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/22
 * Time: 16:36
 */

namespace EsSwoole\Base\Test;

/**
 * Class MailTest
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class MailTest
{
    /**
     * 发送邮件
     * User: dongjw
     * Date: 2022/2/22 18:16
     */
    public function send()
    {
        go(
            function () {
                $res = \EsSwoole\Base\Common\Mail::smartSend(
                    'test subject', 'test body', ['1172391478@qq.com', 'djwjiawei@163.com'], false
                );
            }
        );
    }
}
