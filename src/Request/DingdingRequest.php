<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/25
 * Time: 14:23
 */

namespace EsSwoole\Base\Request;

/**
 * 发送钉钉消息
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class DingdingRequest extends AbstractRequest
{
    const SEND_ROBOT_URL = '/robot/send';

    /**
     * DingdingRequest constructor.
     */
    public function __construct()
    {
        $this->apiDomain = config('esCommon.dingdingHost');
    }

    /**
     * 返回响应数据
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/25 14:44
     */
    public function afterRequest()
    {
        if (!empty($this->response->getBody())) {
            return json_decode($this->response->getBody(), true);
        }

        return [];
    }

    /**
     * 发送文本消息
     *
     * @param string $token
     * @param string $msg
     * @param array  $atMobile
     * @param bool   $isAtAll
     *
     * @return array
     * User: dongjw
     * Date: 2022/2/25 14:44
     */
    public function sendText($token, $msg, $atMobile = [], $isAtAll = false)
    {
        return $this->postJson(
            self::SEND_ROBOT_URL . '?access_token=' . $token, [
                'msgtype' => 'text',
                'text'    => [
                    'content' => $msg,
                ],
                'at'      => [
                    'atMobiles' => $atMobile,
                    'isAtAll'   => $isAtAll,
                ],
            ]
        );
    }
}
